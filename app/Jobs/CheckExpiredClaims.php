<?php

namespace App\Jobs;

use App\Actions\ReleaseClaimAction;
use App\Models\Bounty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckExpiredClaims implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Release claims whose 7-day deadline has passed with no PR submitted.
     * Runs hourly via scheduler.
     */
    public function handle(ReleaseClaimAction $releaseClaim): void
    {
        $expired = Bounty::query()
            ->where('status', 'claimed')
            ->where('claim_expires_at', '<', now())
            ->whereNull('linked_pr_url')
            ->get();

        foreach ($expired as $bounty) {
            try {
                $releaseClaim->handle($bounty);
                Log::info('Expired claim released.', ['bounty_id' => $bounty->id]);
            } catch (\Exception $e) {
                Log::error('Failed to release expired claim.', [
                    'bounty_id' => $bounty->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info('CheckExpiredClaims completed.', ['released' => $expired->count()]);
    }
}
