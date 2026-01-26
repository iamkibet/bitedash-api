<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Get all orders for a user based on their role.
     */
    public function getAllForUser(User $user): LengthAwarePaginator
    {
        $query = Order::with(['customer', 'restaurant', 'rider', 'orderItems.menuItem', 'payments']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isRestaurant()) {
            $query->whereHas('restaurant', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        } elseif ($user->isRider()) {
            // Riders see orders assigned to them (regardless of status)
            $query->where('rider_id', $user->id);
        }
        // Admins see all orders

        return $query->latest()->paginate(15);
    }

    /**
     * Get orders in "preparing" status (for riders to accept).
     * Only returns orders that are not yet assigned to any rider.
     */
    public function getAvailableForRiders(): Collection
    {
        return Order::with(['customer', 'restaurant', 'orderItems.menuItem', 'payments'])
            ->where('status', Order::STATUS_PREPARING)
            ->whereNull('rider_id')
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->latest()
            ->get();
    }

    /**
     * Get orders assigned to a specific rider.
     */
    public function getOrdersForRider(User $rider): LengthAwarePaginator
    {
        if (!$rider->isRider()) {
            throw ValidationException::withMessages([
                'rider' => ['User is not a rider.'],
            ]);
        }

        return Order::with(['customer', 'restaurant', 'orderItems.menuItem', 'payments'])
            ->where('rider_id', $rider->id)
            ->latest()
            ->paginate(15);
    }

    /**
     * Get pending orders for a restaurant.
     */
    public function getPendingForRestaurant(Restaurant $restaurant): Collection
    {
        return Order::with(['customer', 'orderItems.menuItem'])
            ->where('restaurant_id', $restaurant->id)
            ->where('status', Order::STATUS_PENDING)
            ->latest()
            ->get();
    }

    /**
     * Get all orders for a restaurant (all statuses, including paid and delivered).
     */
    public function getAllForRestaurant(Restaurant $restaurant): LengthAwarePaginator
    {
        return Order::with(['customer', 'rider', 'orderItems.menuItem', 'payments'])
            ->where('restaurant_id', $restaurant->id)
            ->latest()
            ->paginate(15);
    }

    /**
     * Get all orders for a restaurant owner's restaurant.
     * Returns all orders regardless of status (pending, paid, preparing, delivered, etc.)
     */
    public function getAllForRestaurantOwner(User $owner): LengthAwarePaginator
    {
        return Order::with(['customer', 'rider', 'orderItems.menuItem', 'payments'])
            ->whereHas('restaurant', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })
            ->latest()
            ->paginate(15);
    }

    /**
     * Get a single order by ID.
     */
    public function getById(int $id): ?Order
    {
        return Order::with(['customer', 'restaurant', 'rider', 'orderItems.menuItem', 'payments'])
            ->find($id);
    }

    /**
     * Create a new order with server-side price calculation.
     *
     * @param array<int, array{menu_item_id: int, quantity: int}> $items
     * @throws ValidationException
     */
    public function create(User $customer, Restaurant $restaurant, array $items, ?string $deliveryAddress = null, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($customer, $restaurant, $items, $deliveryAddress, $notes) {
            // Validate restaurant is open
            if (!$restaurant->is_open) {
                throw ValidationException::withMessages([
                    'restaurant' => ['The restaurant is currently closed.'],
                ]);
            }

            // Validate all menu items exist and belong to the restaurant
            $menuItemIds = array_column($items, 'menu_item_id');
            $menuItems = MenuItem::whereIn('id', $menuItemIds)
                ->where('restaurant_id', $restaurant->id)
                ->where('is_available', true)
                ->get()
                ->keyBy('id');

            if ($menuItems->count() !== count($menuItemIds)) {
                throw ValidationException::withMessages([
                    'items' => ['One or more menu items are invalid or unavailable.'],
                ]);
            }

            // Calculate total amount server-side (never trust client)
            $totalAmount = 0;
            $orderItems = [];

            foreach ($items as $item) {
                $menuItem = $menuItems->get($item['menu_item_id']);
                $quantity = (int) $item['quantity'];

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => ['Quantity must be greater than 0.'],
                    ]);
                }

                // Use current price from database
                $unitPrice = (float) $menuItem->price;
                $subtotal = $unitPrice * $quantity;
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ];
            }

            if ($totalAmount <= 0) {
                throw ValidationException::withMessages([
                    'items' => ['Order total must be greater than 0.'],
                ]);
            }

            // Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurant->id,
                'total_amount' => $totalAmount,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'delivery_address' => $deliveryAddress,
                'notes' => $notes,
            ]);

            // Create order items
            foreach ($orderItems as $orderItemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $orderItemData['menu_item_id'],
                    'quantity' => $orderItemData['quantity'],
                    'unit_price' => $orderItemData['unit_price'],
                ]);
            }

            return $order->load(['customer', 'restaurant', 'orderItems.menuItem']);
        });
    }

    /**
     * Update order status with validation.
     *
     * @throws ValidationException
     */
    public function updateStatus(Order $order, string $newStatus): Order
    {
        if (!$order->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid status transition. Cannot change from ' . $order->status . ' to ' . $newStatus . '.'],
            ]);
        }

        $order->update(['status' => $newStatus]);

        return $order->fresh(['customer', 'restaurant', 'rider', 'orderItems.menuItem']);
    }

    /**
     * Assign a rider to an order.
     *
     * @throws ValidationException
     */
    public function assignRider(Order $order, User $rider): Order
    {
        if (!$rider->isRider()) {
            throw ValidationException::withMessages([
                'rider' => ['User is not a rider.'],
            ]);
        }

        if ($order->status !== Order::STATUS_PREPARING) {
            throw ValidationException::withMessages([
                'order' => ['Order must be in preparing status to assign a rider.'],
            ]);
        }

        if ($order->rider_id !== null) {
            throw ValidationException::withMessages([
                'order' => ['Order is already assigned to a rider.'],
            ]);
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
            throw ValidationException::withMessages([
                'order' => ['Order must be paid before assigning a rider.'],
            ]);
        }

        $order->update(['rider_id' => $rider->id]);

        return $order->fresh(['customer', 'restaurant', 'rider', 'orderItems.menuItem']);
    }

    /**
     * Cancel an order.
     *
     * @throws ValidationException
     */
    public function cancel(Order $order, ?string $reason = null): Order
    {
        if ($order->isFinalState()) {
            throw ValidationException::withMessages([
                'order' => ['Cannot cancel an order that is already delivered or cancelled.'],
            ]);
        }

        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'notes' => $order->notes . ($reason ? "\nCancellation reason: " . $reason : ''),
        ]);

        return $order->fresh(['customer', 'restaurant', 'rider', 'orderItems.menuItem']);
    }

    /**
     * Delete an order (soft delete).
     */
    public function delete(Order $order): bool
    {
        return $order->delete();
    }
}
