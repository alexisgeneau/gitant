<?php

namespace App\Actions;

use App\Models\Bounty;
use Illuminate\Support\Facades\DB;

class MarkBountyInReviewAction
{
    /**
     * Mark a bounty as in_review after a PR is linked.
     *
     * Sets auto_validate_at to now + 14 days.
     */
    public function handle(
        Bounty $bounty,
        string $prUrl,
        string $prPlatform,
        int $prNumber,
    ): Bounty {
        return DB::transaction(function () use ($bounty, $prUrl, $prPlatform, $prNumber) {
            $bounty->update([
                'status'           => 'in_review',
                'linked_pr_url'    => $prUrl,
                'linked_pr_platform' => $prPlatform,
                'linked_pr_number' => $prNumber,
                'pr_submitted_at'  => now(),
                'auto_validate_at' => now()->addDays(14),
            ]);

            return $bounty->fresh();
        });
    }
}
