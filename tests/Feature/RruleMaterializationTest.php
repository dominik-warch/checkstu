<?php

namespace Tests\Feature;

use App\Actions\Tasks\MaterializeOccurrencesAction;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RruleMaterializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_rrule_materializes_correct_saturdays(): void
    {
        // 2026-07-04 is a Saturday.
        $anchor = Carbon::parse('2026-07-04');
        Carbon::setTestNow(Carbon::parse('2026-07-04'));

        $task = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SA')->create(['anchor_date' => $anchor]);

        $created = app(MaterializeOccurrencesAction::class)->handle($task, Carbon::parse('2026-07-25'));

        $dates = $task->occurrences()->orderBy('due_date')->pluck('due_date')
            ->map(fn ($d) => $d->toDateString())->all();

        $this->assertSame(4, $created);
        $this->assertSame(['2026-07-04', '2026-07-11', '2026-07-18', '2026-07-25'], $dates);
        foreach ($task->occurrences as $occurrence) {
            $this->assertSame('Saturday', $occurrence->due_date->format('l'));
        }

        Carbon::setTestNow();
    }

    public function test_biweekly_interval_is_anchored_to_the_true_start_not_the_window(): void
    {
        // Anchor a biweekly Monday task on 2026-06-01 (a Monday).
        $anchor = Carbon::parse('2026-06-01');
        $task = Task::factory()->rrule('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO')->create(['anchor_date' => $anchor]);

        // "Today" is well after the anchor — the interval phase must still be computed
        // from the anchor, not reset by the narrower materialization window.
        Carbon::setTestNow(Carbon::parse('2026-06-29')); // between two biweekly Mondays

        app(MaterializeOccurrencesAction::class)->handle($task, Carbon::parse('2026-07-15'));

        $dates = $task->occurrences()->orderBy('due_date')->pluck('due_date')
            ->map(fn ($d) => $d->toDateString())->all();

        // Biweekly Mondays from 2026-06-01: 06-01, 06-15, 06-29, 07-13. Window starts
        // 2026-06-29 (today), so only 06-29 and 07-13 should materialize — and both
        // land on the correct phase (not shifted to 07-06/07-20).
        $this->assertSame(['2026-06-29', '2026-07-13'], $dates);

        Carbon::setTestNow();
    }

    public function test_materialization_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04'));
        $task = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SA')->create(['anchor_date' => '2026-07-04']);

        $action = app(MaterializeOccurrencesAction::class);
        $first = $action->handle($task, Carbon::parse('2026-08-01'));
        $second = $action->handle($task, Carbon::parse('2026-08-01'));

        $this->assertGreaterThan(0, $first);
        $this->assertSame(0, $second);
        $this->assertSame($first, $task->occurrences()->count());

        Carbon::setTestNow();
    }

    public function test_weekly_sunday_task_stays_on_sunday_across_the_german_dst_boundary(): void
    {
        // Germany's DST ends last Sunday of October (2026-10-25). A weekly Sunday
        // task spanning that boundary must not skip or shift a day.
        Carbon::setTestNow(Carbon::parse('2026-10-11')); // a Sunday, 2 weeks before the DST change
        $task = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SU')->create(['anchor_date' => '2026-10-11']);

        app(MaterializeOccurrencesAction::class)->handle($task, Carbon::parse('2026-11-08'));

        $dates = $task->occurrences()->orderBy('due_date')->pluck('due_date')
            ->map(fn ($d) => $d->toDateString())->all();

        $this->assertSame(
            ['2026-10-11', '2026-10-18', '2026-10-25', '2026-11-01', '2026-11-08'],
            $dates,
        );
        foreach ($task->occurrences as $occurrence) {
            $this->assertSame('Sunday', $occurrence->due_date->format('l'));
        }

        Carbon::setTestNow();
    }

    public function test_inactive_task_is_not_materialized(): void
    {
        $task = Task::factory()->rrule()->create(['is_active' => false]);

        $created = app(MaterializeOccurrencesAction::class)->handle($task, Carbon::today()->addDays(30));

        $this->assertSame(0, $created);
        $this->assertSame(0, $task->occurrences()->count());
    }

    public function test_one_off_and_relative_tasks_are_not_touched_by_this_action(): void
    {
        $oneOff = Task::factory()->create(); // default one_off
        $relative = Task::factory()->relative(3)->create();

        $action = app(MaterializeOccurrencesAction::class);
        $this->assertSame(0, $action->handle($oneOff, Carbon::today()->addDays(30)));
        $this->assertSame(0, $action->handle($relative, Carbon::today()->addDays(30)));
    }

    public function test_recurrence_ends_on_caps_materialization(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-04'));
        $task = Task::factory()->rrule('FREQ=WEEKLY;BYDAY=SA')->create([
            'anchor_date' => '2026-07-04',
            'recurrence_ends_on' => '2026-07-11',
        ]);

        app(MaterializeOccurrencesAction::class)->handle($task, Carbon::parse('2026-09-01'));

        $dates = $task->occurrences()->orderBy('due_date')->pluck('due_date')
            ->map(fn ($d) => $d->toDateString())->all();

        $this->assertSame(['2026-07-04', '2026-07-11'], $dates);

        Carbon::setTestNow();
    }
}
