<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    private const ALLOWED_PROVIDERS = ['github', 'gitlab'];

    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['oauth' => 'Authentication failed. Please try again.']);
        }

        $idField = "{$provider}_id";

        // Find existing user by provider ID first, then by email
        $user = User::where($idField, $socialUser->getId())->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::whereNull($idField)
                ->where('email', $socialUser->getEmail())
                ->first();
        }

        if ($user) {
            // Update OAuth id and avatar if not already linked
            $user->fill([
                $idField => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar() ?? $user->avatar_url,
            ])->save();
        } else {
            // Create new user
            $user = User::create([
                'username' => $this->resolveUniqueUsername($socialUser->getNickname() ?? $socialUser->getName()),
                'email' => $socialUser->getEmail(),
                $idField => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended('/dashboard');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);
    }

    private function resolveUniqueUsername(?string $candidate): string
    {
        $base = Str::slug($candidate ?? 'user', '_');
        $base = $base ?: 'user';
        $username = $base;
        $i = 1;

        while (User::where('username', $username)->exists()) {
            $username = "{$base}_{$i}";
            $i++;
        }

        return $username;
    }
}
