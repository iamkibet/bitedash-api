<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kenyanAddresses = [
            'Westlands, Nairobi',
            'Kilimani, Nairobi',
            'Parklands, Nairobi',
            'Lavington, Nairobi',
            'Karen, Nairobi',
            'Mombasa Road, Nairobi',
        ];

        return [
            'customer_id' => \App\Models\User::factory()->customer(),
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'rider_id' => null,
            'total_amount' => fake()->randomFloat(2, 500, 5000), // KES 500 to 5000
            'status' => fake()->randomElement([
                \App\Models\Order::STATUS_PENDING,
                \App\Models\Order::STATUS_PREPARING,
                \App\Models\Order::STATUS_ON_THE_WAY,
                \App\Models\Order::STATUS_DELIVERED,
            ]),
            'payment_status' => fake()->randomElement([
                \App\Models\Order::PAYMENT_STATUS_UNPAID,
                \App\Models\Order::PAYMENT_STATUS_PAID,
            ]),
            'delivery_address' => fake()->randomElement($kenyanAddresses),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Order::STATUS_PENDING,
            'payment_status' => \App\Models\Order::PAYMENT_STATUS_UNPAID,
        ]);
    }

    /**
     * Indicate that the order is preparing.
     */
    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Order::STATUS_PREPARING,
            'payment_status' => \App\Models\Order::PAYMENT_STATUS_PAID,
        ]);
    }

    /**
     * Indicate that the order is on the way.
     */
    public function onTheWay(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Order::STATUS_ON_THE_WAY,
            'payment_status' => \App\Models\Order::PAYMENT_STATUS_PAID,
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Order::STATUS_DELIVERED,
            'payment_status' => \App\Models\Order::PAYMENT_STATUS_PAID,
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Order::STATUS_CANCELLED,
        ]);
    }
}
