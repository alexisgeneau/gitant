<?php

namespace App\Services;

use RuntimeException;

class WebhookVerifier
{
    /**
     * Verify a GitHub webhook HMAC-SHA256 signature.
     *
     * @throws RuntimeException if the signature is invalid
     */
    public function verifyGitHub(string $payload, string $signature): void
    {
        $secret = config('services.github.webhook_secret');

        if (! $secret) {
            throw new RuntimeException('GitHub webhook secret is not configured.');
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('GitHub webhook signature mismatch.');
        }
    }

    /**
     * Verify a GitLab webhook secret token.
     *
     * @throws RuntimeException if the token is invalid
     */
    public function verifyGitLab(string $token): void
    {
        $secret = config('services.gitlab.webhook_secret');

        if (! $secret) {
            throw new RuntimeException('GitLab webhook secret is not configured.');
        }

        if (! hash_equals($secret, $token)) {
            throw new RuntimeException('GitLab webhook token mismatch.');
        }
    }
}
