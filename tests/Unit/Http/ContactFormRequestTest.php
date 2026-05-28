<?php

declare(strict_types=1);

use App\Http\Requests\ContactFormRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;

function contactErrors(array $payload): array
{
    $req = new ContactFormRequest($payload);

    return Validator::make($payload, $req->rules())->errors()->keys();
}

it('accepts minimum valid payload', function () {
    $errors = contactErrors([
        'name' => 'Иван',
        'phone' => '+77001234567',
    ]);

    expect($errors)->toBe([]);
});

it('requires name and phone', function () {
    $errors = contactErrors([]);

    expect($errors)->toContain('name')
        ->and($errors)->toContain('phone');
});

it('allows null email but rejects malformed one', function () {
    $okErrors = contactErrors([
        'name' => 'Иван',
        'phone' => '+77001234567',
    ]);

    $badErrors = contactErrors([
        'name' => 'Иван',
        'phone' => '+77001234567',
        'email' => 'not-an-email',
    ]);

    expect($okErrors)->toBe([])
        ->and($badErrors)->toContain('email');
});

it('rejects non-existent product_id', function () {
    $errors = contactErrors([
        'name' => 'Иван',
        'phone' => '+77001234567',
        'product_id' => 999999,
    ]);

    expect($errors)->toContain('product_id');
});

it('accepts existing product_id', function () {
    $product = Product::factory()->create();
    $errors = contactErrors([
        'name' => 'Иван',
        'phone' => '+77001234567',
        'product_id' => $product->id,
    ]);

    expect($errors)->toBe([]);
});
