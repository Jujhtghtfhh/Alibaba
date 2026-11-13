<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Only the item's author or an admin can change it.
     */
    public function update(User $user, Item $item): bool
    {
        return $user->isAdmin() || $item->user_id === $user->id;
    }

    /**
     * Only the item's author or an admin can delete it.
     */
    public function delete(User $user, Item $item): bool
    {
        return $user->isAdmin() || $item->user_id === $user->id;
    }
}
