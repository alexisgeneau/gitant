<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class ReleaseClaimAction
{
    /**
     * Release a claim on a bounty.
     *
     * Used for:
     * - Voluntary abandonment (hunter releases)
     * - Auto-expiration (job releases after 7 days with no PR)
     */
    public function handle(Bounty $bounty, ?User $hunter = null): Bounty
    {
        if (! in_array($bounty->status, ['claimed'], true)) {
            throw new LogicException('This bounty does not have an active claim to release.');
        }

        if ($hunter && $bounty->claimed_by_user_id !== $hunter->id) {
            throw new LogicException('You are not the claimer of this bounty.');
        }

        return DB::transaction(function () use ($bounty) {
            $bounty->update([
                'status'             => 'open',
                'claimed_by_user_id' => null,
                'claimed_at'         => null,
                'claim_expires_at'   => null,
            ]);

            return $bounty->fresh();
        });
    }
}
