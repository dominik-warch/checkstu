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

// Nightly SQLite snapshot (plan §12.3). Local-disk baseline — protects against a
// bad migration/accidental deletion, NOT volume loss; see the plan for the
// offsite-replication upgrade path (Litestream) once you have a bucket.
Schedule::command('sqlite:backup')->dailyAt('03:30')->withoutOverlapping();

// Daily overdue-task push reminder (plan §11). Runs after the 02:00 materialize
// pass, timed for the morning rather than a 2am buzz. Intentionally re-notifies
// each day a task stays overdue.
Schedule::command('tasks:notify-overdue')->dailyAt('08:00')->withoutOverlapping();

// Keeps season/episode data fresh for every watchlisted/watching TV show, so
// "coming up" and mark-watched (a season, or a whole show) never call TMDb
// themselves and stay fast regardless of list/season count.
Schedule::command('media:refresh-upcoming')->dailyAt('04:00')->withoutOverlapping();
