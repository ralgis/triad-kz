<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $price = fake()->numberBetween(1000, 50000);
        $qty = fake()->numberBetween(1, 20);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'product_sku' => 'SKU-'.fake()->unique()->numberBetween(1000, 9999),
            'unit_price' => $price,
            'unit' => 'шт',
            'qty' => $qty,
            'line_total' => $price * $qty,
        ];
    }

    /**
     * Snapshot fields filled from an existing Product — typical real-world use.
     */
    public function forProduct(Product $product, int $qty = 1): static
    {
        return $this->state([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $product->price ?? 0,
            'unit' => $product->unit_for_order,
            'qty' => $qty,
            'line_total' => ($product->price ?? 0) * $qty,
        ]);
    }
}
