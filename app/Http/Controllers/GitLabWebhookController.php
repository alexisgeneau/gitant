<?php

namespace App\Http\Controllers;

use App\Actions\MarkBountyInReviewAction;
use App\Models\Bounty;
use App\Services\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GitLabWebhookController extends Controller
{
    // Regex to extract issue numbers/iids from MR description
    private const ISSUE_KEYWORDS_PATTERN = '/(?:close[sd]?|fix(?:e[sd])?|resolve[sd]?)\s+#(\d+)/i';

    public function __construct(
        private readonly WebhookVerifier $verifier,
        private readonly MarkBountyInReviewAction $markInReview,
    ) {}

    public function handle(Request $request): Response
    {
        $token = $request->header('X-Gitlab-Token', '');

        try {
            $this->verifier->verifyGitLab($token);
        } catch (RuntimeException $e) {
            Log::warning('GitLab webhook token verification failed.', ['error' => $e->getMessage()]);
            return response('Invalid token', 400);
        }

        $event   = $request->header('X-Gitlab-Event', '');
        $payload = $request->json()->all();

        match ($event) {
            'Merge Request Hook' => $this->handleMergeRequest($payload),
            'Note Hook'          => $this->handleNote($payload),
            default              => null,
        };

        return response('OK', 200);
    }

    // -------------------------------------------------------------------------
    // Merge Request Hook
    // -------------------------------------------------------------------------

    private function handleMergeRequest(array $payload): void
    {
        $attrs     = $payload['object_attributes'] ?? [];
        $action    = $attrs['action'] ?? '';
        $repoPath  = $payload['project']['path_with_namespace'] ?? null;

        if (! $repoPath || ! in_array($action, ['open', 'close', 'merge'], true)) {
            return;
        }

        [$repoOwner, $repoName] = array_pad(explode('/', $repoPath, 2), 2, null);

        $description = $attrs['description'] ?? '';
        $title       = $attrs['title'] ?? '';
        $issueNumbers = $this->extractIssueNumbers($title . ' ' . $description);

        foreach ($issueNumbers as $issueNumber) {
            $bounty = Bounty::where([
                'issue_platform'   => 'gitlab',
                'issue_repo_owner' => $repoOwner,
                'issue_repo_name'  => $repoName,
                'issue_number'     => $issueNumber,
            ])->first();

            if (! $bounty) {
                continue;
            }

            if ($action === 'open' && $bounty->isClaimed()) {
                $mrUrl    = $attrs['url'] ?? '';
                $mrIid    = $attrs['iid'] ?? 0;

                try {
                    $this->markInReview->handle($bounty, $mrUrl, 'gitlab', $mrIid);
                    Log::info('GitLab MR linked to bounty.', [
                        'bounty_id' => $bounty->id,
                        'mr_url'    => $mrUrl,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to mark bounty in_review from GitLab MR.', [
                        'bounty_id' => $bounty->id,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            if ($action === 'close' && $bounty->status === 'in_review') {
                $bounty->update([
                    'status'           => 'claimed',
                    'auto_validate_at' => null,
                    'linked_pr_url'    => null,
                    'linked_pr_number' => null,
                    'pr_submitted_at'  => null,
                ]);
                Log::info('GitLab MR closed — bounty reverted to claimed.', ['bounty_id' => $bounty->id]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Note Hook (comments on MR / issue)
    // -------------------------------------------------------------------------

    private function handleNote(array $payload): void
    {
        Log::debug('GitLab Note Hook received.', [
            'notable_type' => $payload['object_attributes']['notable_type'] ?? null,
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
