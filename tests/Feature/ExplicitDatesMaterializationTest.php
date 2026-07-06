<?php

namespace Tests\Feature;

use App\Actions\Tasks\MaterializeOccurrencesAction;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExplicitDatesMaterializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_materializes_unconsumed_dates_within_the_horizon_in_order(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01'));

        $task = Task::factory()->explicitDates(['2026-07-10', '2026-07-24', '2026-09-01'])->create();

        // Horizon of 30 days should pick up the first two but not the Sept one.
        $created = app(MaterializeOccurrencesAction::class)->handle($task, Carbon::parse('2026-07-31'));

        $this->assertSame(2, $created);
        $dates = $task->occurrences()->orderBy('due_date')->pluck('due_date')
            ->map(fn ($d) => $d->toDateString())->all();
        $this->assertSame(['2026-07-10', '2026-07-24'], $dates);

        Carbon::setTestNow();
    }

    public function test_consumed_dates_are_marked_and_not_reprocessed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01'));
        $task = Task::factory()->explicitDates(['2026-07-10'])->create();

        app(MaterializeOccurrencesAction::class)->handle($task, Carbon::parse('2026-07-31'));

        $this->assertTrue($task->recurrenceDates()->first()->is_consumed);

        Carbon::setTestNow();
    }

    public function test_materialization_is_idempotent_and_does_not_crash_on_rerun(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-01'));
        $task = Task::factory()->explicitDates(['2026-07-10', '2026-07-24'])->create();

        $action = app(MaterializeOccurrencesAction::class);
        $first = $action->handle($task, Carbon::parse('2026-07-31'));
        $second = $action->handle($task, Carbon::parse('2026-07-31'));

        $this->assertSame(2, $first);
        $this->assertSame(0, $second);
        $this->assertSame(2, $task->occurrences()->count());

        Carbon::setTestNow();
    }

    public function test_past_dates_are_not_retroactively_materialized(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-15'));
        // One date already in the past relative to "today", one upcoming.
        $task = Task::factory()->explicitDates(['2026-07-01', '2026-07-20'])->create();

        $created = app(MaterializeOccurrencesAction::class)->handle($task, Carbon::parse('2026-08-01'));

        $this->assertSame(1, $created);
        $this->assertSame('2026-07-20', $task->occurrences()->first()->due_date->toDateString());
        // The past date row is left unconsumed (harmless — never queried again in range).
        $this->assertFalse(
            $task->recurrenceDates()->whereDate('due_on', '2026-07-01')->first()->is_consumed,
        );

        Carbon::setTestNow();
    }
}
