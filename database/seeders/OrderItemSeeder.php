<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();

        if ($orders->isEmpty()) {
            $this->command->warn('No orders found. Please run OrderSeeder first.');
            return;
        }

        foreach ($orders as $order) {
            // Get menu items from the order's restaurant
            $menuItems = MenuItem::where('restaurant_id', $order->restaurant_id)
                ->where('is_available', true)
                ->get();

            if ($menuItems->isEmpty()) {
                // If no available items, get any items from the restaurant
                $menuItems = MenuItem::where('restaurant_id', $order->restaurant_id)->get();
            }

            if ($menuItems->isEmpty()) {
                continue;
            }

            // Create 2-5 order items per order
            $itemCount = min(rand(2, 5), $menuItems->count());
            if ($itemCount === 0) {
                continue;
            }
            $selectedItems = $menuItems->random($itemCount);

            $totalAmount = 0;

            foreach ($selectedItems as $menuItem) {
                $quantity = rand(1, 3);
                $unitPrice = (float) $menuItem->price;
                $subtotal = $quantity * $unitPrice;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            // Update order total
            $order->update([
                'total_amount' => $totalAmount,
            ]);
        }
    }
}
