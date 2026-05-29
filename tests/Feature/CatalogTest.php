<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Gost;
use App\Models\Product;

it('renders catalog index with root categories only', function () {
    $root = Category::factory()->create(['name' => 'Root Cat A', 'published' => true]);
    Category::factory()->create(['name' => 'Child Cat B', 'parent_id' => $root->id, 'published' => true]);
    Category::factory()->create(['name' => 'Draft Root C', 'published' => false]);

    $r = $this->get('/catalog');

    $r->assertStatus(200);
    $r->assertSee('Каталог продукции', false);
    $r->assertSee('Root Cat A', false);
    $r->assertDontSee('Child Cat B', false);
    $r->assertDontSee('Draft Root C', false);
});

it('shows category page with its published products', function () {
    $cat = Category::factory()->create(['name' => 'Кольца', 'slug' => 'koltsa']);
    $p1 = Product::factory()->create(['name' => 'Кольцо КС10', 'slug' => 'ks-10']);
    $p2 = Product::factory()->create(['name' => 'Кольцо КС15', 'slug' => 'ks-15']);
    $draft = Product::factory()->unpublished()->create(['name' => 'Draft Кольцо']);
    $p1->categories()->attach($cat);
    $p2->categories()->attach($cat);
    $draft->categories()->attach($cat);

    $r = $this->get('/catalog/koltsa');

    $r->assertStatus(200);
    $r->assertSee('Кольца', false);
    $r->assertSee('Кольцо КС10', false);
    $r->assertSee('Кольцо КС15', false);
    $r->assertDontSee('Draft Кольцо', false);
});

it('404s an unpublished category by direct URL', function () {
    Category::factory()->unpublished()->create(['slug' => 'hidden-cat']);

    $this->get('/catalog/hidden-cat')->assertStatus(404);
});

it('renders breadcrumbs on category page', function () {
    Category::factory()->create(['name' => 'Плиты перекрытия', 'slug' => 'pp']);

    $r = $this->get('/catalog/pp');

    $r->assertSeeInOrder(['Главная', 'Каталог', 'Плиты перекрытия'], false);
});

it('shows product detail at nested URL', function () {
    $cat = Category::factory()->create(['name' => 'Кольца', 'slug' => 'koltsa']);
    $p = Product::factory()->create(['name' => 'КС10', 'slug' => 'ks-10']);
    $p->categories()->attach($cat);

    $gost = Gost::create([
        'kind' => Gost::KIND_GOST,
        'label' => '8020-90',
        'slug' => 'gost-8020-90',
    ]);
    $p->gosts()->attach($gost);

    $r = $this->get('/catalog/koltsa/ks-10');

    $r->assertStatus(200);
    $r->assertSee('КС10', false);
    $r->assertSee('ГОСТ 8020-90', false);
    $r->assertSee('В корзину', false);
});

it('shows «Запросить цену» CTA when price is hidden', function () {
    $cat = Category::factory()->create(['slug' => 'cat-x']);
    $p = Product::factory()->priceHidden()->create(['name' => 'Custom', 'slug' => 'custom']);
    $p->categories()->attach($cat);

    $r = $this->get('/catalog/cat-x/custom');

    $r->assertSee('Запросить цену', false);
    $r->assertDontSee('В корзину', false);
});

it('404s product when URL category does not match its categories', function () {
    $realCat = Category::factory()->create(['slug' => 'real-cat']);
    $otherCat = Category::factory()->create(['slug' => 'other-cat']);
    $p = Product::factory()->create(['slug' => 'some-prod']);
    $p->categories()->attach($realCat);

    $this->get('/catalog/other-cat/some-prod')->assertStatus(404);
});

it('paginates products with 12 per page', function () {
    $cat = Category::factory()->create(['slug' => 'big-cat']);
    Product::factory()->count(15)->create()->each(fn ($p) => $p->categories()->attach($cat));

    $r = $this->get('/catalog/big-cat');
    $r->assertStatus(200);
    $r->assertSee('big-cat?page=2', false);
});
