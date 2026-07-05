<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\CompletionAction;
use App\Enums\RecurrenceType;
use App\Models\TaskCompletionLog;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompleteTaskAction
{
    /**
     * Mark an occurrence complete, attributing it to $completedBy (defaults to the
     * occurrence assignee, else the actor). When the actor differs from the
     * attributed user, the log records who actually did it (admin-on-behalf).
     *
     * Relative tasks spawn their next occurrence here; calendar-anchored series
     * (rrule / explicit_dates) are materialized by the P2 scheduler instead.
     */
    public function handle(TaskOccurrence $occurrence, User $actor, ?User $completedBy = null): TaskOccurrence
    {
        $attributed = $completedBy
            ?? $occurrence->assignee
            ?? $actor;

        return DB::transaction(function () use ($occurrence, $actor, $attributed) {
            $occurrence->forceFill([
                'completed_at' => now(),
                'completed_by' => $attributed->id,
            ])->save();

            TaskCompletionLog::create([
                'task_occurrence_id' => $occurrence->id,
                'task_id' => $occurrence->task_id,
                'user_id' => $attributed->id,
                'acted_by_user_id' => $attributed->is($actor) ? null : $actor->id,
                'action' => CompletionAction::Completed,
                'due_date' => $occurrence->due_date,
                'created_at' => now(),
            ]);

            $this->spawnNextIfRelative($occurrence);

            return $occurrence;
        });
    }

    private function spawnNextIfRelative(TaskOccurrence $occurrence): void
    {
        $task = $occurrence->task;

        if (! $task->is_active || $task->recurrence_type !== RecurrenceType::Relative) {
            return;
        }

        $interval = $task->relative_interval_days;
        if ($interval === null || $interval < 1) {
            return;
        }

        $next = Carbon::parse($occurrence->completed_at)->addDays($interval)->toDateString();

        if ($task->recurrence_ends_on !== null && $next > $task->recurrence_ends_on->toDateString()) {
            return;
        }

        // Idempotent against the unique (task_id, due_date) index.
        $task->occurrences()->firstOrCreate(
            ['due_date' => $next],
            ['assignee_id' => $task->default_assignee_id],
        );
    }
}
