<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $riders = User::where('role', 'rider')->get();
        $restaurants = Restaurant::all();

        if ($customers->isEmpty() || $restaurants->isEmpty()) {
            $this->command->warn('No customers or restaurants found. Please run UserSeeder and RestaurantSeeder first.');
            return;
        }

        $orders = [
            // Recent delivered orders
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => $riders->random(),
                'status' => Order::STATUS_DELIVERED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '123 Main Street, Westlands, Nairobi',
                'notes' => 'Please leave at the gate',
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1)->addHours(2),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => $riders->random(),
                'status' => Order::STATUS_DELIVERED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '456 Park Road, Kisumu',
                'notes' => null,
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(2)->addHours(1),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => $riders->random(),
                'status' => Order::STATUS_DELIVERED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '789 Mombasa Road, Nairobi',
                'notes' => 'Call when you arrive',
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3)->addHours(1),
            ],

            // Orders on the way
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => $riders->random(),
                'status' => Order::STATUS_ON_THE_WAY,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '321 University Way, Nairobi',
                'notes' => null,
                'created_at' => Carbon::now()->subHours(1),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => $riders->random(),
                'status' => Order::STATUS_ON_THE_WAY,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '555 Coast Road, Mombasa',
                'notes' => 'Gate code: 1234',
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subMinutes(45),
            ],

            // Preparing orders
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => null,
                'status' => Order::STATUS_PREPARING,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '100 Business Park, Westlands, Nairobi',
                'notes' => null,
                'created_at' => Carbon::now()->subMinutes(30),
                'updated_at' => Carbon::now()->subMinutes(20),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => null,
                'status' => Order::STATUS_PREPARING,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '200 Market Street, Nakuru',
                'notes' => 'Extra spicy please',
                'created_at' => Carbon::now()->subMinutes(45),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],

            // Pending orders
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => null,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'delivery_address' => '300 Residential Area, Eldoret',
                'notes' => null,
                'created_at' => Carbon::now()->subMinutes(10),
                'updated_at' => Carbon::now()->subMinutes(10),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => null,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '400 School Lane, Kisumu',
                'notes' => 'No onions please',
                'created_at' => Carbon::now()->subMinutes(5),
                'updated_at' => Carbon::now()->subMinutes(5),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => null,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'delivery_address' => '500 Hospital Road, Nairobi',
                'notes' => null,
                'created_at' => Carbon::now()->subMinutes(15),
                'updated_at' => Carbon::now()->subMinutes(15),
            ],

            // Cancelled orders
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => null,
                'status' => Order::STATUS_CANCELLED,
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'delivery_address' => '600 Canceled Street, Nairobi',
                'notes' => null,
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5)->addMinutes(30),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => null,
                'status' => Order::STATUS_CANCELLED,
                'payment_status' => Order::PAYMENT_STATUS_FAILED,
                'delivery_address' => '700 Failed Payment Ave, Mombasa',
                'notes' => 'Payment failed',
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subDays(4)->addHours(1),
            ],

            // Older delivered orders
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => $riders->random(),
                'status' => Order::STATUS_DELIVERED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '800 Old Street, Nakuru',
                'notes' => null,
                'created_at' => Carbon::now()->subDays(7),
                'updated_at' => Carbon::now()->subDays(7)->addHours(2),
            ],
            [
                'customer' => $customers->random(),
                'restaurant' => $restaurants->random(),
                'rider' => $riders->random(),
                'status' => Order::STATUS_DELIVERED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'delivery_address' => '900 History Road, Eldoret',
                'notes' => 'Great service!',
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(10)->addHours(1),
            ],
        ];

        foreach ($orders as $orderData) {
            // Create order with initial total (will be updated by OrderItemSeeder)
            Order::create([
                'customer_id' => $orderData['customer']->id,
                'restaurant_id' => $orderData['restaurant']->id,
                'rider_id' => $orderData['rider']?->id,
                'total_amount' => 0, // Will be calculated from order items
                'status' => $orderData['status'],
                'payment_status' => $orderData['payment_status'],
                'delivery_address' => $orderData['delivery_address'],
                'notes' => $orderData['notes'],
                'created_at' => $orderData['created_at'],
                'updated_at' => $orderData['updated_at'],
            ]);
        }
    }
}
