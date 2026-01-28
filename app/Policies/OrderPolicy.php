<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view orders (filtered by their role)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        // Customers can view their own orders
        if ($user->isCustomer() && $order->customer_id === $user->id) {
            return true;
        }

        // Restaurant owners can view orders for their restaurants
        if ($user->isRestaurant() && $order->restaurant->owner_id === $user->id) {
            return true;
        }

        // Riders can view orders assigned to them
        if ($user->isRider() && $order->rider_id === $user->id) {
            return true;
        }

        // Admins can view all orders
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Customers and admins can create orders
        return $user->isCustomer() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        // Customers can only update their own pending orders (to cancel)
        if ($user->isCustomer() && $order->customer_id === $user->id) {
            return $order->status === Order::STATUS_PENDING;
        }

        // Restaurant owners can update orders for their restaurants (status transitions)
        if ($user->isRestaurant() && $order->restaurant->owner_id === $user->id) {
            return true;
        }

        // Riders can update orders assigned to them (status transitions)
        if ($user->isRider() && $order->rider_id === $user->id) {
            return true;
        }

        // Admins can update any order
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        // Only admins can delete orders
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        // Only admins can restore orders
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        // Only admins can force delete orders
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can accept an order (rider).
     */
    public function accept(User $user, Order $order): bool
    {
        // Admins can accept any order
        if ($user->isAdmin()) {
            return $order->status === Order::STATUS_PREPARING && $order->rider_id === null;
        }

        // Only riders can accept orders, and only if order is in preparing status
        return $user->isRider()
            && $order->status === Order::STATUS_PREPARING
            && $order->rider_id === null;
    }

    /**
     * Determine whether the user can cancel an order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Customers can cancel their own pending orders
        if ($user->isCustomer() && $order->customer_id === $user->id) {
            return $order->status === Order::STATUS_PENDING;
        }

        // Restaurant owners can cancel orders for their restaurants
        if ($user->isRestaurant() && $order->restaurant->owner_id === $user->id) {
            return in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PREPARING], true);
        }

        // Admins can cancel any order
        return $user->isAdmin();
    }
}
