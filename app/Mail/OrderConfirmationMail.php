<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Confirmation email to the customer right after checkout.
 * For bank-transfer orders, attaches the PDF invoice.
 */
final class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ваш заказ № '.$this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.confirmation',
            with: ['order' => $this->order],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $path = $this->order->invoice_pdf_path;
        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $path)
                ->as('Счёт '.$this->order->order_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
