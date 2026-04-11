<?php

use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\SettingsController;
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
    });
});
