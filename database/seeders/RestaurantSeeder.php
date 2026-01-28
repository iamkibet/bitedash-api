<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurantOwners = User::where('role', 'restaurant')->get();

        $restaurants = [
            [
                'name' => 'Nairobi Kitchen',
                'description' => 'Authentic Kenyan cuisine with traditional recipes passed down through generations. Specializing in nyama choma, ugali, and sukuma wiki.',
                'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=600&fit=crop',
                'location' => 'Westlands, Nairobi',
                'latitude' => -1.2620,
                'longitude' => 36.8019,
                'is_open' => true,
            ],
            [
                'name' => "Mama's Place",
                'description' => 'Home-style cooking with a warm, family atmosphere. Famous for our githeri, mukimo, and fresh chapati.',
                'image_url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=600&fit=crop',
                'location' => 'Kisumu',
                'latitude' => -0.0917,
                'longitude' => 34.7680,
                'is_open' => true,
            ],
            [
                'name' => 'Kilimanjaro Grill',
                'description' => 'Premium BBQ and grilled meats. Experience the best nyama choma in town with our signature marinades.',
                'image_url' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800&h=600&fit=crop',
                'location' => 'Nairobi CBD',
                'latitude' => -1.2921,
                'longitude' => 36.8219,
                'is_open' => true,
            ],
            [
                'name' => 'Coast Delights',
                'description' => 'Swahili and coastal cuisine featuring fresh seafood, pilau, biryani, and coconut-infused dishes.',
                'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop',
                'location' => 'Mombasa',
                'latitude' => -4.0435,
                'longitude' => 39.6682,
                'is_open' => true,
            ],
            [
                'name' => 'Safari Bites',
                'description' => 'Fast food and snacks perfect for on-the-go. Serving burgers, fries, samosas, and mandazi.',
                'image_url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=600&fit=crop',
                'location' => 'Nakuru',
                'latitude' => -0.3031,
                'longitude' => 36.0800,
                'is_open' => true,
            ],
            [
                'name' => 'Nyama Choma Express',
                'description' => 'Specializing in grilled meat dishes. Quick service, great prices, and authentic Kenyan flavors.',
                'image_url' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800&h=600&fit=crop',
                'location' => 'Nairobi',
                'latitude' => -1.2864,
                'longitude' => 36.8172,
                'is_open' => false, // Closed for testing
            ],
            [
                'name' => 'Ugali & Stew House',
                'description' => 'Traditional meals with hearty stews, matoke, and fresh vegetables. A taste of home.',
                'image_url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=600&fit=crop',
                'location' => 'Eldoret',
                'latitude' => 0.5143,
                'longitude' => 35.2698,
                'is_open' => true,
            ],
            [
                'name' => 'Tilapia Paradise',
                'description' => 'Fresh fish from Lake Victoria. Specializing in tilapia, fish curry, and traditional accompaniments.',
                'image_url' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=600&fit=crop',
                'location' => 'Kisumu',
                'latitude' => -0.0917,
                'longitude' => 34.7680,
                'is_open' => true,
            ],
        ];

        foreach ($restaurants as $index => $restaurant) {
            $owner = $restaurantOwners[$index % $restaurantOwners->count()];
            
            Restaurant::create([
                ...$restaurant,
                'owner_id' => $owner->id,
            ]);
        }
    }
}
