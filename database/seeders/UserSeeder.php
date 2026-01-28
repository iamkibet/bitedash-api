<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@bitedash.com',
            'phone' => '+254700000000',
            'role' => 'admin',
            'password' => 'password',
        ]);

        // Customer Users
        $customers = [
            [
                'name' => 'John Kamau',
                'email' => 'customer1@bitedash.com',
                'phone' => '+254712345678',
                'role' => 'customer',
            ],
            [
                'name' => 'Mary Wanjiku',
                'email' => 'customer2@bitedash.com',
                'phone' => '+254723456789',
                'role' => 'customer',
            ],
            [
                'name' => 'Peter Ochieng',
                'email' => 'customer3@bitedash.com',
                'phone' => '+254734567890',
                'role' => 'customer',
            ],
            [
                'name' => 'Grace Akinyi',
                'email' => 'customer4@bitedash.com',
                'phone' => '+254745678901',
                'role' => 'customer',
            ],
        ];

        foreach ($customers as $customer) {
            User::create([
                ...$customer,
                'password' => 'password',
            ]);
        }

        // Restaurant Owner Users
        $restaurantOwners = [
            [
                'name' => 'James Mwangi',
                'email' => 'restaurant@gmail.com',
                'phone' => '+254756789012',
                'role' => 'restaurant',
            ],
            [
                'name' => 'Sarah Njeri',
                'email' => 'restaurant2@bitedash.com',
                'phone' => '+254767890123',
                'role' => 'restaurant',
            ],
            [
                'name' => 'David Kipchoge',
                'email' => 'restaurant3@bitedash.com',
                'phone' => '+254778901234',
                'role' => 'restaurant',
            ],
            [
                'name' => 'Lucy Wambui',
                'email' => 'restaurant4@bitedash.com',
                'phone' => '+254789012345',
                'role' => 'restaurant',
            ],
        ];

        foreach ($restaurantOwners as $owner) {
            User::create([
                ...$owner,
                'password' => 'password',
            ]);
        }

        // Rider Users
        $riders = [
            [
                'name' => 'Michael Otieno',
                'email' => 'rider@bitedash.com',
                'phone' => '+254790123456',
                'role' => 'rider',
            ],
            [
                'name' => 'Daniel Mutua',
                'email' => 'rider2@bitedash.com',
                'phone' => '+254701234567',
                'role' => 'rider',
            ],
            [
                'name' => 'Brian Kariuki',
                'email' => 'rider3@bitedash.com',
                'phone' => '+254710987654',
                'role' => 'rider',
            ],
        ];

        foreach ($riders as $rider) {
            User::create([
                ...$rider,
                'password' => 'password',
            ]);
        }
    }
}
