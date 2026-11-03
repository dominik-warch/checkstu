<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\CompletionAction;
use App\Models\TaskCompletionLog;
use App\Models\TaskOccurrence;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Let one occurrence go without anyone getting credit: it leaves every open list, the
 * series carries on (a relative one spawns its next occurrence from today), and the log
 * records who skipped it. ReopenTaskAction undoes it.
 */
class SkipOccurrenceAction
{
    public function __construct(private readonly CompleteTaskAction $complete) {}

    public function handle(TaskOccurrence $occurrence, User $actor): TaskOccurrence
    {
        return DB::transaction(function () use ($occurrence, $actor) {
            $occurrence->forceFill(['is_skipped' => true])->save();

            TaskCompletionLog::create([
                'task_occurrence_id' => $occurrence->id,
                'task_id' => $occurrence->task_id,
                'user_id' => $actor->id,
                'acted_by_user_id' => null,
                'action' => CompletionAction::Skipped,
                'due_date' => $occurrence->due_date,
                'created_at' => now(),
            ]);

            $this->complete->spawnNextIfRelative($occurrence, now());

            return $occurrence;
        });
    }
}
