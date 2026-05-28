<?php

declare(strict_types=1);

use App\Models\Redirect;

it('matches a stored Cyrillic-decoded /from against an encoded request URL', function () {
    // What BuildLegacyRedirects stores after urldecode(post_name).
    Redirect::create([
        'from' => '/product/опорная-подушка-оп3',
        'to' => '/catalog/opornye-podushki/opornaya-podushka-op3',
        'status' => 301,
    ]);

    // How an old Yandex/Google index hit looks on the wire:
    // %d0%be%d0%bf% … = «оп»
    $r = $this->get('/product/%d0%be%d0%bf%d0%be%d1%80%d0%bd%d0%b0%d1%8f-%d0%bf%d0%be%d0%b4%d1%83%d1%88%d0%ba%d0%b0-%d0%be%d0%bf3');
    $r->assertStatus(301);
    $r->assertRedirect('/catalog/opornye-podushki/opornaya-podushka-op3');
});

it('matches both encoded and decoded request paths against the same stored row', function () {
    Redirect::create([
        'from' => '/контакты',
        'to' => '/contacts',
        'status' => 301,
    ]);

    $this->get('/%d0%ba%d0%be%d0%bd%d1%82%d0%b0%d0%ba%d1%82%d1%8b')
        ->assertStatus(301)
        ->assertRedirect('/contacts');

    $this->get('/контакты')
        ->assertStatus(301)
        ->assertRedirect('/contacts');
});
