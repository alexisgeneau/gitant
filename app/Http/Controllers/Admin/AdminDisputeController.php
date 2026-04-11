<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveDisputeAction;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

class AdminDisputeController extends Controller
{
    public function __construct(private readonly ResolveDisputeAction $resolveDispute) {}

    // -------------------------------------------------------------------------
    // Admin dispute list (GET /admin/disputes)
    // -------------------------------------------------------------------------

    public function index(): Response
    {
        $disputes = Dispute::query()
            ->with(['bounty', 'opener'])
            ->whereIn('status', ['awaiting_response', 'mediation', 'arbitration'])
            ->orderBy('response_deadline_at')
            ->paginate(25);

        $resolved = Dispute::query()
            ->with(['bounty', 'opener', 'resolver'])
            ->where('status', 'resolved')
            ->orderByDesc('resolved_at')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => $disputes,
            'resolved' => $resolved,
        ]);
    }

    // -------------------------------------------------------------------------
    // Admin resolve dispute (POST /admin/disputes/{dispute}/resolve)
    // -------------------------------------------------------------------------

    public function resolve(Request $request, Dispute $dispute): RedirectResponse
    {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'in:paid_full,refunded_full,split_75_25,split_50_50,split_25_75,mutual'],
            'notes'      => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        try {
            $this->resolveDispute->handle(
                dispute: $dispute,
                admin: $request->user(),
                resolution: $data['resolution'],
                notes: $data['notes'],
            );
        } catch (LogicException $e) {
            return back()->withErrors(['resolve' => $e->getMessage()]);
        }

        return redirect()->route('admin.disputes.index')
            ->with('success', "Dispute resolved: {$data['resolution']}.");
    }
}
