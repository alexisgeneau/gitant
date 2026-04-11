<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class IssueMetadataService
{
    /**
     * Parse a GitHub or GitLab issue URL and return metadata.
     *
     * @throws InvalidArgumentException When URL is not a valid issue URL
     * @throws RuntimeException When the API call fails or issue is inaccessible
     */
    public function fetchFromUrl(string $url): array
    {
        if ($github = $this->parseGithubUrl($url)) {
            return $this->fetchGithubIssue($github['owner'], $github['repo'], $github['number']);
        }

        if ($gitlab = $this->parseGitlabUrl($url)) {
            return $this->fetchGitlabIssue($gitlab['namespace'], $gitlab['repo'], $gitlab['number']);
        }

        throw new InvalidArgumentException('The URL must be a valid GitHub or GitLab issue URL.');
    }

    // -------------------------------------------------------------------------
    // URL parsing
    // -------------------------------------------------------------------------

    private function parseGithubUrl(string $url): ?array
    {
        if (preg_match('#^https?://github\.com/([^/]+)/([^/]+)/issues/(\d+)#i', $url, $m)) {
            return ['owner' => $m[1], 'repo' => $m[2], 'number' => (int) $m[3]];
        }
        return null;
    }

    private function parseGitlabUrl(string $url): ?array
    {
        if (preg_match('#^https?://gitlab\.com/(.+?)/([^/]+)/-/issues/(\d+)#i', $url, $m)) {
            return ['namespace' => $m[1], 'repo' => $m[2], 'number' => (int) $m[3]];
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // GitHub
    // -------------------------------------------------------------------------

    private function fetchGithubIssue(string $owner, string $repo, int $number): array
    {
        $headers = ['Accept' => 'application/vnd.github+json'];

        if ($token = config('services.github.token')) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/issues/{$number}");
        } catch (RequestException $e) {
            throw new RuntimeException('Failed to reach GitHub API.');
        }

        if ($response->status() === 404) {
            throw new RuntimeException('The GitHub issue was not found or the repository is private.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('GitHub API returned an error: ' . $response->status());
        }

        $data = $response->json();

        if (! empty($data['pull_request'])) {
            throw new InvalidArgumentException('The URL points to a pull request, not an issue.');
        }

        if ($data['state'] !== 'open') {
            throw new InvalidArgumentException('The GitHub issue is already closed.');
        }

        // Fetch primary language of the repo
        $language = $this->fetchGithubRepoLanguage($owner, $repo, $headers);

        return [
            'issue_url'          => "https://github.com/{$owner}/{$repo}/issues/{$number}",
            'issue_platform'     => 'github',
            'issue_repo_owner'   => $owner,
            'issue_repo_name'    => $repo,
            'issue_number'       => $number,
            'issue_title'        => $data['title'],
            'issue_description'  => $data['body'] ?? null,
            'issue_labels'       => array_column($data['labels'] ?? [], 'name'),
            'issue_language'     => $language,
        ];
    }

    private function fetchGithubRepoLanguage(string $owner, string $repo, array $headers): ?string
    {
        try {
            $response = Http::withHeaders($headers)
                ->timeout(5)
                ->get("https://api.github.com/repos/{$owner}/{$repo}");

            if ($response->successful()) {
                return $response->json('language');
            }
        } catch (\Throwable) {
            // Non-critical — language is optional
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // GitLab
    // -------------------------------------------------------------------------

    private function fetchGitlabIssue(string $namespace, string $repo, int $number): array
    {
        $projectPath = urlencode("{$namespace}/{$repo}");

        $headers = [];
        if ($token = config('services.gitlab.token')) {
            $headers['PRIVATE-TOKEN'] = $token;
        }

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://gitlab.com/api/v4/projects/{$projectPath}/issues/{$number}");
        } catch (RequestException $e) {
            throw new RuntimeException('Failed to reach GitLab API.');
        }

        if ($response->status() === 404) {
            throw new RuntimeException('The GitLab issue was not found or the project is private.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('GitLab API returned an error: ' . $response->status());
        }

        $data = $response->json();

        if ($data['state'] !== 'opened') {
            throw new InvalidArgumentException('The GitLab issue is already closed.');
        }

        $language = $this->fetchGitlabProjectLanguage($projectPath, $headers);

        return [
            'issue_url'          => "https://gitlab.com/{$namespace}/{$repo}/-/issues/{$number}",
            'issue_platform'     => 'gitlab',
            'issue_repo_owner'   => $namespace,
            'issue_repo_name'    => $repo,
            'issue_number'       => $number,
            'issue_title'        => $data['title'],
            'issue_description'  => $data['description'] ?? null,
            'issue_labels'       => $data['labels'] ?? [],
            'issue_language'     => $language,
        ];
    }

    private function fetchGitlabProjectLanguage(string $projectPath, array $headers): ?string
    {
        try {
            $response = Http::withHeaders($headers)
                ->timeout(5)
                ->get("https://gitlab.com/api/v4/projects/{$projectPath}/languages");

            if ($response->successful()) {
                $languages = $response->json();
                if (is_array($languages) && count($languages) > 0) {
                    return array_key_first($languages);
                }
            }
        } catch (\Throwable) {
            // Non-critical
        }

        return null;
    }
}
