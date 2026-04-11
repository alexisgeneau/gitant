<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\BountyContribution;
use App\Models\User;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    // -------------------------------------------------------------------------
    // Funder: Stripe Checkout session
    // -------------------------------------------------------------------------

    /**
     * Create a Stripe Checkout session for a bounty contribution.
     * Returns ['url' => ..., 'session_id' => ...].
     *
     * @return array{url: string, session_id: string}
     */
    public function createCheckoutSession(
        Bounty $bounty,
        BountyContribution $contribution,
        User $funder,
    ): array {
        $totalCents = $contribution->amount_cents + $contribution->commission_cents;

        $params = [
            'mode'    => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => $totalCents,
                    'product_data' => [
                        'name'        => "Bounty: {$bounty->issue_title}",
                        'description' => "Bounty on {$bounty->fullRepoName()} #{$bounty->issue_number} (includes 10% platform fee)",
                    ],
                ],
                'quantity' => 1,
            ]],
            'success_url' => route('bounties.show', $bounty) . '?payment=success',
            'cancel_url'  => route('bounties.show', $bounty) . '?payment=cancelled',
            'metadata'    => [
                'bounty_id'       => $bounty->id,
                'contribution_id' => $contribution->id,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'bounty_id'       => $bounty->id,
                    'contribution_id' => $contribution->id,
                ],
            ],
        ];

        // Associate with Stripe Customer if funder already has one
        if ($funder->stripe_id) {
            $params['customer'] = $funder->stripe_id;
        } else {
            $params['customer_email'] = $funder->email;
        }

        $session = $this->stripe->checkout->sessions->create($params);

        return [
            'url'        => $session->url,
            'session_id' => $session->id,
        ];
    }

    // -------------------------------------------------------------------------
    // Hunter: Stripe Connect Custom account
    // -------------------------------------------------------------------------

    /**
     * Create a Stripe Connect Custom account for a hunter.
     */
    public function createConnectAccount(User $hunter): string
    {
        $account = $this->stripe->accounts->create([
            'type'         => 'custom',
            'country'      => 'FR',
            'email'        => $hunter->email,
            'capabilities' => [
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'metadata' => [
                'user_id' => $hunter->id,
            ],
        ]);

        $hunter->update([
            'stripe_connect_account_id' => $account->id,
            'stripe_connect_status'     => 'pending',
        ]);

        return $account->id;
    }

    /**
     * Create an Account Link for KYC onboarding.
     * Returns the URL to redirect the hunter to.
     */
    public function createAccountLink(User $hunter): string
    {
        $accountId = $hunter->stripe_connect_account_id;

        if (! $accountId) {
            $accountId = $this->createConnectAccount($hunter);
        }

        $link = $this->stripe->accountLinks->create([
            'account'     => $accountId,
            'refresh_url' => route('settings.payments.connect'),
            'return_url'  => route('settings.payments.return'),
            'type'        => 'account_onboarding',
        ]);

        return $link->url;
    }

    /**
     * Refresh Connect account status from Stripe and update the user.
     */
    public function syncConnectStatus(User $hunter): string
    {
        if (! $hunter->stripe_connect_account_id) {
            return 'not_connected';
        }

        $account = $this->stripe->accounts->retrieve($hunter->stripe_connect_account_id);

        $status = match (true) {
            $account->charges_enabled && $account->payouts_enabled => 'active',
            $account->details_submitted                             => 'pending_verification',
            default                                                 => 'pending',
        };

        $hunter->update(['stripe_connect_status' => $status]);

        return $status;
    }

    // -------------------------------------------------------------------------
    // Payout: Transfer to hunter
    // -------------------------------------------------------------------------

    /**
     * Transfer the bounty amount to the hunter's connected account.
     * Returns the Stripe Transfer ID.
     */
    public function transferToHunter(Bounty $bounty, User $hunter): string
    {
        if (! $hunter->stripe_connect_account_id) {
            throw new \RuntimeException("Hunter {$hunter->id} has no Stripe Connect account.");
        }

        $transfer = $this->stripe->transfers->create([
            'amount'      => $bounty->total_amount_cents,
            'currency'    => 'eur',
            'destination' => $hunter->stripe_connect_account_id,
            'metadata'    => [
                'bounty_id' => $bounty->id,
                'hunter_id' => $hunter->id,
            ],
        ]);

        return $transfer->id;
    }

    // -------------------------------------------------------------------------
    // Refund: Refund funder contributions
    // -------------------------------------------------------------------------

    /**
     * Refund a paid contribution back to the funder.
     * Returns the Stripe Refund ID.
     */
    public function refundContribution(BountyContribution $contribution): string
    {
        if (! $contribution->stripe_payment_intent_id && ! $contribution->stripe_charge_id) {
            throw new \RuntimeException("Contribution {$contribution->id} has no Stripe payment reference.");
        }

        $params = ['metadata' => ['contribution_id' => $contribution->id]];

        if ($contribution->stripe_charge_id) {
            $params['charge'] = $contribution->stripe_charge_id;
        } else {
            $params['payment_intent'] = $contribution->stripe_payment_intent_id;
        }

        $refund = $this->stripe->refunds->create($params);

        return $refund->id;
    }

    // -------------------------------------------------------------------------
    // Webhook signature verification
    // -------------------------------------------------------------------------

    /**
     * Construct and verify a Stripe webhook event from the raw request payload.
     *
     * @throws \Stripe\Exception\SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret'),
        );
    }
}
