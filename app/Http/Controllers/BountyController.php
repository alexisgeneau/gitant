<?php

namespace App\Http\Controllers;

use App\Actions\ApproveBountyAction;
use App\Actions\ClaimBountyAction;
use App\Actions\CreateBountyAction;
use App\Actions\RejectBountyAction;
use App\Actions\ReleaseClaimAction;
use App\Http\Requests\CreateBountyRequest;
use App\Models\Bounty;
use App\Services\IssueMetadataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class BountyController extends Controller
{
    // -------------------------------------------------------------------------
    // Public catalogue
    // -------------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $query = Bounty::query()->where('status', 'open');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('issue_title', 'ilike', "%{$search}%")
                  ->orWhere('issue_description', 'ilike', "%{$search}%");
            });
        }

        if ($platform = $request->input('platform')) {
            $query->where('issue_platform', $platform);
        }

        if ($language = $request->input('language')) {
            $query->where('issue_language', $language);
        }

        if ($minAmount = $request->input('min_amount')) {
            $query->where('total_amount_cents', '>=', (int) $minAmount * 100);
        }

        if ($maxAmount = $request->input('max_amount')) {
            $query->where('total_amount_cents', '<=', (int) $maxAmount * 100);
        }

        $sortField = match ($request->input('sort', 'amount')) {
            'created_at' => 'created_at',
            'expires_at'  => 'expires_at',
            default       => 'total_amount_cents',
        };

        $query->orderByDesc($sortField)->orderByDesc('created_at');

        $bounties = $query->paginate(20)->withQueryString();

        $languages = Cache::remember('bounty_languages', 300, fn () =>
            Bounty::query()
                ->whereNotNull('issue_language')
                ->where('status', 'open')
                ->distinct()
                ->pluck('issue_language')
                ->sort()
                ->values()
        );

        return Inertia::render('Bounty/Index', [
            'bounties'  => $bounties,
            'filters'   => $request->only(['q', 'platform', 'language', 'min_amount', 'max_amount', 'sort']),
            'languages' => $languages,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create form
    // -------------------------------------------------------------------------

    public function create(): Response
    {
        return Inertia::render('Bounty/Create');
    }

    // -------------------------------------------------------------------------
    // Resolve issue metadata (AJAX)
    // -------------------------------------------------------------------------

    public function resolveIssue(Request $request, IssueMetadataService $service): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'string', 'url'],
        ]);

        try {
            $metadata = $service->fetchFromUrl($request->input('url'));
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        return response()->json($metadata);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function store(CreateBountyRequest $request, CreateBountyAction $action): \Illuminate\Http\RedirectResponse
    {
        try {
            ['bounty' => $bounty, 'checkoutUrl' => $checkoutUrl] = $action->handle(
                funder: $request->user(),
                issueUrl: $request->input('issue_url'),
                amountCents: $request->integer('amount_cents'),
                publicMessage: $request->input('public_message'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['issue_url' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            return back()->withErrors(['issue_url' => $e->getMessage()]);
        }

        return redirect()->away($checkoutUrl);
    }

    // -------------------------------------------------------------------------
    // Show (public)
    // -------------------------------------------------------------------------

    public function show(Request $request, Bounty $bounty): Response
    {
        $bounty->load(['claimer', 'paidContributions.funder']);

        return Inertia::render('Bounty/Show', [
            'bounty'        => $bounty,
            'paymentStatus' => $request->query('payment'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Claim
    // -------------------------------------------------------------------------

    public function claim(Request $request, Bounty $bounty, ClaimBountyAction $action): RedirectResponse
    {
        try {
            $action->handle($bounty, $request->user());
        } catch (LogicException $e) {
            return back()->withErrors(['claim' => $e->getMessage()]);
        }

        return redirect()->route('bounties.show', $bounty)
            ->with('success', 'You have claimed this bounty. You have 7 days to submit a PR.');
    }

    // -------------------------------------------------------------------------
    // Release claim
    // -------------------------------------------------------------------------

    public function release(Request $request, Bounty $bounty, ReleaseClaimAction $action): RedirectResponse
    {
        try {
            $action->handle($bounty, $request->user());
        } catch (LogicException $e) {
            return back()->withErrors(['release' => $e->getMessage()]);
        }

        return redirect()->route('bounties.show', $bounty)
            ->with('success', 'Claim released. The bounty is open again.');
    }

    // -------------------------------------------------------------------------
    // Approve (funder validates PR → triggers payout)
    // -------------------------------------------------------------------------

    public function approve(Request $request, Bounty $bounty, ApproveBountyAction $action): RedirectResponse
    {
        try {
            $action->handle($bounty, $request->user());
        } catch (LogicException $e) {
            return back()->withErrors(['approve' => $e->getMessage()]);
        } catch (RuntimeException $e) {
            return back()->withErrors(['approve' => $e->getMessage()]);
        }

        return redirect()->route('bounties.show', $bounty)
            ->with('success', 'Bounty approved! Payout has been triggered for the hunter.');
    }

    // -------------------------------------------------------------------------
    // Reject (funder rejects PR)
    // -------------------------------------------------------------------------

    public function reject(Request $request, Bounty $bounty, RejectBountyAction $action): RedirectResponse
    {
        $request->validate([
            'dispute' => ['sometimes', 'boolean'],
        ]);

        try {
            $action->handle($bounty, $request->user(), (bool) $request->input('dispute', false));
        } catch (LogicException $e) {
            return back()->withErrors(['reject' => $e->getMessage()]);
        }

        $message = $request->input('dispute')
            ? 'Dispute opened. An admin will arbitrate.'
            : 'PR rejected. The hunter can revise and resubmit.';

        return redirect()->route('bounties.show', $bounty)->with('success', $message);
    }

    // -------------------------------------------------------------------------
    // RSS feed
    // -------------------------------------------------------------------------

    public function rss(): \Illuminate\Http\Response
    {
        $bounties = Bounty::query()
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $xml = view('rss.bounties', compact('bounties'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
