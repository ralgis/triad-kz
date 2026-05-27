<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContactSubmission;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactSubmission>
 */
class ContactSubmissionFactory extends Factory
{
    protected $model = ContactSubmission::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+7'.fake()->numerify('##########'),
            'email' => fake()->safeEmail(),
            'message' => fake()->paragraph(),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function forProduct(?Product $product = null): static
    {
        return $this->state([
            'product_id' => $product?->id ?? Product::factory(),
        ]);
    }
}
