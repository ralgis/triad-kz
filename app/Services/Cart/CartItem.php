<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\Product;

/**
 * Immutable value object for a single line in the cart.
 *
 * The Cart service holds a list of these in session. We deliberately store
 * the raw scalars (product_id, qty, unit_price snapshot) rather than the
 * Product model, because:
 *   1) Session must serialize to JSON cleanly; Eloquent models drag
 *      relationships/casts and bloat the payload.
 *   2) Price snapshot — even if the admin changes the product price mid-
 *      session, the cart shows what the customer added at the time.
 *
 * We lazy-load the actual Product via product() when the template needs
 * details (name, image, link).
 */
final class CartItem
{
    public function __construct(
        public readonly int $productId,
        public readonly int $qty,
        public readonly string $unitPrice,
        public readonly string $unit,
    ) {}

    public function product(): ?Product
    {
        return Product::find($this->productId);
    }

    public function lineTotal(): string
    {
        return bcmul($this->unitPrice, (string) $this->qty, 2);
    }

    /**
     * @return array{product_id: int, qty: int, unit_price: string, unit: string}
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'qty' => $this->qty,
            'unit_price' => $this->unitPrice,
            'unit' => $this->unit,
        ];
    }

    /**
     * @param array{product_id: int|string, qty: int|string, unit_price: string, unit: string} $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            productId: (int) $row['product_id'],
            qty: (int) $row['qty'],
            unitPrice: (string) $row['unit_price'],
            unit: (string) $row['unit'],
        );
    }
}
