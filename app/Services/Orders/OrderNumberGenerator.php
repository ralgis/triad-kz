<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;

/**
 * Generates the next human-readable order number in `T-NNNNNN` format
 * (T = Triad). Pulls the highest numeric suffix already used and
 * increments — done inside a transaction in OrderService::create() so
 * concurrent checkouts can't collide on the same number.
 */
final class OrderNumberGenerator
{
    private const PREFIX = 'T-';

    private const MIN_DIGITS = 6;

    public function next(): string
    {
        $latest = Order::query()
            ->where('order_number', 'like', self::PREFIX.'%')
            ->orderByDesc('id')
            ->value('order_number');

        $next = is_string($latest)
            ? ((int) substr($latest, strlen(self::PREFIX))) + 1
            : 1;

        return self::PREFIX.str_pad((string) $next, self::MIN_DIGITS, '0', STR_PAD_LEFT);
    }
}
