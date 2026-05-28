<?php

declare(strict_types=1);

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderConfirmationMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('redirects to /cart when checkout opened with empty cart', function () {
    $this->get('/checkout')
        ->assertRedirect(route('cart.show'))
        ->assertSessionHas('cart.empty');
});

it('renders the checkout form when cart has items', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create();
    $p->categories()->attach($cat);
    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 1]);

    $r = $this->get('/checkout');
    $r->assertStatus(200);
    $r->assertSee('Оформление заказа', false);
    $r->assertSee('Тип покупателя', false);
});

it('creates an order on valid submit and redirects to success page', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create(['name' => 'Кольцо X', 'price' => 5000]);
    $p->categories()->attach($cat);
    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 2]);

    $r = $this->post('/checkout', [
        'customer_type' => CustomerType::Individual->value,
        'customer_name' => 'Иван Иванов',
        'customer_email' => 'ivan@example.com',
        'customer_phone' => '+77011234567',
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    $order = Order::query()->latest('id')->first();
    expect($order)->not->toBeNull()
        ->and($order->customer_name)->toBe('Иван Иванов')
        ->and((float) $order->total)->toBe(10000.0)
        ->and($order->status)->toBe(OrderStatus::New);

    $r->assertRedirect(route('order.show', ['order' => $order->order_number]));

    // OrderService dispatches mailables; both queued.
    Mail::assertQueued(OrderConfirmationMail::class);
    Mail::assertQueued(NewOrderAdminMail::class);
});

it('rejects legal customer without БИН', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create();
    $p->categories()->attach($cat);
    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 1]);

    $this->from('/checkout')->post('/checkout', [
        'customer_type' => CustomerType::Legal->value,
        'customer_name' => 'ТОО Стройка',
        'customer_email' => 'a@b.kz',
        'customer_phone' => '+77011234567',
        'customer_company_name' => 'ТОО Стройка',
        // BIN missing on purpose
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::BankTransfer->value,
    ])->assertSessionHasErrors('customer_bin');
});

it('clears the cart on successful checkout', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create();
    $p->categories()->attach($cat);
    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 1]);

    $this->post('/checkout', [
        'customer_type' => CustomerType::Individual->value,
        'customer_name' => 'И. И.',
        'customer_email' => 'i@i.kz',
        'customer_phone' => '+77011234567',
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    expect(session('cart.items'))->toBeEmpty();
});

it('shows the order on the success page', function () {
    $order = Order::factory()->create();

    $r = $this->get('/order/'.$order->order_number);
    $r->assertStatus(200);
    $r->assertSee($order->order_number, false);
    $r->assertSee('Спасибо', false);
});

it('404s an unknown order number', function () {
    $this->get('/order/T-999999')->assertStatus(404);
});

it('404s invoice download for cash orders', function () {
    $order = Order::factory()->create([
        'payment_method' => PaymentMethod::Cash,
        'invoice_pdf_path' => null,
    ]);

    $this->get(route('order.invoice', ['order' => $order->order_number]))
        ->assertStatus(404);
});

it('404s invoice download when bank-transfer order has no PDF file on disk', function () {
    $order = Order::factory()->create([
        'payment_method' => PaymentMethod::BankTransfer,
        'invoice_pdf_path' => 'invoices/does-not-exist.pdf',
    ]);

    $this->get(route('order.invoice', ['order' => $order->order_number]))
        ->assertStatus(404);
});
