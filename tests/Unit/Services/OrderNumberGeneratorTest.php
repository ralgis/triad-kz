<?php

declare(strict_types=1);

use App\Models\Order;
use App\Services\Orders\OrderNumberGenerator;

it('starts at T-000001 when no orders exist', function () {
    $gen = new OrderNumberGenerator;
    expect($gen->next())->toBe('T-000001');
});

it('increments from the latest existing order_number', function () {
    Order::factory()->create(['order_number' => 'T-000042']);

    $gen = new OrderNumberGenerator;
    expect($gen->next())->toBe('T-000043');
});

it('pads to at least 6 digits', function () {
    Order::factory()->create(['order_number' => 'T-000099']);

    expect((new OrderNumberGenerator)->next())->toBe('T-000100');
});

it('keeps growing past 6 digits', function () {
    Order::factory()->create(['order_number' => 'T-999999']);

    // No truncation, just extra digits.
    expect((new OrderNumberGenerator)->next())->toBe('T-1000000');
});
