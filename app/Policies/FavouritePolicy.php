<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Favourite;
use App\Models\User;

class FavouritePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Customers can view their own favourites
        return $user->isCustomer();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Favourite $favourite): bool
    {
        // Users can only view their own favourites
        return $user->id === $favourite->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only customers can create favourites
        return $user->isCustomer();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Favourite $favourite): bool
    {
        // Users can only delete their own favourites
        return $user->id === $favourite->user_id;
    }
}
