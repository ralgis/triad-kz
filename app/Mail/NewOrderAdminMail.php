<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notification to the admin (Setting::current()->email_recipient) about a
 * fresh order. Plain text — admin clicks through to the Filament panel.
 */
final class NewOrderAdminMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новый заказ № '.$this->order->order_number.' — '.number_format((float) $this->order->total, 0, '.', ' ').' ₸',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.new_admin',
            with: [
                'order' => $this->order,
                'adminUrl' => url('/admin/orders/'.$this->order->id),
            ],
        );
    }
}
