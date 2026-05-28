<?php

declare(strict_types=1);

it('renders the homepage with the public layout', function () {
    $r = $this->get('/');
    $r->assertStatus(200);
    $r->assertSee('ТРИ АД Construction', false);
    $r->assertSee('Каталог', false);
    $r->assertSee('Перейти к контенту', false); // skip-link a11y
});

it('emits X-Robots-Tag noindex in non-prod', function () {
    $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('emits a noindex meta tag in non-prod', function () {
    $this->get('/')->assertSee('name="robots" content="noindex, nofollow"', false);
});

it('serves robots.txt with Disallow / on dev', function () {
    $r = $this->get('/robots.txt');
    $r->assertStatus(200);
    $r->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($r->getContent())->toContain('User-agent: *')
        ->and($r->getContent())->toContain('Disallow: /');
});

it('serves robots.txt with allow + Sitemap on production', function () {
    config(['app.env' => 'production']);
    app()->detectEnvironment(fn () => 'production');

    $r = $this->get('/robots.txt');
    $r->assertStatus(200);
    expect($r->getContent())->toContain('User-agent: *')
        ->and($r->getContent())->toContain('Sitemap:')
        ->and($r->getContent())->not->toContain('Disallow: /');
});

it('includes the canonical URL meta tag', function () {
    $this->get('/')->assertSee('rel="canonical"', false);
});
