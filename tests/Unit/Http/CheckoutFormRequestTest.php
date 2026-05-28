<?php

declare(strict_types=1);

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Http\Requests\CheckoutFormRequest;
use Illuminate\Support\Facades\Validator;

function validateCheckout(array $payload): array
{
    $req = CheckoutFormRequest::create('/checkout/submit', 'POST', $payload);
    // Trigger prepareForValidation so phone gets normalized first.
    $req->setContainer(app())->setRedirector(app('redirect'));
    $req->validateResolved();

    return [];
}

function checkoutErrors(array $payload): array
{
    $req = new CheckoutFormRequest($payload);

    return Validator::make($payload, $req->rules(), $req->messages())->errors()->keys();
}

it('accepts a complete individual checkout', function () {
    $errors = checkoutErrors([
        'customer_type' => CustomerType::Individual->value,
        'customer_name' => 'Иванов Иван',
        'customer_email' => 'i@example.com',
        'customer_phone' => '+77001234567',
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    expect($errors)->toBe([]);
});

it('requires company_name + БИН for legal customers', function () {
    $errors = checkoutErrors([
        'customer_type' => CustomerType::Legal->value,
        'customer_name' => 'Иванов Иван',
        'customer_email' => 'i@example.com',
        'customer_phone' => '+77001234567',
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::BankTransfer->value,
    ]);

    expect($errors)->toContain('customer_company_name')
        ->and($errors)->toContain('customer_bin');
});

it('accepts a complete legal checkout', function () {
    $errors = checkoutErrors([
        'customer_type' => CustomerType::Legal->value,
        'customer_name' => 'Иванов Иван',
        'customer_email' => 'i@example.com',
        'customer_phone' => '+77001234567',
        'customer_company_name' => 'ТОО Тестовая',
        'customer_bin' => '123456789012',
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::BankTransfer->value,
    ]);

    expect($errors)->toBe([]);
});

it('rejects malformed БИН for legal customer', function () {
    $errors = checkoutErrors([
        'customer_type' => CustomerType::Legal->value,
        'customer_name' => 'Иванов Иван',
        'customer_email' => 'i@example.com',
        'customer_phone' => '+77001234567',
        'customer_company_name' => 'ТОО Тестовая',
        'customer_bin' => 'not-a-bin',
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::BankTransfer->value,
    ]);

    expect($errors)->toContain('customer_bin');
});

it('requires delivery_address when delivery_method=delivery', function () {
    $errors = checkoutErrors([
        'customer_type' => CustomerType::Individual->value,
        'customer_name' => 'Иванов Иван',
        'customer_email' => 'i@example.com',
        'customer_phone' => '+77001234567',
        'delivery_method' => DeliveryMethod::Delivery->value,
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    expect($errors)->toContain('delivery_address');
});

it('rejects invalid email', function () {
    $errors = checkoutErrors([
        'customer_type' => CustomerType::Individual->value,
        'customer_name' => 'Иванов Иван',
        'customer_email' => 'not-an-email',
        'customer_phone' => '+77001234567',
        'delivery_method' => DeliveryMethod::Pickup->value,
        'payment_method' => PaymentMethod::Cash->value,
    ]);

    expect($errors)->toContain('customer_email');
});
