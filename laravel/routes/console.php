<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Jobs\RecalculateCustomerMetrics;

Schedule::command('waybills:auto-cancel-pending')->daily();
Schedule::command('leads:generate-reorders')->daily();
Schedule::command('leads:score')->daily();
Schedule::command('leads:analyze-agents')->dailyAt('01:00');

// Customer metrics recalculation - runs at 2:00 AM daily
Schedule::job(new RecalculateCustomerMetrics())
    ->dailyAt('02:00')
    ->name('recalculate-customer-metrics')
    ->withoutOverlapping();

Schedule::command('leads:guardian-audit')->dailyAt('02:30');

// Lead recycling pool maintenance - runs at 3:00 AM daily
Schedule::call(function () {
    $poolService = app(\App\Services\RecyclingPoolService::class);
    $poolService->cleanupExpired();
    $poolService->releaseStaleAssignments(24);
})
    ->dailyAt('03:00')
    ->name('recycling-pool-cleanup');

Schedule::command('leads:snapshot-active')->dailyAt('03:30');
