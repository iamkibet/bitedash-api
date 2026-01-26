<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
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
        $menuItem = \App\Models\MenuItem::factory()->create();

        return [
            'order_id' => \App\Models\Order::factory(),
            'menu_item_id' => $menuItem->id,
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => $menuItem->price, // Snapshot of price at time of order
        ];
    }
}
