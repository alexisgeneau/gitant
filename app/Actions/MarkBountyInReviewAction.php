<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Notifications\PRSubmittedNotification;
use Illuminate\Support\Facades\DB;

class MarkBountyInReviewAction
{
    /**
     * Mark a bounty as in_review after a PR is linked.
     *
     * Sets auto_validate_at to now + 14 days and notifies all funders.
     */
    public function handle(
        Bounty $bounty,
        string $prUrl,
        string $prPlatform,
        int $prNumber,
    ): Bounty {
        return DB::transaction(function () use ($bounty, $prUrl, $prPlatform, $prNumber) {
            $bounty->update([
                'status'             => 'in_review',
                'linked_pr_url'      => $prUrl,
                'linked_pr_platform' => $prPlatform,
                'linked_pr_number'   => $prNumber,
                'pr_submitted_at'    => now(),
                'auto_validate_at'   => now()->addDays(14),
            ]);

            $fresh = $bounty->fresh();

            // Notify all funders that a PR was submitted
            $notifiedFunders = [];
            foreach ($fresh->paidContributions as $contribution) {
                if ($contribution->funder && ! in_array($contribution->funder->id, $notifiedFunders, true)) {
                    $contribution->funder->notify(new PRSubmittedNotification($fresh));
                    $notifiedFunders[] = $contribution->funder->id;
                }
            }

            return $fresh;
        });
    }
}
