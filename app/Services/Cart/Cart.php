<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\Product;
use Illuminate\Contracts\Session\Session;

/**
 * Session-backed shopping cart.
 *
 * - No DB writes — cart lives only in the session until checkout converts
 *   it to an Order + OrderItems.
 * - Identifies products by `product_id` (a single cart item per product).
 *   Adding the same product again accumulates `qty`.
 * - Uses bcmath strings for money to avoid float drift on totals.
 * - Honors `Product::$price_visible`. A product whose price is hidden
 *   ("Запросить цену") can still be added — `unit_price` becomes "0.00"
 *   and the checkout UI surfaces a "Цена по запросу" line. The order
 *   still goes through as a request-for-quote.
 */
final class Cart
{
    private const SESSION_KEY = 'cart.items';

    public function __construct(private readonly Session $session) {}

    /**
     * Add a product to the cart (or increase qty if already present).
     */
    public function add(Product $product, int $qty = 1): void
    {
        if ($qty < 1) {
            return;
        }

        $items = $this->loadItems();
        $existing = $items[$product->id] ?? null;

        if ($existing !== null) {
            $items[$product->id] = new CartItem(
                productId: $product->id,
                qty: $existing->qty + $qty,
                unitPrice: $existing->unitPrice,
                unit: $existing->unit,
            );
        } else {
            $items[$product->id] = new CartItem(
                productId: $product->id,
                qty: $qty,
                unitPrice: (string) ($product->price ?? '0.00'),
                unit: (string) ($product->unit_for_order ?? 'шт'),
            );
        }

        $this->persist($items);
    }

    /**
     * Remove a product from the cart entirely.
     */
    public function remove(int $productId): void
    {
        $items = $this->loadItems();
        unset($items[$productId]);
        $this->persist($items);
    }

    /**
     * Replace a product's qty. Qty=0 removes the item.
     */
    public function update(int $productId, int $qty): void
    {
        if ($qty < 1) {
            $this->remove($productId);

            return;
        }

        $items = $this->loadItems();
        if (! isset($items[$productId])) {
            return;
        }

        $existing = $items[$productId];
        $items[$productId] = new CartItem(
            productId: $existing->productId,
            qty: $qty,
            unitPrice: $existing->unitPrice,
            unit: $existing->unit,
        );

        $this->persist($items);
    }

    /**
     * @return array<int, CartItem> Keyed by product_id.
     */
    public function items(): array
    {
        return $this->loadItems();
    }

    /**
     * Total of all line totals as a bcmath string.
     */
    public function subtotal(): string
    {
        $sum = '0.00';
        foreach ($this->loadItems() as $item) {
            $sum = bcadd($sum, $item->lineTotal(), 2);
        }

        return $sum;
    }

    /**
     * Total number of items (sum of qtys), not number of distinct products.
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->loadItems() as $item) {
            $count += $item->qty;
        }

        return $count;
    }

    public function isEmpty(): bool
    {
        return $this->loadItems() === [];
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * @return array<int, CartItem>
     */
    private function loadItems(): array
    {
        /** @var array<int|string, array{product_id: int|string, qty: int|string, unit_price: string, unit: string}> $raw */
        $raw = $this->session->get(self::SESSION_KEY, []);

        $items = [];
        foreach ($raw as $row) {
            $item = CartItem::fromArray($row);
            $items[$item->productId] = $item;
        }

        return $items;
    }

    /**
     * @param array<int, CartItem> $items
     */
    private function persist(array $items): void
    {
        $this->session->put(
            self::SESSION_KEY,
            array_map(fn (CartItem $item): array => $item->toArray(), array_values($items)),
        );
    }
}
