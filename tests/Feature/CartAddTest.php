<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;

it('adds a product to the cart and redirects back with success', function () {
    $cat = Category::factory()->create(['slug' => 'cat-z']);
    $p = Product::factory()->create(['name' => 'Прод W', 'slug' => 'prod-w']);
    $p->categories()->attach($cat);

    $r = $this->from('/catalog/cat-z/prod-w')
        ->post('/cart/add', ['product_id' => $p->id, 'qty' => 2]);

    $r->assertRedirect('/catalog/cat-z/prod-w');
    $r->assertSessionHas('cart.added', 'Прод W');
    // Session is the source of truth for the cart; app(Cart) in test scope
    // wraps a different Session instance so we inspect items directly.
    expect(session('cart.items'))->toHaveCount(1);
    $entry = array_values(session('cart.items'))[0];
    expect($entry['product_id'])->toBe($p->id)
        ->and($entry['qty'])->toBe(2);
});

it('refuses to add an unpublished product', function () {
    $p = Product::factory()->unpublished()->create();

    $r = $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 1]);

    $r->assertStatus(404);
});

it('validates product_id presence', function () {
    $this->from('/')->post('/cart/add', ['qty' => 1])
        ->assertSessionHasErrors('product_id');
});

it('caps qty at 999', function () {
    $cat = Category::factory()->create(['slug' => 'cat-y']);
    $p = Product::factory()->create(['slug' => 'p-y']);
    $p->categories()->attach($cat);

    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 1000])
        ->assertSessionHasErrors('qty');
});
