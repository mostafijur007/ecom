<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(rand(1, 3), true);
        
        return [
            'name' => ucwords($name),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->sentence(10),
            'parent_id' => null,
            'is_active' => fake()->boolean(90), // 90% active
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category is a child of another category.
     */
    public function child(Category|int $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent instanceof Category ? $parent->id : $parent,
        ]);
    }
}
