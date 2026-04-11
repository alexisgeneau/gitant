<?php

namespace App\Actions;

use App\Models\Bounty;
use App\Models\BountyContribution;
use App\Models\User;
use App\Services\IssueMetadataService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CreateBountyAction
{
    public function __construct(
        private readonly IssueMetadataService $issueMetadataService,
    ) {}

    /**
     * Create or add a contribution to a bounty for the given issue URL.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function handle(User $funder, string $issueUrl, int $amountCents, ?string $publicMessage = null): Bounty
    {
        $metadata = $this->issueMetadataService->fetchFromUrl($issueUrl);

        $commissionCents = (int) round($amountCents * 0.10);

        return DB::transaction(function () use ($funder, $metadata, $amountCents, $commissionCents, $publicMessage) {
            $bounty = Bounty::firstOrCreate(
                [
                    'issue_platform'   => $metadata['issue_platform'],
                    'issue_repo_owner' => $metadata['issue_repo_owner'],
                    'issue_repo_name'  => $metadata['issue_repo_name'],
                    'issue_number'     => $metadata['issue_number'],
                ],
                [
                    'issue_url'         => $metadata['issue_url'],
                    'issue_title'       => $metadata['issue_title'],
                    'issue_description' => $metadata['issue_description'],
                    'issue_labels'      => $metadata['issue_labels'],
                    'issue_language'    => $metadata['issue_language'],
                    'status'            => 'open',
                    'total_amount_cents' => 0,
                    'public_message'    => $publicMessage,
                ]
            );

            // Update title/metadata in case it changed (re-fetch scenario)
            $bounty->update([
                'issue_title'       => $metadata['issue_title'],
                'issue_description' => $metadata['issue_description'],
                'issue_labels'      => $metadata['issue_labels'],
                'issue_language'    => $metadata['issue_language'],
            ]);

            BountyContribution::create([
                'bounty_id'        => $bounty->id,
                'user_id'          => $funder->id,
                'amount_cents'     => $amountCents,
                'commission_cents' => $commissionCents,
                'status'           => 'pending',
            ]);

            // Recompute total from paid contributions + this pending one (optimistic)
            $bounty->increment('total_amount_cents', $amountCents);
            $bounty->refresh();

            return $bounty;
        });
    }
}
