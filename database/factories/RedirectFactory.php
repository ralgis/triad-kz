<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Redirect>
 */
class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition(): array
    {
        return [
            'from' => '/'.fake()->unique()->slug().'/',
            'to' => '/'.fake()->slug().'/',
            'status' => 301,
            'hit_count' => 0,
        ];
    }

    public function permanent(): static
    {
        return $this->state(['status' => 301]);
    }

    public function temporary(): static
    {
        return $this->state(['status' => 302]);
    }
}
