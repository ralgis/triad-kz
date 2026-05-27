<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomerType;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_company_name',
        'customer_bin',
        'customer_address',
        'delivery_method',
        'delivery_address',
        'payment_method',
        'comment',
        'subtotal',
        'total',
        'status',
        'status_history',
        'invoice_pdf_path',
        'notification_sent',
    ];

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'delivery_method' => DeliveryMethod::class,
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'status_history' => 'array',
            'notification_sent' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Public URL for the "thank you" page.
     */
    public function url(): string
    {
        return url('/order/'.$this->order_number);
    }

    public function invoiceUrl(): ?string
    {
        return $this->invoice_pdf_path
            ? url('/order/'.$this->order_number.'/invoice')
            : null;
    }

    /**
     * Append a status-change entry to status_history JSON.
     * Caller is responsible for actually setting $this->status afterwards.
     *
     * @param int|null $byUserId null when status changes from a public-side
     *                           action (e.g. cancellation by customer);
     *                           admin user id when from Filament.
     */
    public function appendStatusHistory(OrderStatus $from, OrderStatus $to, ?int $byUserId = null, ?string $note = null): void
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'at' => now()->toIso8601String(),
            'by_user_id' => $byUserId,
            'from' => $from->value,
            'to' => $to->value,
            'note' => $note,
        ];
        $this->status_history = $history;
    }
}
