<?php

use App\Jobs\CheckAutoValidation;
use App\Jobs\CheckExpiredClaims;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// -------------------------------------------------------------------------
// Scheduled jobs
// -------------------------------------------------------------------------

// Release claims that expired without a PR submitted (7-day window)
Schedule::job(new CheckExpiredClaims)->hourly()->name('check-expired-claims');

// Auto-validate bounties where funder hasn't responded within 14 days
Schedule::job(new CheckAutoValidation)->hourly()->name('check-auto-validation');
