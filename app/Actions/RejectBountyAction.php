<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class RejectBountyAction
{
    /**
     * Funder rejects the hunter's PR — bounty moves back to 'claimed'
     * so the hunter can submit a new/revised PR, or it transitions to 'disputed'.
     */
    public function handle(Bounty $bounty, User $funder, bool $openDispute = false): Bounty
    {
        if ($bounty->status !== 'in_review') {
            throw new LogicException('This bounty is not in review.');
        }

        if (! $bounty->paidContributions()->where('user_id', $funder->id)->exists()) {
            throw new LogicException('You are not a funder of this bounty.');
        }

        $newStatus = $openDispute ? 'disputed' : 'claimed';

        DB::transaction(function () use ($bounty, $newStatus) {
            $bounty->update([
                'status'           => $newStatus,
                'auto_validate_at' => null,
            ]);
        });

        return $bounty->fresh();
    }
}
