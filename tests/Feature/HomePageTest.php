<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;

it('renders home with hero + sections', function () {
    $r = $this->get('/');

    $r->assertStatus(200);
    $r->assertSee('Железобетонные изделия', false);
    $r->assertSee('Перейти в каталог', false);
});

it('lists only root + published categories on home', function () {
    $root = Category::factory()->create(['name' => 'Бетонные кольца X', 'published' => true]);
    $draft = Category::factory()->create(['name' => 'Draft Cat', 'published' => false]);
    $child = Category::factory()->create(['parent_id' => $root->id, 'name' => 'Child Y', 'published' => true]);

    $r = $this->get('/');

    $r->assertSee('Бетонные кольца X', false);
    $r->assertDontSee('Draft Cat', false);
    $r->assertDontSee('Child Y', false);
});

it('lists only featured + published products in the recommendations block', function () {
    $cat = Category::factory()->create();
    $featured = Product::factory()->featured()->create(['name' => 'Feat Product Z']);
    $featured->categories()->attach($cat);

    $regular = Product::factory()->create(['name' => 'Regular Product W']);
    $regular->categories()->attach($cat);

    $unpubFeatured = Product::factory()->featured()->unpublished()->create(['name' => 'Hidden Feat V']);
    $unpubFeatured->categories()->attach($cat);

    $r = $this->get('/');

    $r->assertSee('Feat Product Z', false);
    $r->assertDontSee('Regular Product W', false);
    $r->assertDontSee('Hidden Feat V', false);
});

it('lists only published articles, latest first', function () {
    $older = Article::factory()->create([
        'title' => 'Older Article',
        'published_at' => now()->subDays(10),
    ]);
    $newer = Article::factory()->create([
        'title' => 'Newer Article',
        'published_at' => now()->subDay(),
    ]);
    $draft = Article::factory()->create([
        'title' => 'Draft Article',
        'published_at' => null,
    ]);

    $r = $this->get('/');

    $r->assertSeeInOrder(['Newer Article', 'Older Article'], false);
    $r->assertDontSee('Draft Article', false);
});
