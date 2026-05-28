<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\Cart\Cart;
use App\Services\Invoices\InvoiceGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Creates an Order + OrderItems from a Cart + CheckoutData. Side-effects:
 *   1) Atomically (in DB transaction) write Order + items with snapshot
 *      fields and a generated order_number.
 *   2) Clear the cart from the session.
 *   3) For bank-transfer orders, generate a PDF invoice and store its
 *      relative path on the order.
 *   4) Send customer confirmation + admin notification emails. Failures
 *      are logged but DON'T roll back the order — the customer should
 *      still see the "thank you" page and admin can resend manually via
 *      Filament.
 */
final class OrderService
{
    public function __construct(
        private readonly OrderNumberGenerator $numbers,
        private readonly InvoiceGenerator $invoices,
    ) {}

    public function create(CheckoutData $data, Cart $cart): Order
    {
        if ($cart->isEmpty()) {
            throw new RuntimeException('Cannot create an order from an empty cart.');
        }

        $order = DB::transaction(function () use ($data, $cart): Order {
            $order = new Order;
            $order->order_number = $this->numbers->next();
            $order->customer_type = $data->customerType;
            $order->customer_name = $data->customerName;
            $order->customer_email = $data->customerEmail;
            $order->customer_phone = $data->customerPhone;
            $order->customer_company_name = $data->customerCompanyName;
            $order->customer_bin = $data->customerBin;
            $order->customer_address = $data->customerAddress;
            $order->delivery_method = $data->deliveryMethod;
            $order->delivery_address = $data->deliveryAddress;
            $order->payment_method = $data->paymentMethod;
            $order->comment = $data->comment;
            $order->status = OrderStatus::New;
            $order->subtotal = $cart->subtotal();
            $order->total = $cart->subtotal();
            $order->save();

            foreach ($cart->items() as $cartItem) {
                $product = $cartItem->product();
                if ($product === null) {
                    // Skip silently — product was deleted between adding to
                    // cart and checkout. Customer still gets the rest.
                    continue;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price' => $cartItem->unitPrice,
                    'unit' => $cartItem->unit,
                    'qty' => $cartItem->qty,
                    'line_total' => $cartItem->lineTotal(),
                ]);
            }

            return $order;
        });

        $cart->clear();

        if ($order->payment_method->generatesInvoice()) {
            $this->tryGenerateInvoice($order);
        }

        $this->dispatchEmails($order);

        return $order->fresh(['items']) ?? $order;
    }

    private function tryGenerateInvoice(Order $order): void
    {
        try {
            $path = $this->invoices->generate($order);
            $order->invoice_pdf_path = $path;
            $order->save();
        } catch (\Throwable $e) {
            Log::error('Failed to generate invoice PDF', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchEmails(Order $order): void
    {
        $adminEmail = Setting::current()->email_recipient
            ?? (string) config('triad.inquiry_email');

        try {
            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));

            if ($adminEmail !== '') {
                Mail::to($adminEmail)->send(new NewOrderAdminMail($order));
            }

            $order->notification_sent = true;
            $order->save();
        } catch (\Throwable $e) {
            Log::error('Failed to send order notification emails', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'exception' => $e->getMessage(),
            ]);
            // Don't rethrow — admin can resend from Filament; the order
            // itself is persisted and visible.
        }
    }
}
