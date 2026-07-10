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

class ReopenTaskAction
{
    /**
     * Undo an accidental completion: clear completed_at/completed_by and log a
     * "reopened" entry attributed the same way completion logs are (user_id =
     * who it's attributed to, acted_by_user_id = actor if different).
     *
     * For a relative task, completing it immediately spawns its next occurrence
     * (see CompleteTaskAction::spawnNextIfRelative). Reopening removes that
     * spawned occurrence too, but only if it's still untouched — otherwise the
     * series would be left with two open occurrences until one is resolved.
     */
    public function handle(TaskOccurrence $occurrence, User $actor): TaskOccurrence
    {
        return DB::transaction(function () use ($occurrence, $actor) {
            $attributed = $occurrence->completedBy ?? $actor;
            $completedAt = $occurrence->completed_at;

            $occurrence->forceFill([
                'completed_at' => null,
                'completed_by' => null,
            ])->save();

            TaskCompletionLog::create([
                'task_occurrence_id' => $occurrence->id,
                'task_id' => $occurrence->task_id,
                'user_id' => $attributed->id,
                'acted_by_user_id' => $attributed->is($actor) ? null : $actor->id,
                'action' => CompletionAction::Reopened,
                'due_date' => $occurrence->due_date,
                'created_at' => now(),
            ]);

            $this->removeSpawnedNextIfUntouched($occurrence, $completedAt);

            return $occurrence;
        });
    }

    private function removeSpawnedNextIfUntouched(TaskOccurrence $occurrence, ?Carbon $completedAt): void
    {
        $task = $occurrence->task;

        if ($completedAt === null || $task->recurrence_type !== RecurrenceType::Relative) {
            return;
        }

        $interval = $task->relative_interval_days;
        if ($interval === null || $interval < 1) {
            return;
        }

        $spawnedDue = $completedAt->copy()->addDays($interval);

        $task->occurrences()
            ->whereDate('due_date', $spawnedDue)
            ->whereNull('completed_at')
            ->where('is_skipped', false)
            ->delete();
    }
}
