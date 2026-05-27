<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Бетонные кольца',
            'Плиты перекрытия',
            'Плиты днища',
            'Фундаментные блоки',
            'Опорные подушки',
            'Арычные лотки',
            'Плиты лотков теплотрасс',
            'Сетка сварная',
        ]).' '.fake()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(0, 100),
            'published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['published' => false]);
    }
}
