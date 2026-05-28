<?php

declare(strict_types=1);

use App\Models\Redirect;

it('301-redirects a 404 path that matches a Redirect row', function () {
    Redirect::create([
        'from' => '/old-page',
        'to' => '/new-page',
        'status' => 301,
    ]);

    $this->get('/old-page')
        ->assertStatus(301)
        ->assertRedirect('/new-page');
});

it('matches even when the visitor URL has a trailing slash', function () {
    Redirect::create([
        'from' => '/old-page',
        'to' => '/new-page',
        'status' => 301,
    ]);

    $this->get('/old-page/')
        ->assertStatus(301)
        ->assertRedirect('/new-page');
});

it('leaves the 404 alone when nothing matches', function () {
    Redirect::create([
        'from' => '/something-else',
        'to' => '/elsewhere',
        'status' => 301,
    ]);

    $this->get('/totally-unknown')->assertStatus(404);
});

it('increments hit_count on a matched redirect', function () {
    $r = Redirect::create([
        'from' => '/popular-old',
        'to' => '/new-target',
        'status' => 301,
    ]);

    $this->get('/popular-old');
    $this->get('/popular-old');
    $this->get('/popular-old');

    expect($r->fresh()->hit_count)->toBe(3)
        ->and($r->fresh()->last_hit_at)->not->toBeNull();
});
