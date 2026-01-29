<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;

class RatingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Anyone can view ratings (public)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Rating $rating): bool
    {
        // Anyone can view ratings (public)
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only customers can create ratings (validation ensures they've ordered and paid)
        return $user->isCustomer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rating $rating): bool
    {
        // Users can only update their own ratings
        return $user->id === $rating->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rating $rating): bool
    {
        // Users can only delete their own ratings
        return $user->id === $rating->user_id;
    }
}
