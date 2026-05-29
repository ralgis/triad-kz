<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $diameter = fake()->numberBetween(700, 2000);
        $height = fake()->numberBetween(300, 1500);

        return [
            'name' => 'Кольцо колодезное КС'.fake()->numberBetween(10, 25).'.'.fake()->numberBetween(3, 12),
            'sku' => 'KS-'.fake()->unique()->numberBetween(1000, 9999),
            'gost' => fake()->randomElement(['ГОСТ 8020-90', 'ГОСТ 13579-78', 'Серия 3.900.1-14']),
            'dimensions' => [
                'diameter' => $diameter,
                'height' => $height,
                'wall' => fake()->numberBetween(50, 120),
            ],
            'weight_t' => fake()->randomFloat(3, 0.2, 3.0),
            'price' => fake()->numberBetween(5000, 80000),
            'price_unit' => 'за шт',
            'price_visible' => true,
            'unit_for_order' => 'шт',
            'description' => '<p>'.fake()->paragraph().'</p>',
            'published' => true,
            'featured' => false,
            'in_stock' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['published' => false]);
    }

    public function featured(): static
    {
        return $this->state(['featured' => true]);
    }

    public function outOfStock(): static
    {
        return $this->state(['in_stock' => false]);
    }

    public function priceHidden(): static
    {
        return $this->state(['price_visible' => false, 'price' => null]);
    }
}
