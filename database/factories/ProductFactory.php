<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 1000);
        $salePrice = fake()->optional(0.3)->randomFloat(2, 5, $price - 1); // 30% chance of sale
        $costPrice = fake()->randomFloat(2, 5, $price * 0.6);
        
        return [
            'vendor_id' => User::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->words(rand(2, 5), true),
            'slug' => fake()->unique()->slug(),
            'sku' => fake()->unique()->regexify('[A-Z]{3}[0-9]{6}'),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(15),
            'price' => $price,
            'sale_price' => $salePrice,
            'cost_price' => $costPrice,
            'stock_quantity' => fake()->numberBetween(0, 500),
            'low_stock_threshold' => 10,
            'track_inventory' => fake()->boolean(80), // 80% track inventory
            'is_active' => fake()->boolean(90), // 90% active
            'is_featured' => fake()->boolean(20), // 20% featured
            'images' => [
                fake()->imageUrl(640, 480, 'products'),
                fake()->imageUrl(640, 480, 'products'),
            ],
            'attributes' => [
                'color' => fake()->optional()->colorName(),
                'size' => fake()->optional()->randomElement(['S', 'M', 'L', 'XL']),
                'material' => fake()->optional()->word(),
            ],
            'dimensions' => [
                'length' => fake()->randomFloat(2, 1, 100),
                'width' => fake()->randomFloat(2, 1, 100),
                'height' => fake()->randomFloat(2, 1, 100),
                'unit' => 'cm',
            ],
            'weight' => fake()->randomFloat(2, 0.1, 50),
            'meta_data' => [
                'brand' => fake()->optional()->company(),
                'warranty' => fake()->optional()->randomElement(['1 year', '2 years', '5 years']),
            ],
            'views_count' => fake()->numberBetween(0, 10000),
            'rating' => fake()->randomFloat(1, 0, 5),
            'reviews_count' => fake()->numberBetween(0, 500),
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
            'track_inventory' => true,
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => fake()->numberBetween(1, 9),
            'low_stock_threshold' => 10,
            'track_inventory' => true,
        ]);
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? fake()->randomFloat(2, 10, 1000);
            return [
                'sale_price' => fake()->randomFloat(2, 5, $price * 0.8),
            ];
        });
    }
}
