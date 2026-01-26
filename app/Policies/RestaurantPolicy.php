<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view restaurants
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Restaurant $restaurant): bool
    {
        // All authenticated users can view any restaurant
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only restaurant role users can create restaurants
        return $user->isRestaurant();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Restaurant $restaurant): bool
    {
        // Only the owner or admin can update
        return $user->id === $restaurant->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Restaurant $restaurant): bool
    {
        // Only the owner or admin can delete
        return $user->id === $restaurant->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Restaurant $restaurant): bool
    {
        // Only the owner or admin can restore
        return $user->id === $restaurant->owner_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Restaurant $restaurant): bool
    {
        // Only admin can force delete
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can toggle the restaurant's open status.
     */
    public function toggleStatus(User $user, Restaurant $restaurant): bool
    {
        // Only the owner can toggle status
        return $user->id === $restaurant->owner_id;
    }
}
