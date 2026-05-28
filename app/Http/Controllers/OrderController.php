<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Post-checkout thank-you + invoice download.
 *
 * Order URLs are guessable (sequential T-NNNNNN) so we deliberately
 * do NOT show full customer details on the success page beyond what
 * was just submitted in the form — name + total + status + invoice
 * link is enough for "did the order go through?" UX while not
 * leaking PII to anyone who walks up to the URL.
 */
final class OrderController extends Controller
{
    public function show(Order $order): View
    {
        return view('checkout.success', ['order' => $order]);
    }

    public function invoice(Order $order): BinaryFileResponse
    {
        if ($order->payment_method !== PaymentMethod::BankTransfer || ! $order->invoice_pdf_path) {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($order->invoice_pdf_path)) {
            abort(404);
        }

        return response()->download(
            $disk->path($order->invoice_pdf_path),
            'invoice-'.$order->order_number.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
