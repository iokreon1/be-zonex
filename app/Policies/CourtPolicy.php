<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Court;

class CourtPolicy
{
    /**
     * Determine whether the user can view the court.
     */
    public function view(User $user, Court $court): bool
    {
        return $court->venue->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the court.
     */
    public function update(User $user, Court $court): bool
    {
        return $court->venue->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the court.
     */
    public function delete(User $user, Court $court): bool
    {
        return $court->venue->users()->where('user_id', $user->id)->exists();
    }
}
