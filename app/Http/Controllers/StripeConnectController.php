<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StripeConnectController extends Controller
{
    public function __construct(private readonly StripeService $stripeService) {}

    /**
     * GET /settings/payments
     * Show the payment settings page with current Connect status.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Payments', [
            'connectStatus' => $user->stripe_connect_status,
            'hasAccount'    => (bool) $user->stripe_connect_account_id,
        ]);
    }

    /**
     * POST /settings/payments/connect
     * Create or refresh a Stripe Connect Account Link and redirect the hunter.
     */
    public function connect(Request $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $url = $this->stripeService->createAccountLink($user);
        } catch (\Exception $e) {
            return back()->withErrors(['stripe' => 'Unable to start Stripe onboarding. Please try again.']);
        }

        return redirect()->away($url);
    }

    /**
     * GET /settings/payments/return
     * Called by Stripe after the hunter completes (or abandons) KYC.
     * Syncs the Connect account status and redirects back to settings.
     */
    public function return(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->stripe_connect_account_id) {
            $this->stripeService->syncConnectStatus($user);
        }

        return redirect()->route('settings.payments')
            ->with('success', 'Your payment account has been updated.');
    }
}
