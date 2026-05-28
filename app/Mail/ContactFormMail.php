<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the admin when a customer submits the /contacts/ form or the
 * "Запросить цену" modal on a product card. Note product_id may be null
 * — see ContactSubmission::$product_id.
 */
final class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ContactSubmission $submission) {}

    public function envelope(): Envelope
    {
        $tag = $this->submission->product_id ? '(по товару)' : '(общая)';

        return new Envelope(
            subject: 'Заявка с сайта '.$tag.' — '.$this->submission->name,
            replyTo: $this->submission->email
                ? [$this->submission->email]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.new',
            with: ['submission' => $this->submission],
        );
    }
}
