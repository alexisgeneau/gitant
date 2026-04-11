<?php

namespace App\Http\Controllers;

use App\Actions\OpenDisputeAction;
use App\Models\Bounty;
use App\Models\Dispute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

class DisputeController extends Controller
{
    public function __construct(private readonly OpenDisputeAction $openDispute) {}

    // -------------------------------------------------------------------------
    // Open dispute form (GET /bounties/{bounty}/dispute)
    // -------------------------------------------------------------------------

    public function create(Bounty $bounty): Response
    {
        $bounty->load('activeDispute');

        return Inertia::render('Dispute/Create', [
            'bounty' => $bounty,
        ]);
    }

    // -------------------------------------------------------------------------
    // Submit dispute (POST /bounties/{bounty}/dispute)
    // -------------------------------------------------------------------------

    public function store(Request $request, Bounty $bounty): RedirectResponse
    {
        $data = $request->validate([
            'type'     => ['required', 'string', 'in:unjustified_rejection,non_conforming_pr,ambiguous_specs,timing,other'],
            'summary'  => ['required', 'string', 'min:50', 'max:2000'],
            'demand'   => ['required', 'string', 'in:full_payment,full_refund,split_75_25,split_50_50,split_25_75'],
            'evidence' => ['sometimes', 'array', 'max:5'],
            'evidence.*' => ['url', 'max:500'],
        ]);

        try {
            $dispute = $this->openDispute->handle(
                bounty: $bounty,
                opener: $request->user(),
                type: $data['type'],
                summary: $data['summary'],
                demand: $data['demand'],
                evidence: $data['evidence'] ?? [],
            );
        } catch (LogicException $e) {
            return back()->withErrors(['dispute' => $e->getMessage()]);
        }

        return redirect()->route('disputes.show', $dispute)
            ->with('success', 'Dispute opened. The other party has 7 days to respond.');
    }

    // -------------------------------------------------------------------------
    // Show dispute (GET /disputes/{dispute})
    // -------------------------------------------------------------------------

    public function show(Dispute $dispute): Response
    {
        $dispute->load(['bounty', 'opener', 'resolver']);

        return Inertia::render('Dispute/Show', [
            'dispute' => $dispute,
        ]);
    }

    // -------------------------------------------------------------------------
    // Respondent reply (POST /disputes/{dispute}/respond)
    // -------------------------------------------------------------------------

    public function respond(Request $request, Dispute $dispute): RedirectResponse
    {
        if ($dispute->status !== 'awaiting_response') {
            return back()->withErrors(['respond' => 'This dispute is not awaiting a response.']);
        }

        $data = $request->validate([
            'position' => ['required', 'string', 'min:50', 'max:2000'],
            'evidence' => ['sometimes', 'array', 'max:5'],
            'evidence.*' => ['url', 'max:500'],
        ]);

        $dispute->update([
            'respondent_position'   => $data['position'],
            'respondent_evidence'   => $data['evidence'] ?? [],
            'respondent_replied_at' => now(),
            'status'                => 'mediation',
        ]);

        return redirect()->route('disputes.show', $dispute)
            ->with('success', 'Your response has been recorded. Mediation phase has begun (48h).');
    }
}
