<?php

namespace App\Http\Controllers;

use App\Actions\MarkBountyInReviewAction;
use App\Actions\ReleaseClaimAction;
use App\Models\Bounty;
use App\Services\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GitHubWebhookController extends Controller
{
    // Regex to extract issue numbers from PR body/title keywords
    private const ISSUE_KEYWORDS_PATTERN = '/(?:close[sd]?|fix(?:e[sd])?|resolve[sd]?)\s+#(\d+)/i';

    public function __construct(
        private readonly WebhookVerifier $verifier,
        private readonly MarkBountyInReviewAction $markInReview,
        private readonly ReleaseClaimAction $releaseClaim,
    ) {}

    public function handle(Request $request): Response
    {
        $signature = $request->header('X-Hub-Signature-256', '');

        try {
            $this->verifier->verifyGitHub($request->getContent(), $signature);
        } catch (RuntimeException $e) {
            Log::warning('GitHub webhook signature verification failed.', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        $event = $request->header('X-GitHub-Event', '');
        $payload = $request->json()->all();

        match ($event) {
            'pull_request' => $this->handlePullRequest($payload),
            'issue_comment' => $this->handleIssueComment($payload),
            default => null,
        };

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------
    // pull_request event
    // -------------------------------------------------------------------------

    private function handlePullRequest(array $payload): void
    {
        $action = $payload['action'] ?? '';
        $pr     = $payload['pull_request'] ?? [];
        $repo   = $payload['repository'] ?? [];

        if (! in_array($action, ['opened', 'closed'], true)) {
            return;
        }

        $repoOwner = $repo['owner']['login'] ?? null;
        $repoName  = $repo['name'] ?? null;

        if (! $repoOwner || ! $repoName) {
            return;
        }

        // Extract referenced issue numbers from PR body
        $body    = $pr['body'] ?? '';
        $title   = $pr['title'] ?? '';
        $issueNumbers = $this->extractIssueNumbers($title . ' ' . $body);

        foreach ($issueNumbers as $issueNumber) {
            $bounty = Bounty::where([
                'issue_platform'   => 'github',
                'issue_repo_owner' => $repoOwner,
                'issue_repo_name'  => $repoName,
                'issue_number'     => $issueNumber,
            ])->first();

            if (! $bounty) {
                continue;
            }

            if ($action === 'opened' && $bounty->isClaimed()) {
                $prUrl    = $pr['html_url'] ?? '';
                $prNumber = $pr['number'] ?? 0;

                try {
                    $this->markInReview->handle($bounty, $prUrl, 'github', $prNumber);
                    Log::info('GitHub PR linked to bounty.', [
                        'bounty_id' => $bounty->id,
                        'pr_url'    => $prUrl,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to mark bounty in_review from GitHub PR.', [
                        'bounty_id' => $bounty->id,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            if ($action === 'closed' && ! ($pr['merged'] ?? false) && $bounty->status === 'in_review') {
                // PR closed without merge — revert to claimed so hunter can re-submit
                $bounty->update([
                    'status'           => 'claimed',
                    'auto_validate_at' => null,
                    'linked_pr_url'    => null,
                    'linked_pr_number' => null,
                    'pr_submitted_at'  => null,
                ]);
                Log::info('PR closed without merge — bounty reverted to claimed.', ['bounty_id' => $bounty->id]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // issue_comment event (PR comments are also issue_comment on GitHub)
    // -------------------------------------------------------------------------

    private function handleIssueComment(array $payload): void
    {
        // Reserved for funder replies / auto-validation extension logic (GIT-10)
        Log::debug('GitHub issue_comment webhook received.', [
            'issue' => $payload['issue']['number'] ?? null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function extractIssueNumbers(string $text): array
    {
        preg_match_all(self::ISSUE_KEYWORDS_PATTERN, $text, $matches);
        return array_map('intval', array_unique($matches[1] ?? []));
    }
}
