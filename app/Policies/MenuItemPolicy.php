<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view menu items
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MenuItem $menuItem): bool
    {
        // All authenticated users can view menu items
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?int $restaurantId = null): bool
    {
        // Only restaurant owners can create menu items
        if (!$user->isRestaurant()) {
            return false;
        }

        // If restaurant_id is provided, check ownership
        if ($restaurantId !== null) {
            $restaurant = \App\Models\Restaurant::find($restaurantId);
            return $restaurant && $restaurant->owner_id === $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MenuItem $menuItem): bool
    {
        // Only the restaurant owner or admin can update
        return $user->id === $menuItem->restaurant->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MenuItem $menuItem): bool
    {
        // Only the restaurant owner or admin can delete
        return $user->id === $menuItem->restaurant->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MenuItem $menuItem): bool
    {
        // Only the restaurant owner or admin can restore
        return $user->id === $menuItem->restaurant->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MenuItem $menuItem): bool
    {
        // Only admin can force delete
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can toggle the menu item's availability.
     */
    public function toggleAvailability(User $user, MenuItem $menuItem): bool
    {
        // Only the restaurant owner can toggle availability
        return $user->id === $menuItem->restaurant->owner_id;
    }
}
