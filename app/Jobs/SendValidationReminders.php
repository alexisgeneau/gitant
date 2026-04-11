<?php

namespace App\Jobs;

use App\Models\Bounty;
use App\Notifications\AutoValidationReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendValidationReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Send reminder emails to funders at D+3, D+7, D+12 before auto-validation.
     * Runs daily via scheduler.
     */
    public function handle(): void
    {
        $reminderDays = [11, 7, 2]; // Days remaining until auto_validate_at

        foreach ($reminderDays as $daysRemaining) {
            $bounties = Bounty::query()
                ->where('status', 'in_review')
                ->whereNotNull('auto_validate_at')
                ->whereDate('auto_validate_at', now()->addDays($daysRemaining)->toDateString())
                ->with(['paidContributions.funder'])
                ->get();

            foreach ($bounties as $bounty) {
                $notifiedFunders = [];

                foreach ($bounty->paidContributions as $contribution) {
                    $funder = $contribution->funder;
                    if ($funder && ! in_array($funder->id, $notifiedFunders, true)) {
                        $funder->notify(new AutoValidationReminderNotification($bounty, $daysRemaining));
                        $notifiedFunders[] = $funder->id;
                    }
                }

                Log::info("Validation reminders sent ({$daysRemaining}d remaining).", ['bounty_id' => $bounty->id]);
            }
        }
    }
}
