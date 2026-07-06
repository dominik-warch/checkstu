<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rolling-horizon materialization for rrule/explicit_dates tasks (plan §4.4).
// withoutOverlapping guards against a slow run still executing at the next tick.
Schedule::command('tasks:materialize')->dailyAt('02:00')->withoutOverlapping();
