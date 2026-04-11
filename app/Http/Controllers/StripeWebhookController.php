<?php

namespace App\Http\Controllers;

use App\Models\BountyContribution;
use App\Models\ProcessedStripeEvent;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeService $stripeService) {}

    public function handle(Request $request): Response
    {
        $signature = $request->header('Stripe-Signature', '');

        try {
            $event = $this->stripeService->constructWebhookEvent(
                $request->getContent(),
                $signature,
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // Idempotence: skip already-processed events
        if (ProcessedStripeEvent::hasProcessed($event->id)) {
            return response('Already processed', 200);
        }

        match ($event->type) {
            'checkout.session.completed'   => $this->handleCheckoutCompleted($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            'account.updated'              => $this->handleAccountUpdated($event->data->object),
            'transfer.created'             => $this->handleTransferCreated($event->data->object),
            'charge.refunded'              => $this->handleChargeRefunded($event->data->object),
            default                        => null,
        };

        ProcessedStripeEvent::markProcessed($event->id, $event->type);

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------
    // checkout.session.completed → mark contribution paid, update bounty total
    // -------------------------------------------------------------------------

    private function handleCheckoutCompleted(object $session): void
    {
        $contribution = BountyContribution::where(
            'stripe_checkout_session_id',
            $session->id,
        )->first();

        if (! $contribution) {
            // Fallback: look up via metadata
            $contributionId = $session->metadata->contribution_id ?? null;
            if ($contributionId) {
                $contribution = BountyContribution::find($contributionId);
            }
        }

        if (! $contribution || $contribution->status !== 'pending') {
            return;
        }

        $contribution->update([
            'status'                   => 'paid',
            'stripe_payment_intent_id' => $session->payment_intent,
        ]);

        // Increment bounty total with this contribution's amount
        $contribution->bounty()->increment('total_amount_cents', $contribution->amount_cents);

        Log::info('Bounty contribution paid via Stripe.', [
            'contribution_id' => $contribution->id,
            'bounty_id'       => $contribution->bounty_id,
        ]);
    }

    // -------------------------------------------------------------------------
    // payment_intent.payment_failed → mark contribution failed
    // -------------------------------------------------------------------------

    private function handlePaymentFailed(object $paymentIntent): void
    {
        $contribution = BountyContribution::where(
            'stripe_payment_intent_id',
            $paymentIntent->id,
        )->first();

        if (! $contribution || $contribution->status !== 'pending') {
            return;
        }

        $contribution->update(['status' => 'failed']);

        Log::info('Bounty contribution payment failed.', [
            'contribution_id' => $contribution->id,
            'payment_intent'  => $paymentIntent->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // account.updated → sync hunter stripe_connect_status
    // -------------------------------------------------------------------------

    private function handleAccountUpdated(object $account): void
    {
        $hunter = User::where('stripe_connect_account_id', $account->id)->first();

        if (! $hunter) {
            return;
        }

        $status = match (true) {
            $account->charges_enabled && $account->payouts_enabled => 'active',
            $account->details_submitted                             => 'pending_verification',
            default                                                 => 'pending',
        };

        $hunter->update(['stripe_connect_status' => $status]);

        Log::info('Stripe Connect account status updated.', [
            'user_id' => $hunter->id,
            'status'  => $status,
        ]);
    }

    // -------------------------------------------------------------------------
    // transfer.created → log successful payout
    // -------------------------------------------------------------------------

    private function handleTransferCreated(object $transfer): void
    {
        Log::info('Stripe Transfer created.', [
            'transfer_id' => $transfer->id,
            'bounty_id'   => $transfer->metadata->bounty_id ?? null,
            'amount'      => $transfer->amount,
        ]);
    }

    // -------------------------------------------------------------------------
    // charge.refunded → mark contribution refunded
    // -------------------------------------------------------------------------

    private function handleChargeRefunded(object $charge): void
    {
        $contribution = BountyContribution::where('stripe_charge_id', $charge->id)->first();

        if (! $contribution) {
            $contribution = BountyContribution::where(
                'stripe_payment_intent_id',
                $charge->payment_intent,
            )->first();
        }

        if (! $contribution) {
            return;
        }

        $contribution->update(['status' => 'refunded']);

        Log::info('Bounty contribution refunded.', [
            'contribution_id' => $contribution->id,
            'charge_id'       => $charge->id,
        ]);
    }
}
