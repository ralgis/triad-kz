<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;

function extractJsonLd(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $blocks = [];
    foreach ($m[1] as $raw) {
        $blocks[] = json_decode(trim($raw), true, flags: JSON_THROW_ON_ERROR);
    }

    return $blocks;
}

it('emits valid Organization JSON-LD on every page', function () {
    $blocks = extractJsonLd($this->get('/')->getContent());

    $org = collect($blocks)->firstWhere('@type', 'Organization');
    expect($org)->not->toBeNull()
        ->and($org['@context'])->toBe('https://schema.org')
        ->and($org['url'])->toBe(url('/'));
});

it('emits Product JSON-LD on product detail pages', function () {
    $cat = Category::factory()->create(['slug' => 'sc-cat']);
    $p = Product::factory()->create([
        'name' => 'JLD Product',
        'slug' => 'jld-prod',
        'sku' => 'SKU-1',
        'price' => 12000,
    ]);
    $p->categories()->attach($cat);

    $blocks = extractJsonLd($this->get('/catalog/sc-cat/jld-prod')->getContent());

    $product = collect($blocks)->firstWhere('@type', 'Product');
    expect($product)->not->toBeNull()
        ->and($product['name'])->toBe('JLD Product')
        ->and($product['sku'])->toBe('SKU-1')
        ->and($product['offers']['priceCurrency'])->toBe('KZT')
        ->and($product['offers']['price'])->toBe('12000.00');
});

it('omits offers when price is hidden', function () {
    $cat = Category::factory()->create(['slug' => 'sc-cat']);
    $p = Product::factory()->priceHidden()->create(['name' => 'Hidden P', 'slug' => 'hp']);
    $p->categories()->attach($cat);

    $blocks = extractJsonLd($this->get('/catalog/sc-cat/hp')->getContent());

    $product = collect($blocks)->firstWhere('@type', 'Product');
    expect($product)->not->toBeNull()
        ->and($product)->not->toHaveKey('offers');
});

it('emits Article JSON-LD on article pages', function () {
    $a = Article::factory()->create([
        'title' => 'Schema Article',
        'slug' => 'schema-art',
        'published_at' => now()->subDay(),
    ]);

    $blocks = extractJsonLd($this->get('/blog/schema-art')->getContent());

    $article = collect($blocks)->firstWhere('@type', 'Article');
    expect($article)->not->toBeNull()
        ->and($article['headline'])->toBe('Schema Article')
        ->and($article['mainEntityOfPage']['@id'])->toBe($a->url());
});

it('emits BreadcrumbList JSON-LD where breadcrumbs render', function () {
    $cat = Category::factory()->create(['slug' => 'bc-cat', 'name' => 'BC Cat']);

    $blocks = extractJsonLd($this->get('/catalog/bc-cat')->getContent());

    $bc = collect($blocks)->firstWhere('@type', 'BreadcrumbList');
    expect($bc)->not->toBeNull()
        ->and($bc['itemListElement'][0]['name'])->toBe('Главная')
        ->and($bc['itemListElement'][1]['name'])->toBe('Каталог')
        ->and($bc['itemListElement'][2]['name'])->toBe('BC Cat')
        ->and($bc['itemListElement'][2]['position'])->toBe(3);
});
