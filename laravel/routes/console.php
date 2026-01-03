<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('waybills:auto-cancel-pending')->daily();
Schedule::command('leads:generate-reorders')->daily();
Schedule::command('leads:score')->daily();
