<?php

declare(strict_types=1);

use App\Models\Product;
use App\Services\Cart\Cart;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

beforeEach(function () {
    // A fresh in-memory session for every test — no cross-test bleed.
    $this->session = new Store('test', new ArraySessionHandler(60));
    $this->cart = new Cart($this->session);
});

it('starts empty', function () {
    expect($this->cart->isEmpty())->toBeTrue()
        ->and($this->cart->count())->toBe(0)
        ->and($this->cart->subtotal())->toBe('0.00');
});

it('adds a product and snapshots its price', function () {
    $product = Product::factory()->create([
        'price' => 100,
        'unit_for_order' => 'м',
    ]);

    $this->cart->add($product, 3);

    expect($this->cart->count())->toBe(3)
        ->and($this->cart->subtotal())->toBe('300.00');

    $items = $this->cart->items();
    expect($items[$product->id]->unit)->toBe('м')
        ->and($items[$product->id]->qty)->toBe(3);
});

it('accumulates qty when adding the same product twice', function () {
    $product = Product::factory()->create(['price' => 50]);

    $this->cart->add($product, 2);
    $this->cart->add($product, 5);

    expect($this->cart->count())->toBe(7)
        ->and($this->cart->subtotal())->toBe('350.00');
});

it('keeps the original price snapshot when product price changes mid-session', function () {
    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 1);

    // Admin "changes price" while customer's session is open
    $product->update(['price' => 999]);

    expect($this->cart->subtotal())->toBe('100.00');
});

it('updates qty to a new value', function () {
    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 5);

    $this->cart->update($product->id, 2);

    expect($this->cart->count())->toBe(2)
        ->and($this->cart->subtotal())->toBe('200.00');
});

it('removes a product when updating qty to 0', function () {
    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 5);

    $this->cart->update($product->id, 0);

    expect($this->cart->isEmpty())->toBeTrue();
});

it('removes a product explicitly', function () {
    $a = Product::factory()->create(['price' => 100]);
    $b = Product::factory()->create(['price' => 50]);
    $this->cart->add($a, 1);
    $this->cart->add($b, 2);

    $this->cart->remove($a->id);

    expect($this->cart->count())->toBe(2)
        ->and($this->cart->subtotal())->toBe('100.00');
});

it('clears completely', function () {
    $this->cart->add(Product::factory()->create(['price' => 100]), 5);
    $this->cart->clear();

    expect($this->cart->isEmpty())->toBeTrue();
});

it('handles products with hidden price by snapshotting 0', function () {
    $product = Product::factory()->create([
        'price' => null,
        'price_visible' => false,
    ]);

    $this->cart->add($product, 1);

    expect($this->cart->subtotal())->toBe('0.00')
        ->and($this->cart->items()[$product->id]->unitPrice)->toBe('0.00');
});

it('does not add when qty < 1', function () {
    $product = Product::factory()->create(['price' => 100]);
    $this->cart->add($product, 0);
    $this->cart->add($product, -5);

    expect($this->cart->isEmpty())->toBeTrue();
});
