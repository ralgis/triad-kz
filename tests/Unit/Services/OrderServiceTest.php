<?php

declare(strict_types=1);

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart\Cart;
use App\Services\Invoices\InvoiceGenerator;
use App\Services\Orders\CheckoutData;
use App\Services\Orders\OrderNumberGenerator;
use App\Services\Orders\OrderService;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->session = new Store('test', new ArraySessionHandler(60));
    $this->cart = new Cart($this->session);
});

function makeCheckout(array $overrides = []): CheckoutData
{
    $defaults = [
        'customerType' => CustomerType::Individual,
        'customerName' => 'Иванов Иван',
        'customerEmail' => 'i@example.com',
        'customerPhone' => '+77001234567',
        'customerCompanyName' => null,
        'customerBin' => null,
        'customerAddress' => 'Алматы, ул. Тестовая 1',
        'deliveryMethod' => DeliveryMethod::Pickup,
        'deliveryAddress' => null,
        'paymentMethod' => PaymentMethod::Cash,
        'comment' => null,
    ];

    return new CheckoutData(...array_merge($defaults, $overrides));
}

function makeService(): OrderService
{
    return new OrderService(
        new OrderNumberGenerator,
        new InvoiceGenerator,
    );
}

it('refuses to create an order from an empty cart', function () {
    makeService()->create(makeCheckout(), $this->cart);
})->throws(RuntimeException::class);

it('creates Order + OrderItems with snapshot fields', function () {
    $product = Product::factory()->create([
        'name' => 'Кольцо КС15',
        'sku' => 'KS-15',
        'price' => 1000,
        'unit_for_order' => 'шт',
    ]);

    $this->cart->add($product, 3);

    $order = makeService()->create(makeCheckout(), $this->cart);

    expect($order->order_number)->toBe('T-000001')
        ->and($order->status)->toBe(OrderStatus::New)
        ->and((float) $order->total)->toBe(3000.0)
        ->and($order->items)->toHaveCount(1);

    $item = $order->items->first();
    expect($item->product_name)->toBe('Кольцо КС15')
        ->and($item->product_sku)->toBe('KS-15')
        ->and((float) $item->unit_price)->toBe(1000.0)
        ->and($item->qty)->toBe(3)
        ->and((float) $item->line_total)->toBe(3000.0);
});

it('clears the cart after a successful order', function () {
    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 2);

    makeService()->create(makeCheckout(), $this->cart);

    expect($this->cart->isEmpty())->toBeTrue();
});

it('sends customer + admin emails', function () {
    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 1);

    makeService()->create(makeCheckout([
        'customerEmail' => 'buyer@example.com',
    ]), $this->cart);

    // OrderConfirmationMail + NewOrderAdminMail implement ShouldQueue, so
    // Mail::to()->send() pushes onto the queue instead of sending directly.
    // Mail::fake() records these as queued, not sent.
    Mail::assertQueued(OrderConfirmationMail::class, 1);
    Mail::assertQueued(NewOrderAdminMail::class, 1);
});

it('snapshots survive product deletion', function () {
    $product = Product::factory()->create([
        'name' => 'Будет удалён',
        'sku' => 'DEL-1',
        'price' => 555,
    ]);

    $this->cart->add($product, 2);
    $order = makeService()->create(makeCheckout(), $this->cart);

    $product->forceDelete();
    $order->refresh()->load('items');

    expect($order->items->first()->product_name)->toBe('Будет удалён')
        ->and((float) $order->items->first()->line_total)->toBe(1110.0);
});

it('does not generate an invoice for cash orders', function () {
    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 1);

    $order = makeService()->create(makeCheckout([
        'paymentMethod' => PaymentMethod::Cash,
    ]), $this->cart);

    expect($order->invoice_pdf_path)->toBeNull();
});

it('persists order even when mail dispatch fails', function () {
    // Re-fake to throw on send.
    Mail::shouldReceive('to')->andReturnSelf();
    Mail::shouldReceive('send')->andThrow(new RuntimeException('SMTP boom'));

    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 1);

    $order = makeService()->create(makeCheckout(), $this->cart);

    expect(Order::find($order->id))->not->toBeNull()
        ->and($order->notification_sent)->toBeFalse();
});
