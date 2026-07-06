<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\RecurrenceType;
use App\Models\Task;
use App\Support\Recurrence\RruleExpander;
use Illuminate\Support\Carbon;

/**
 * Materializes concrete TaskOccurrence rows for calendar-anchored recurrence
 * (rrule, explicit_dates) up to a rolling horizon. Idempotent: a whereDate()
 * existence check before each create, backed by the unique(task_id, due_date)
 * index, so re-running never duplicates — safe to call from the nightly
 * scheduler and right after a task is created/edited.
 *
 * Completion-anchored (`relative`) tasks are NOT handled here — those spawn
 * their next occurrence on completion (see CompleteTaskAction).
 */
class MaterializeOccurrencesAction
{
    private const DEFAULT_HORIZON_DAYS = 60;

    public function __construct(private readonly RruleExpander $expander) {}

    /**
     * @return int number of newly created occurrences
     */
    public function handle(Task $task, ?Carbon $horizonEnd = null): int
    {
        if (! $task->is_active) {
            return 0;
        }

        $horizonEnd ??= Carbon::today()->addDays(self::DEFAULT_HORIZON_DAYS);

        if ($task->recurrence_ends_on !== null && $horizonEnd->gt($task->recurrence_ends_on)) {
            $horizonEnd = $task->recurrence_ends_on->copy();
        }

        return match ($task->recurrence_type) {
            RecurrenceType::Rrule => $this->materializeRrule($task, $horizonEnd),
            RecurrenceType::ExplicitDates => $this->materializeExplicitDates($task, $horizonEnd),
            default => 0,
        };
    }

    private function materializeRrule(Task $task, Carbon $horizonEnd): int
    {
        if (! $task->rrule || ! $task->anchor_date) {
            return 0;
        }

        $windowStart = Carbon::today()->max($task->anchor_date);
        if ($windowStart->gt($horizonEnd)) {
            return 0;
        }

        $dates = $this->expander->datesBetween($task->rrule, $task->anchor_date, $windowStart, $horizonEnd);

        $created = 0;
        foreach ($dates as $date) {
            // NOTE: can't use firstOrCreate(['due_date' => ...]) here — Eloquent's `date`
            // cast serializes to a full datetime string on write (e.g. "2026-07-04
            // 00:00:00"), but a raw where() array compares against the plain date
            // string and never matches, so a re-run would crash on the unique index
            // instead of no-op'ing. whereDate() normalizes both sides correctly.
            $exists = $task->occurrences()->whereDate('due_date', $date)->exists();
            if ($exists) {
                continue;
            }

            $task->occurrences()->create([
                'due_date' => $date->toDateString(),
                'assignee_id' => $task->default_assignee_id,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Irregular real-world schedules (e.g. municipal garbage pickup): consume
     * explicit due dates in order, only from today forward (a date that passed
     * before anyone looked is not retroactively materialized). Each date row is
     * one-shot — once its occurrence is created it's marked consumed so it's
     * never reconsidered, independent of whether/when that occurrence is later
     * completed.
     */
    private function materializeExplicitDates(Task $task, Carbon $horizonEnd): int
    {
        $dueRows = $task->recurrenceDates()
            ->where('is_consumed', false)
            ->whereDate('due_on', '>=', Carbon::today())
            ->whereDate('due_on', '<=', $horizonEnd)
            ->orderBy('due_on')
            ->get();

        $created = 0;
        foreach ($dueRows as $dueRow) {
            $date = $dueRow->due_on;

            $exists = $task->occurrences()->whereDate('due_date', $date)->exists();
            if (! $exists) {
                $task->occurrences()->create([
                    'due_date' => $date->toDateString(),
                    'assignee_id' => $task->default_assignee_id,
                ]);
                $created++;
            }

            $dueRow->update(['is_consumed' => true]);
        }

        return $created;
    }
}
