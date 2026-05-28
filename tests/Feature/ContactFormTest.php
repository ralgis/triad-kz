<?php

declare(strict_types=1);

use App\Mail\ContactFormMail;
use App\Models\ContactSubmission;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('renders contacts page with the lead form', function () {
    $r = $this->get('/contacts');
    $r->assertStatus(200);
    $r->assertSee('Контакты', false);
    $r->assertSee('Оставить заявку', false);
});

it('pre-fills product context when ?product= is set', function () {
    $p = Product::factory()->create(['name' => 'КС10']);

    $r = $this->get('/contacts?product='.$p->id);
    $r->assertStatus(200);
    $r->assertSee('Запрос по товару', false);
    $r->assertSee('КС10', false);
});

it('ignores ?product= when product is unpublished or missing', function () {
    Product::factory()->unpublished()->create(['id' => 999, 'name' => 'HIDDEN']);

    $r = $this->get('/contacts?product=999');
    $r->assertStatus(200);
    $r->assertDontSee('Запрос по товару', false);
});

it('stores a valid submission and queues admin email', function () {
    $r = $this->from('/contacts')->post('/contacts', [
        'name' => 'Иван',
        'phone' => '+77011234567',
        'email' => 'a@b.kz',
        'message' => 'Нужны кольца',
    ]);

    $r->assertRedirect('/contacts');
    $r->assertSessionHas('contact.sent');

    expect(ContactSubmission::count())->toBe(1);
    $s = ContactSubmission::first();
    expect($s->name)->toBe('Иван')
        ->and($s->phone)->toBe('+77011234567');

    // ContactFormMail implements ShouldQueue, so the fake records it
    // as queued rather than sent.
    Mail::assertQueued(ContactFormMail::class);
});

it('rejects submission without phone', function () {
    $this->from('/contacts')->post('/contacts', [
        'name' => 'И',
        'email' => 'x@x.kz',
    ])->assertSessionHasErrors('phone');

    expect(ContactSubmission::count())->toBe(0);
});

it('still persists submission even when mail throws', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    $this->from('/contacts')->post('/contacts', [
        'name' => 'И',
        'phone' => '+77011234567',
    ]);

    // Submission survives the throw because controller try/catches around
    // the Mail::send. Lead is not lost when SMTP misbehaves.
    expect(ContactSubmission::count())->toBe(1);
});
