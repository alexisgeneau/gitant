<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Index', [
            'user' => [
                'username' => $user->username,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
                'preferred_locale' => $user->preferred_locale,
                'github_id' => $user->github_id,
                'gitlab_id' => $user->gitlab_id,
                'stripe_connect_status' => $user->stripe_connect_status,
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated())->save();

        return redirect()->route('settings')->with('success', 'Settings updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Soft-delete the account
        $user->delete();

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
