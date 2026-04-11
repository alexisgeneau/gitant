<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class ApproveBountyAction
{
    public function __construct(private readonly PayoutAction $payoutAction) {}

    /**
     * Funder approves the hunter's work and triggers payout.
     */
    public function handle(Bounty $bounty, User $funder): Bounty
    {
        if ($bounty->status !== 'in_review') {
            throw new LogicException('This bounty is not in review.');
        }

        if (! $bounty->paidContributions()->where('user_id', $funder->id)->exists()) {
            throw new LogicException('You are not a funder of this bounty.');
        }

        DB::transaction(function () use ($bounty) {
            $this->payoutAction->handle($bounty);
        });

        return $bounty->fresh();
    }
}
