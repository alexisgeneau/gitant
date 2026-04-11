<?php

namespace App\Jobs;

use App\Actions\PayoutAction;
use App\Models\Bounty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAutoValidation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Auto-validate bounties where the funder has not responded within 14 days.
     * Triggers payout to the hunter.
     * Runs hourly via scheduler.
     */
    public function handle(PayoutAction $payout): void
    {
        $due = Bounty::query()
            ->where('status', 'in_review')
            ->where('auto_validate_at', '<', now())
            ->with('claimer')
            ->get();

        foreach ($due as $bounty) {
            try {
                $payout->handle($bounty);
                Log::info('Bounty auto-validated and payout triggered.', ['bounty_id' => $bounty->id]);
            } catch (\Exception $e) {
                Log::error('Auto-validation failed.', [
                    'bounty_id' => $bounty->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        Log::info('CheckAutoValidation completed.', ['auto_validated' => $due->count()]);
    }
}
