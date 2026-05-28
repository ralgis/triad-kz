<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;

it('serves an XML sitemap with the static entry points', function () {
    $r = $this->get('/sitemap.xml');

    $r->assertStatus(200);
    $r->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

    $body = $r->getContent();
    expect($body)->toContain('<urlset')
        ->and($body)->toContain(url('/'))
        ->and($body)->toContain(url('/catalog'))
        ->and($body)->toContain(url('/blog'))
        ->and($body)->toContain(url('/contacts'));
});

it('lists published categories, products, articles, and pages', function () {
    $cat = Category::factory()->create(['slug' => 'sm-cat']);
    $p = Product::factory()->create(['slug' => 'sm-prod']);
    $p->categories()->attach($cat);
    $a = Article::factory()->create(['slug' => 'sm-art', 'published_at' => now()->subDay()]);
    $page = Page::factory()->create(['slug' => 'about']);

    $body = $this->get('/sitemap.xml')->getContent();

    expect($body)->toContain('/catalog/sm-cat')
        ->and($body)->toContain('/catalog/sm-cat/sm-prod')
        ->and($body)->toContain('/blog/sm-art')
        ->and($body)->toContain('/about');
});

it('omits unpublished / draft / future content', function () {
    Category::factory()->unpublished()->create(['slug' => 'draft-cat']);
    Product::factory()->unpublished()->create(['slug' => 'draft-prod']);
    Article::factory()->create(['slug' => 'future-art', 'published_at' => now()->addDays(3)]);

    $body = $this->get('/sitemap.xml')->getContent();

    expect($body)->not->toContain('draft-cat')
        ->and($body)->not->toContain('draft-prod')
        ->and($body)->not->toContain('future-art');
});

it('does not duplicate /contacts (the Page entry is skipped when its slug=contacts)', function () {
    Page::factory()->create(['slug' => 'contacts', 'title' => 'Contacts Page']);

    $body = $this->get('/sitemap.xml')->getContent();
    // Should appear exactly once via the static contacts entry
    expect(substr_count($body, '<loc>'.url('/contacts').'</loc>'))->toBe(1);
});
