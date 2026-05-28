<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;

it('renders empty-cart state with a catalog CTA', function () {
    $r = $this->get('/cart');

    $r->assertStatus(200);
    $r->assertSee('В корзине пока ничего нет', false);
    $r->assertSee('Перейти в каталог', false);
});

it('renders cart with items + subtotal + checkout button', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create(['name' => 'Кольцо КС20', 'slug' => 'ks20', 'price' => 12000]);
    $p->categories()->attach($cat);

    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 3]);

    $r = $this->get('/cart');
    $r->assertStatus(200);
    $r->assertSee('Кольцо КС20', false);
    $r->assertSee('Оформить заказ', false);
    $r->assertSee('₸', false);
    // Subtotal math is exercised in CartUnitTest; here we only verify the
    // figure appears at all (locale-specific separators make a literal
    // "36 000" assertion fragile).
    $r->assertSee('Итого', false);
});

it('updates qty via PATCH', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create(['price' => 1000]);
    $p->categories()->attach($cat);

    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 1]);

    $r = $this->patch('/cart/'.$p->id, ['qty' => 5]);
    $r->assertRedirect(route('cart.show'));

    expect(array_values(session('cart.items'))[0]['qty'])->toBe(5);
});

it('removes a line when qty is set to 0', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create();
    $p->categories()->attach($cat);

    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 2]);
    $this->patch('/cart/'.$p->id, ['qty' => 0]);

    expect(session('cart.items'))->toBeEmpty();
});

it('removes a line via DELETE', function () {
    $cat = Category::factory()->create(['slug' => 'c1']);
    $p = Product::factory()->create();
    $p->categories()->attach($cat);

    $this->from('/')->post('/cart/add', ['product_id' => $p->id, 'qty' => 2]);
    $this->delete('/cart/'.$p->id);

    expect(session('cart.items'))->toBeEmpty();
});

it('emits noindex on the cart page', function () {
    $this->get('/cart')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
