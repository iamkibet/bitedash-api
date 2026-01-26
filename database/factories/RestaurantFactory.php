<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
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
        $kenyanLocations = [
            'Westlands, Nairobi',
            'Kilimani, Nairobi',
            'Parklands, Nairobi',
            'Lavington, Nairobi',
            'Karen, Nairobi',
            'Mombasa Road, Nairobi',
            'Thika Road, Nairobi',
            'Ngong Road, Nairobi',
        ];

        $location = fake()->randomElement($kenyanLocations);

        return [
            'name' => fake()->company() . ' Restaurant',
            'description' => fake()->paragraph(),
            'location' => $location,
            'latitude' => fake()->latitude(-1.3, -1.2), // Nairobi latitude range
            'longitude' => fake()->longitude(36.7, 36.9), // Nairobi longitude range
            'is_open' => fake()->boolean(80), // 80% chance of being open
            'owner_id' => \App\Models\User::factory()->restaurant(),
        ];
    }

    /**
     * Indicate that the restaurant is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => true,
        ]);
    }

    /**
     * Indicate that the restaurant is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open' => false,
        ]);
    }
}
