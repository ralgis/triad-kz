<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Redirect;

// Canonical path form: leading slash, no trailing slash. Laravel's url()
// helper strips trailing slashes anyway and the Redirect model normalizes
// stored paths to match. See app/Models/Redirect.php::normalizePath().

it('creates a 301 redirect when a Category slug changes', function () {
    $cat = Category::factory()->create(['slug' => 'old-slug']);

    $cat->update(['slug' => 'new-slug']);

    $r = Redirect::where('from', '/catalog/old-slug')->first();
    expect($r)->not->toBeNull()
        ->and($r->to)->toBe('/catalog/new-slug')
        ->and($r->status)->toBe(301);
});

it('creates a 301 redirect when an Article slug changes', function () {
    $a = Article::factory()->create(['slug' => 'old-post']);

    $a->update(['slug' => 'new-post']);

    expect(Redirect::where('from', '/blog/old-post')->first()->to)
        ->toBe('/blog/new-post');
});

it('creates a 301 redirect when a Page slug changes', function () {
    $p = Page::factory()->create(['slug' => 'old-page']);

    $p->update(['slug' => 'new-page']);

    expect(Redirect::where('from', '/old-page')->first()->to)
        ->toBe('/new-page');
});

it('upserts the redirect when a slug is renamed twice', function () {
    $cat = Category::factory()->create(['slug' => 'v1']);

    $cat->update(['slug' => 'v2']);
    $cat->update(['slug' => 'v3']);

    // First rename → from=/catalog/v1, to=/catalog/v2.
    // Second rename → updateOrCreate by from=/catalog/v2 inserts a new
    // row; the original /catalog/v1 row is left alone so the v1 → v2
    // chain still works (browser does two 301s).
    expect(Redirect::where('from', '/catalog/v1')->first()->to)->toBe('/catalog/v2')
        ->and(Redirect::where('from', '/catalog/v2')->first()->to)->toBe('/catalog/v3');
});

it('does NOT create a redirect when slug is set on first save', function () {
    Category::factory()->create(['slug' => 'fresh-cat']);

    expect(Redirect::count())->toBe(0);
});

it('does NOT create a redirect when slug stays the same on update', function () {
    $cat = Category::factory()->create(['slug' => 'stable']);

    $cat->update(['description' => 'changed but slug stays']);

    expect(Redirect::count())->toBe(0);
});
