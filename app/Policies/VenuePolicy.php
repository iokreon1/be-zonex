<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    /**
     * Determine whether the user can view the venue.
     */
    public function view(User $user, Venue $venue): bool
    {
        return $venue->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the venue.
     */
    public function update(User $user, Venue $venue): bool
    {
        return $venue->users()->where('user_id', $user->id)->exists();
    }
}
