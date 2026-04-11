<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\User;
use App\Notifications\BountyClaimedNotification;
use Illuminate\Support\Facades\DB;
use LogicException;

class ClaimBountyAction
{
    /**
     * Claim a bounty for a hunter.
     *
     * Rules:
     * - Bounty must be in 'open' status.
     * - The hunter must not already have an active claim on this bounty.
     * - The hunter has 7 days to submit a PR after claiming.
     *
     * @throws LogicException
     */
    public function handle(Bounty $bounty, User $hunter): Bounty
    {
        if (! $bounty->isOpen()) {
            throw new LogicException('This bounty is not open for claiming.');
        }

        if ($bounty->claimed_by_user_id !== null) {
            throw new LogicException('This bounty is already claimed.');
        }

        return DB::transaction(function () use ($bounty, $hunter) {
            $now = now();

            $bounty->update([
                'status'              => 'claimed',
                'claimed_by_user_id'  => $hunter->id,
                'claimed_at'          => $now,
                'claim_expires_at'    => $now->copy()->addDays(7),
            ]);

            $fresh = $bounty->fresh();

            // Notify all funders
            foreach ($fresh->paidContributions as $contribution) {
                if ($contribution->funder) {
                    $contribution->funder->notify(new BountyClaimedNotification($fresh, $hunter));
                }
            }

            return $fresh;
        });
    }
}
