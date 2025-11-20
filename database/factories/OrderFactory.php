<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 500);
        $tax = $subtotal * 0.1; // 10% tax
        $shippingCost = fake()->randomFloat(2, 5, 20);
        $discount = fake()->optional(0.2)->randomFloat(2, 5, 50) ?? 0; // 20% chance of discount, default 0
        $total = $subtotal + $tax + $shippingCost - $discount;
        
        return [
            'order_number' => 'ORD-' . fake()->unique()->numerify('######'),
            'customer_id' => User::factory(),
            'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'discount' => $discount,
            'total' => $total,
            'shipping_name' => fake()->name(),
            'shipping_email' => fake()->safeEmail(),
            'shipping_phone' => fake()->phoneNumber(),
            'shipping_address' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_state' => fake()->state(),
            'shipping_postal_code' => fake()->postcode(),
            'shipping_country' => fake()->country(),
            'payment_method' => fake()->randomElement(['credit_card', 'paypal', 'bank_transfer']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed', 'refunded']),
            'transaction_id' => fake()->optional()->uuid(),
            'paid_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional()->sentence(),
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
            'paid_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'payment_status' => 'paid',
            'paid_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'payment_status' => 'paid',
            'paid_at' => fake()->dateTimeBetween('-14 days', '-7 days'),
            'shipped_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'delivered_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'paid_at' => fake()->dateTimeBetween('-30 days', '-14 days'),
            'shipped_at' => fake()->dateTimeBetween('-14 days', '-7 days'),
            'delivered_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancelled_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
