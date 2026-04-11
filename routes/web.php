<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\BountyController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\SettingsController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// -------------------------------------------------------------------------
// Public routes
// -------------------------------------------------------------------------

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login')->middleware('guest');

// -------------------------------------------------------------------------
// OAuth routes (rate-limited)
// -------------------------------------------------------------------------

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/{provider}', [SocialiteController::class, 'redirect'])
        ->where('provider', 'github|gitlab')
        ->name('auth.redirect');

    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->where('provider', 'github|gitlab')
        ->name('auth.callback');
});

Route::post('/logout', [SocialiteController::class, 'logout'])
    ->name('logout')
    ->middleware('auth:sanctum');

// -------------------------------------------------------------------------
// Bounty routes (create/rss before {bounty} to avoid wildcard capture)
// -------------------------------------------------------------------------

Route::get('/bounties', [BountyController::class, 'index'])->name('bounties.index');
Route::get('/bounties/rss', [BountyController::class, 'rss'])->name('bounties.rss');

// Auth-required creation routes — declared before {bounty} to prevent wildcard capture
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bounties/create', [BountyController::class, 'create'])->name('bounties.create');
    Route::post('/bounties', [BountyController::class, 'store'])->name('bounties.store');
    Route::post('/api/bounties/resolve-issue', [BountyController::class, 'resolveIssue'])->name('bounties.resolve-issue');
});

Route::get('/bounties/{bounty}', [BountyController::class, 'show'])->name('bounties.show');

// -------------------------------------------------------------------------
// Public profile pages
// -------------------------------------------------------------------------

Route::get('/profile/{username}', [ProfileController::class, 'show'])
    ->name('profile.show');

// -------------------------------------------------------------------------
// Authenticated routes
// -------------------------------------------------------------------------

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('settings');
        Route::patch('/', [SettingsController::class, 'update'])->name('settings.update');
        Route::delete('/', [SettingsController::class, 'destroy'])->name('settings.destroy');

        // Stripe Connect (hunter payment onboarding)
        Route::prefix('payments')->group(function () {
            Route::get('/', [StripeConnectController::class, 'index'])->name('settings.payments');
            Route::post('/connect', [StripeConnectController::class, 'connect'])->name('settings.payments.connect');
            Route::get('/return', [StripeConnectController::class, 'return'])->name('settings.payments.return');
        });
    });
});

// -------------------------------------------------------------------------
// Stripe webhooks (no CSRF, raw body needed for signature verification)
// -------------------------------------------------------------------------

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
    ->middleware('throttle:100,1');
