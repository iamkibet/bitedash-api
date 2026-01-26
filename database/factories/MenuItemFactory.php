<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
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
        $kenyanDishes = [
            'Nyama Choma',
            'Ugali & Sukuma Wiki',
            'Chapati & Beef Stew',
            'Pilau',
            'Githeri',
            'Matoke',
            'Fish & Chips',
            'Chicken Biryani',
            'Mandazi',
            'Samosa',
            'Chips Masala',
            'Beef Burger',
            'Chicken Burger',
            'Pizza Margherita',
            'Pizza Pepperoni',
            'Fried Rice',
            'Chicken Wings',
            'Grilled Tilapia',
        ];

        return [
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'name' => fake()->randomElement($kenyanDishes),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 100, 2000), // KES 100 to 2000
            'image_url' => fake()->imageUrl(640, 480, 'food'),
            'is_available' => fake()->boolean(85), // 85% chance of being available
        ];
    }

    /**
     * Indicate that the menu item is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
        ]);
    }

    /**
     * Indicate that the menu item is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
