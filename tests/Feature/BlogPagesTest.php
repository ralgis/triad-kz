<?php

declare(strict_types=1);

use App\Models\Article;

it('renders blog index with published articles, newest first', function () {
    $older = Article::factory()->create(['title' => 'A1', 'published_at' => now()->subDays(5)]);
    $newer = Article::factory()->create(['title' => 'A2', 'published_at' => now()->subDay()]);
    Article::factory()->create(['title' => 'Draft A3', 'published_at' => null]);

    $r = $this->get('/blog');

    $r->assertStatus(200);
    $r->assertSee('Статьи', false);
    $r->assertSeeInOrder(['A2', 'A1'], false);
    $r->assertDontSee('Draft A3', false);
});

it('renders empty blog index gracefully', function () {
    $r = $this->get('/blog');
    $r->assertStatus(200);
    $r->assertSee('Статей пока нет', false);
});

it('renders single article page', function () {
    $a = Article::factory()->create([
        'title' => 'Как выбрать ЖБИ',
        'slug' => 'kak-vybrat-zhbi',
        'content' => '<p>Тут много полезного.</p>',
        'published_at' => now()->subDay(),
    ]);

    $r = $this->get('/blog/'.$a->slug);
    $r->assertStatus(200);
    $r->assertSee('Как выбрать ЖБИ', false);
    $r->assertSee('Тут много полезного', false);
});

it('404s an article scheduled for the future', function () {
    Article::factory()->create([
        'slug' => 'future-post',
        'published_at' => now()->addDays(7),
    ]);

    $this->get('/blog/future-post')->assertStatus(404);
});

it('404s an unpublished article', function () {
    Article::factory()->create(['slug' => 'unpub', 'published_at' => null]);

    $this->get('/blog/unpub')->assertStatus(404);
});
