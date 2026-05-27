<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MenuPosition;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'label' => fake()->word(),
            'url' => fake()->url(),
            'position' => MenuPosition::Header,
            'order' => fake()->numberBetween(0, 100),
            'open_in_new_tab' => false,
        ];
    }

    public function inFooter(): static
    {
        return $this->state(['position' => MenuPosition::Footer]);
    }
}
