<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RestaurantService
{
    /**
     * Get all restaurants with optional filtering.
     */
    public function getAll(?bool $isOpen = null): LengthAwarePaginator
    {
        $query = Restaurant::with(['owner', 'menuItems']);

        if ($isOpen !== null) {
            $query->where('is_open', $isOpen);
        }

        return $query->paginate(15);
    }

    /**
     * Get a single restaurant by ID.
     */
    public function getById(int $id): ?Restaurant
    {
        return Restaurant::with(['owner', 'menuItems'])->find($id);
    }

    /**
     * Get restaurant owned by a user.
     */
    public function getByOwner(User $owner): ?Restaurant
    {
        return Restaurant::with(['owner', 'menuItems'])
            ->where('owner_id', $owner->id)
            ->first();
    }

    /**
     * Calculate total revenue for a restaurant (sum of all paid orders).
     * Includes all orders regardless of status (pending, preparing, delivered, etc.)
     * as long as they are paid.
     */
    public function getTotalRevenue(Restaurant $restaurant): float
    {
        $revenue = \App\Models\Order::where('restaurant_id', $restaurant->id)
            ->where('payment_status', \App\Models\Order::PAYMENT_STATUS_PAID)
            ->sum('total_amount');
        
        return (float) ($revenue ?? 0.0);
    }

    /**
     * Get order statistics for a restaurant.
     * Includes all orders regardless of status.
     *
     * @return array<string, mixed>
     */
    public function getOrderStatistics(Restaurant $restaurant): array
    {
        $totalOrders = \App\Models\Order::where('restaurant_id', $restaurant->id)->count();
        $paidOrders = \App\Models\Order::where('restaurant_id', $restaurant->id)
            ->where('payment_status', \App\Models\Order::PAYMENT_STATUS_PAID)
            ->count();
        $pendingOrders = \App\Models\Order::where('restaurant_id', $restaurant->id)
            ->where('status', \App\Models\Order::STATUS_PENDING)
            ->count();
        $preparingOrders = \App\Models\Order::where('restaurant_id', $restaurant->id)
            ->where('status', \App\Models\Order::STATUS_PREPARING)
            ->count();
        $deliveredOrders = \App\Models\Order::where('restaurant_id', $restaurant->id)
            ->where('status', \App\Models\Order::STATUS_DELIVERED)
            ->count();
        $totalRevenue = $this->getTotalRevenue($restaurant);

        return [
            'total_orders' => $totalOrders,
            'paid_orders' => $paidOrders,
            'pending_orders' => $pendingOrders,
            'preparing_orders' => $preparingOrders,
            'delivered_orders' => $deliveredOrders,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * Create a new restaurant.
     */
    public function create(User $owner, array $data): Restaurant
    {
        return Restaurant::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_open' => $data['is_open'] ?? true,
            'owner_id' => $owner->id,
        ]);
    }

    /**
     * Update a restaurant.
     */
    public function update(Restaurant $restaurant, array $data): Restaurant
    {
        $restaurant->update([
            'name' => $data['name'] ?? $restaurant->name,
            'description' => $data['description'] ?? $restaurant->description,
            'location' => $data['location'] ?? $restaurant->location,
            'latitude' => $data['latitude'] ?? $restaurant->latitude,
            'longitude' => $data['longitude'] ?? $restaurant->longitude,
            'is_open' => $data['is_open'] ?? $restaurant->is_open,
        ]);

        return $restaurant->fresh();
    }

    /**
     * Toggle restaurant open/closed status.
     */
    public function toggleStatus(Restaurant $restaurant): Restaurant
    {
        $restaurant->update([
            'is_open' => !$restaurant->is_open,
        ]);

        return $restaurant->fresh();
    }

    /**
     * Delete a restaurant (soft delete).
     */
    public function delete(Restaurant $restaurant): bool
    {
        return $restaurant->delete();
    }
}
