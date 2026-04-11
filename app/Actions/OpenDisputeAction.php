<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class OpenDisputeAction
{
    /**
     * Open a dispute on a bounty.
     *
     * Either the hunter or a funder can open a dispute when the bounty is 'disputed'.
     * The response deadline is set to 5 business days (≈7 calendar days).
     *
     * @throws LogicException
     */
    public function handle(
        Bounty $bounty,
        User $opener,
        string $type,
        string $summary,
        string $demand,
        array $evidence = [],
    ): Dispute {
        if ($bounty->status !== 'disputed') {
            throw new LogicException('A dispute can only be opened on a bounty in disputed status.');
        }

        if ($bounty->activeDispute) {
            throw new LogicException('This bounty already has an active dispute.');
        }

        // Validate evidence cap
        if (count($evidence) > 5) {
            throw new LogicException('You can submit at most 5 evidence links.');
        }

        return DB::transaction(function () use ($bounty, $opener, $type, $summary, $demand, $evidence) {
            $dispute = Dispute::create([
                'bounty_id'            => $bounty->id,
                'opened_by_user_id'    => $opener->id,
                'type'                 => $type,
                'opener_summary'       => $summary,
                'opener_evidence'      => $evidence,
                'opener_demand'        => $demand,
                'status'               => 'awaiting_response',
                'response_deadline_at' => now()->addDays(7), // ~5 business days
            ]);

            return $dispute;
        });
    }
}
