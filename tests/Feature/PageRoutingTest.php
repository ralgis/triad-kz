<?php

declare(strict_types=1);

use App\Models\Page;

it('renders a Page at its slug URL', function () {
    Page::factory()->create([
        'slug' => 'about',
        'title' => 'О компании',
        'content' => '<p>История ТРИ АД.</p>',
    ]);

    $r = $this->get('/about');
    $r->assertStatus(200);
    $r->assertSee('О компании', false);
    $r->assertSee('История ТРИ АД', false);
});

it('404s when slug has no Page', function () {
    $this->get('/this-slug-does-not-exist')->assertStatus(404);
});

it('does not let catch-all override declared routes', function () {
    // /catalog has an explicit route; a Page with slug=catalog must NOT shadow it
    Page::factory()->create(['slug' => 'catalog', 'title' => 'Page Catalog']);

    $r = $this->get('/catalog');
    $r->assertStatus(200);
    // Real catalog page mentions "Каталог продукции"; the rogue Page would say "Page Catalog"
    $r->assertSee('Каталог продукции', false);
    $r->assertDontSee('Page Catalog', false);
});

it('does not match /contacts via catch-all even when a Page exists', function () {
    Page::factory()->create(['slug' => 'contacts', 'title' => 'Old Contacts Page']);

    // Should land on ContactController (which renders contacts.blade with form),
    // not on PageController.
    $r = $this->get('/contacts');
    $r->assertStatus(200);
    $r->assertSee('Оставить заявку', false);
});
