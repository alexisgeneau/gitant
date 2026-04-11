<?php

namespace App\Http\Controllers;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Generate a dynamic XML sitemap.
     *
     * Cached for 1 hour to avoid hammering the DB on every crawler visit.
     */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $baseUrl = rtrim(config('app.url'), '/');

            $lines = [];
            $lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
            $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            // Static pages
            foreach ([
                ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
                ['loc' => '/bounties', 'priority' => '0.9', 'changefreq' => 'hourly'],
            ] as $page) {
                $lines[] = $this->urlEntry($baseUrl . $page['loc'], now()->toAtomString(), $page['changefreq'], $page['priority']);
            }

            // Bounty pages — only open/claimed/in_review bounties
            Bounty::whereIn('status', ['open', 'claimed', 'in_review', 'completed'])
                ->orderByDesc('updated_at')
                ->select(['id', 'updated_at'])
                ->chunk(500, function ($bounties) use ($baseUrl, &$lines) {
                    foreach ($bounties as $bounty) {
                        $lines[] = $this->urlEntry(
                            $baseUrl . '/bounties/' . $bounty->id,
                            $bounty->updated_at->toAtomString(),
                            'daily',
                            '0.8',
                        );
                    }
                });

            // Public user profiles
            User::orderByDesc('updated_at')
                ->select(['username', 'updated_at'])
                ->chunk(500, function ($users) use ($baseUrl, &$lines) {
                    foreach ($users as $user) {
                        $lines[] = $this->urlEntry(
                            $baseUrl . '/profile/' . rawurlencode($user->username),
                            $user->updated_at->toAtomString(),
                            'weekly',
                            '0.5',
                        );
                    }
                });

            $lines[] = '</urlset>';

            return implode("\n", $lines);
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return sprintf(
            "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>",
            htmlspecialchars($loc, ENT_XML1, 'UTF-8'),
            $lastmod,
            $changefreq,
            $priority,
        );
    }
}
