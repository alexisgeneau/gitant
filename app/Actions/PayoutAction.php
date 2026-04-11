<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayoutAction
{
    public function __construct(private readonly StripeService $stripeService) {}

    /**
     * Transfer the bounty amount to the hunter's Stripe Connect account
     * and mark the bounty as completed.
     *
     * If the hunter's KYC is not complete, the bounty is marked as 'payout_pending'
     * so a retry job can attempt it within 30 days.
     *
     * @throws RuntimeException
     */
    public function handle(Bounty $bounty): void
    {
        if (! $bounty->claimer) {
            throw new RuntimeException("Bounty {$bounty->id} has no claimer to pay out to.");
        }

        $hunter = $bounty->claimer;

        if (! $hunter->isStripeConnectOnboarded()) {
            // KYC not complete — store intent and queue retry job
            Log::warning('Payout deferred: hunter KYC incomplete.', [
                'bounty_id' => $bounty->id,
                'hunter_id' => $hunter->id,
            ]);

            $bounty->update(['status' => 'payout_pending']);

            // TODO(GIT-8): dispatch RetryPayoutJob with 30-day TTL
            return;
        }

        DB::transaction(function () use ($bounty, $hunter) {
            $transferId = $this->stripeService->transferToHunter($bounty, $hunter);

            $bounty->update(['status' => 'completed']);

            Log::info('Bounty payout completed.', [
                'bounty_id'   => $bounty->id,
                'hunter_id'   => $hunter->id,
                'transfer_id' => $transferId,
                'amount_eur'  => $bounty->total_amount_cents / 100,
            ]);
        });
    }
}
