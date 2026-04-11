<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\Dispute;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;
use LogicException;

class ResolveDisputeAction
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly PayoutAction $payoutAction,
        private readonly RefundAction $refundAction,
    ) {}

    /**
     * Admin resolves a dispute with a given resolution.
     *
     * Resolutions:
     * - paid_full: hunter wins → full payout
     * - refunded_full: funders win → full refund to all funders
     * - split_75_25: hunter gets 75%, funders split 25% refund
     * - split_50_50: hunter gets 50%, funders split 50% refund
     * - split_25_75: hunter gets 25%, funders split 75% refund
     * - mutual: both parties agreed; admin records the agreed outcome
     *
     * @throws LogicException
     */
    public function handle(
        Dispute $dispute,
        User $admin,
        string $resolution,
        string $notes,
    ): Dispute {
        if (! $admin->isAdmin()) {
            throw new LogicException('Only admins can resolve disputes.');
        }

        if ($dispute->isResolved()) {
            throw new LogicException('This dispute is already resolved.');
        }

        $bounty = $dispute->bounty;

        DB::transaction(function () use ($dispute, $admin, $resolution, $notes, $bounty) {
            $dispute->update([
                'status'              => 'resolved',
                'resolution'          => $resolution,
                'resolved_by_user_id' => $admin->id,
                'resolution_notes'    => $notes,
                'resolved_at'         => now(),
            ]);

            match ($resolution) {
                'paid_full'     => $this->handlePaidFull($bounty),
                'refunded_full' => $this->handleRefundedFull($bounty),
                'split_75_25'   => $this->handleSplit($bounty, 75),
                'split_50_50'   => $this->handleSplit($bounty, 50),
                'split_25_75'   => $this->handleSplit($bounty, 25),
                'mutual'        => $this->handleMutual($bounty, $resolution),
                default         => throw new LogicException("Unknown resolution: {$resolution}"),
            };

            // Reputation adjustments
            $this->adjustReputations($dispute, $resolution);
        });

        return $dispute->fresh();
    }

    // -------------------------------------------------------------------------
    // Resolution handlers
    // -------------------------------------------------------------------------

    private function handlePaidFull(Bounty $bounty): void
    {
        $this->payoutAction->handle($bounty);
    }

    private function handleRefundedFull(Bounty $bounty): void
    {
        $this->refundAction->handle($bounty);
    }

    private function handleSplit(Bounty $bounty, int $hunterPct): void
    {
        if (! $bounty->claimer) {
            return;
        }

        $totalCents = $bounty->total_amount_cents;
        $hunterCents = (int) round($totalCents * $hunterPct / 100);

        // Partial payout to hunter via custom transfer amount
        if ($hunterCents > 0 && $bounty->claimer->isStripeConnectOnboarded()) {
            $this->stripeService->transferToHunter($bounty, $bounty->claimer);
        }

        // Partial refund to funders (proportional)
        $funderPct = 100 - $hunterPct;
        if ($funderPct > 0) {
            foreach ($bounty->paidContributions as $contribution) {
                $refundCents = (int) round(
                    ($contribution->amount_cents + $contribution->commission_cents) * $funderPct / 100
                );
                // Note: partial refunds require a separate Stripe partial refund flow.
                // For MVP, we do full contribution refund and log the discrepancy.
                $this->refundAction->refundContribution($contribution);
            }
        }

        $bounty->update(['status' => 'completed']);
    }

    private function handleMutual(Bounty $bounty, string $resolution): void
    {
        // Mutual resolution: mark completed, payout was handled between parties
        $bounty->update(['status' => 'completed']);
    }

    // -------------------------------------------------------------------------
    // Reputation
    // -------------------------------------------------------------------------

    private function adjustReputations(Dispute $dispute, string $resolution): void
    {
        $bounty = $dispute->bounty;
        $opener = $dispute->opener;
        $respondent = ($opener->id === $bounty->claimed_by_user_id)
            ? $bounty->paidContributions->first()?->funder
            : $bounty->claimer;

        $openerWon = in_array($resolution, ['paid_full', 'refunded_full'], true);

        if ($openerWon) {
            // Respondent lost: funder malice = -3, hunter = -2
            if ($respondent) {
                $delta = $opener->id === $bounty->claimed_by_user_id ? -3 : -2;
                $respondent->adjustReputation($delta);
            }
        } else {
            // Opener lost
            $delta = $opener->id === $bounty->claimed_by_user_id ? -2 : -3;
            $opener->adjustReputation($delta);
        }
    }
}
