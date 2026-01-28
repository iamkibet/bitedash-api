<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::all();

        // Menu items for Nairobi Kitchen
        $nairobiKitchen = $restaurants->where('name', 'Nairobi Kitchen')->first();
        if ($nairobiKitchen) {
            $items = [
                [
                    'name' => 'Nyama Choma',
                    'description' => 'Tender grilled beef served with kachumbari (tomato and onion salad)',
                    'price' => 800.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Ugali & Sukuma Wiki',
                    'description' => 'Traditional maize meal with collard greens and your choice of stew',
                    'price' => 250.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Beef Stew',
                    'description' => 'Slow-cooked beef in rich tomato and onion gravy',
                    'price' => 550.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1558030006-450675393462?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Githeri',
                    'description' => 'Boiled beans and corn, a traditional Kenyan staple',
                    'price' => 200.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Mukimo',
                    'description' => 'Mashed potatoes mixed with vegetables and corn',
                    'price' => 300.00,
                    'is_available' => false, // Unavailable
                    'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $nairobiKitchen->id,
                ]);
            }
        }

        // Menu items for Mama's Place
        $mamasPlace = $restaurants->where('name', "Mama's Place")->first();
        if ($mamasPlace) {
            $items = [
                [
                    'name' => 'Chapati & Beans',
                    'description' => 'Fresh flatbread with spiced beans',
                    'price' => 300.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Githeri Special',
                    'description' => 'Beans and corn with vegetables and spices',
                    'price' => 250.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Mukimo',
                    'description' => 'Mashed potatoes with vegetables',
                    'price' => 300.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1518977822534-7049a61ee0c2?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Chicken Curry',
                    'description' => 'Spiced chicken in coconut curry sauce',
                    'price' => 600.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1563379091339-03246963d29a?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $mamasPlace->id,
                ]);
            }
        }

        // Menu items for Kilimanjaro Grill
        $kilimanjaroGrill = $restaurants->where('name', 'Kilimanjaro Grill')->first();
        if ($kilimanjaroGrill) {
            $items = [
                [
                    'name' => 'Nyama Choma (Beef)',
                    'description' => 'Premium grilled beef with kachumbari',
                    'price' => 900.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Nyama Choma (Goat)',
                    'description' => 'Tender grilled goat meat',
                    'price' => 850.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1558030006-450675393462?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Grilled Chicken',
                    'description' => 'Whole grilled chicken with spices',
                    'price' => 1200.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Ugali',
                    'description' => 'Traditional maize meal',
                    'price' => 100.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $kilimanjaroGrill->id,
                ]);
            }
        }

        // Menu items for Coast Delights
        $coastDelights = $restaurants->where('name', 'Coast Delights')->first();
        if ($coastDelights) {
            $items = [
                [
                    'name' => 'Pilau',
                    'description' => 'Spiced rice with meat and vegetables',
                    'price' => 450.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Chicken Biryani',
                    'description' => 'Fragrant spiced rice with chicken',
                    'price' => 750.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Fish Curry',
                    'description' => 'Fresh fish in coconut curry sauce',
                    'price' => 600.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Coconut Rice',
                    'description' => 'Rice cooked in coconut milk',
                    'price' => 400.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Samosa',
                    'description' => 'Crispy pastry filled with spiced meat or vegetables (3 pieces)',
                    'price' => 90.00,
                    'is_available' => false, // Unavailable
                    'image_url' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $coastDelights->id,
                ]);
            }
        }

        // Menu items for Safari Bites
        $safariBites = $restaurants->where('name', 'Safari Bites')->first();
        if ($safariBites) {
            $items = [
                [
                    'name' => 'Mandazi',
                    'description' => 'Sweet fried doughnuts (5 pieces)',
                    'price' => 250.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Samosa',
                    'description' => 'Crispy pastry filled with spiced meat (3 pieces)',
                    'price' => 90.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Chips',
                    'description' => 'Crispy French fries',
                    'price' => 200.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Chicken Burger',
                    'description' => 'Grilled chicken burger with vegetables',
                    'price' => 450.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1606755962773-d324e0a13086?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $safariBites->id,
                ]);
            }
        }

        // Menu items for Nyama Choma Express
        $nyamaChomaExpress = $restaurants->where('name', 'Nyama Choma Express')->first();
        if ($nyamaChomaExpress) {
            $items = [
                [
                    'name' => 'Nyama Choma (Beef)',
                    'description' => 'Grilled beef with kachumbari',
                    'price' => 750.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Ugali & Stew',
                    'description' => 'Maize meal with beef stew',
                    'price' => 350.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1558030006-450675393462?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $nyamaChomaExpress->id,
                ]);
            }
        }

        // Menu items for Ugali & Stew House
        $ugaliStewHouse = $restaurants->where('name', 'Ugali & Stew House')->first();
        if ($ugaliStewHouse) {
            $items = [
                [
                    'name' => 'Ugali & Beef Stew',
                    'description' => 'Traditional maize meal with beef stew',
                    'price' => 350.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1558030006-450675393462?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Matoke',
                    'description' => 'Steamed green bananas with meat',
                    'price' => 350.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Beef Stew',
                    'description' => 'Rich beef stew with vegetables',
                    'price' => 500.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1558030006-450675393462?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $ugaliStewHouse->id,
                ]);
            }
        }

        // Menu items for Tilapia Paradise
        $tilapiaParadise = $restaurants->where('name', 'Tilapia Paradise')->first();
        if ($tilapiaParadise) {
            $items = [
                [
                    'name' => 'Tilapia Fish (Whole)',
                    'description' => 'Fresh grilled tilapia from Lake Victoria',
                    'price' => 500.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Fish Curry',
                    'description' => 'Tilapia in coconut curry sauce',
                    'price' => 550.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Fried Fish',
                    'description' => 'Crispy fried tilapia with vegetables',
                    'price' => 480.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=800&h=600&fit=crop',
                ],
                [
                    'name' => 'Ugali & Fish',
                    'description' => 'Maize meal with grilled tilapia',
                    'price' => 600.00,
                    'is_available' => true,
                    'image_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=800&h=600&fit=crop',
                ],
            ];

            foreach ($items as $item) {
                MenuItem::create([
                    ...$item,
                    'restaurant_id' => $tilapiaParadise->id,
                ]);
            }
        }
    }
}
