<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single-form submission log. Two sources:
 *   - /contacts/ page form (product_id NULL)
 *   - "Quick request price" modal on a product card (product_id set)
 *
 * Order-flow uses its own Order/OrderItem tables — not this one.
 */
class ContactSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'message',
        'product_id',
        'ip',
        'user_agent',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
