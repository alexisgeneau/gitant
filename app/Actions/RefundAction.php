<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\BountyContribution;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundAction
{
    public function __construct(private readonly StripeService $stripeService) {}

    /**
     * Refund all paid contributions for a bounty (e.g. on expiration or full dispute resolution).
     * Each funder receives their original payment back.
     */
    public function handle(Bounty $bounty): void
    {
        $paidContributions = $bounty->paidContributions;

        DB::transaction(function () use ($bounty, $paidContributions) {
            foreach ($paidContributions as $contribution) {
                $this->refundContribution($contribution);
            }

            $bounty->update(['status' => 'expired']);
        });
    }

    /**
     * Refund a single contribution (e.g. for partial/proportional refund in disputes).
     */
    public function refundContribution(BountyContribution $contribution): void
    {
        if ($contribution->status !== 'paid') {
            return;
        }

        try {
            $this->stripeService->refundContribution($contribution);

            $contribution->update(['status' => 'refunded']);

            Log::info('Contribution refunded.', [
                'contribution_id' => $contribution->id,
                'bounty_id'       => $contribution->bounty_id,
                'amount_eur'      => ($contribution->amount_cents + $contribution->commission_cents) / 100,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refund contribution.', [
                'contribution_id' => $contribution->id,
                'error'           => $e->getMessage(),
            ]);
        }
    }
}
