<?php

namespace App\Policies;

use App\Models\Bounty;
use App\Models\User;

class BountyPolicy
{
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create a bounty
    }

    public function contribute(User $user, Bounty $bounty): bool
    {
        return $bounty->isOpen();
    }

    public function claim(User $user, Bounty $bounty): bool
    {
        return $bounty->isOpen() && $user->id !== $bounty->claimed_by_user_id;
    }

    public function approve(User $user, Bounty $bounty): bool
    {
        // Funders who contributed can approve
        return $bounty->contributions()->where('user_id', $user->id)->exists();
    }
}
