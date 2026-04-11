<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\BountyContribution;
use App\Models\User;
use App\Services\IssueMetadataService;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CreateBountyAction
{
    public function __construct(
        private readonly IssueMetadataService $issueMetadataService,
        private readonly StripeService $stripeService,
    ) {}

    /**
     * Create (or stack) a bounty contribution and return the Stripe Checkout URL.
     *
     * The contribution starts as 'pending' until Stripe confirms payment via webhook.
     * The bounty total is only incremented after payment is confirmed.
     *
     * @throws InvalidArgumentException|RuntimeException
     * @return array{bounty: Bounty, checkoutUrl: string}
     */
    public function handle(User $funder, string $issueUrl, int $amountCents, ?string $publicMessage = null): array
    {
        $metadata = $this->issueMetadataService->fetchFromUrl($issueUrl);

        $commissionCents = (int) round($amountCents * 0.10);

        [$bounty, $contribution] = DB::transaction(function () use ($funder, $metadata, $amountCents, $commissionCents, $publicMessage) {
            $bounty = Bounty::firstOrCreate(
                [
                    'issue_platform'   => $metadata['issue_platform'],
                    'issue_repo_owner' => $metadata['issue_repo_owner'],
                    'issue_repo_name'  => $metadata['issue_repo_name'],
                    'issue_number'     => $metadata['issue_number'],
                ],
                [
                    'issue_url'          => $metadata['issue_url'],
                    'issue_title'        => $metadata['issue_title'],
                    'issue_description'  => $metadata['issue_description'],
                    'issue_labels'       => $metadata['issue_labels'],
                    'issue_language'     => $metadata['issue_language'],
                    'status'             => 'open',
                    'total_amount_cents' => 0,
                    'public_message'     => $publicMessage,
                ]
            );

            // Refresh metadata in case the issue was updated upstream
            $bounty->update([
                'issue_title'       => $metadata['issue_title'],
                'issue_description' => $metadata['issue_description'],
                'issue_labels'      => $metadata['issue_labels'],
                'issue_language'    => $metadata['issue_language'],
            ]);

            $contribution = BountyContribution::create([
                'bounty_id'        => $bounty->id,
                'user_id'          => $funder->id,
                'amount_cents'     => $amountCents,
                'commission_cents' => $commissionCents,
                'status'           => 'pending',
            ]);

            return [$bounty, $contribution];
        });

        // Create the Stripe Checkout session
        ['url' => $checkoutUrl, 'session_id' => $sessionId] =
            $this->stripeService->createCheckoutSession($bounty, $contribution, $funder);

        // Persist the session ID for idempotent webhook matching
        $contribution->update(['stripe_checkout_session_id' => $sessionId]);

        return [
            'bounty'      => $bounty,
            'checkoutUrl' => $checkoutUrl,
        ];
    }
}
