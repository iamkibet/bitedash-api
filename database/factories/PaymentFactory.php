<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
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
        $order = \App\Models\Order::factory()->create();

        return [
            'order_id' => $order->id,
            'transaction_reference' => 'TXN' . fake()->unique()->numerify('##########'),
            'amount' => $order->total_amount,
            'provider' => 'paystack',
            'method' => 'mpesa',
            'status' => fake()->randomElement([
                \App\Models\Payment::STATUS_PENDING,
                \App\Models\Payment::STATUS_SUCCESS,
                \App\Models\Payment::STATUS_FAILED,
            ]),
            'raw_payload' => null,
            'phone_number' => '+254' . fake()->numerify('##########'),
            'failure_reason' => null,
        ];
    }

    /**
     * Indicate that the payment is successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Payment::STATUS_SUCCESS,
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Payment::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => \App\Models\Payment::STATUS_FAILED,
            'failure_reason' => fake()->sentence(),
        ]);
    }
}
