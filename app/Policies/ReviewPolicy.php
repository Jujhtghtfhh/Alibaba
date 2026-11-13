<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only the review's author or an admin can change it.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->user_id === $user->id;
    }

    /**
     * Only the review's author or an admin can delete it.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $review->user_id === $user->id;
    }
}
