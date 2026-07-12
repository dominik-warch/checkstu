<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\Role;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyTaskAssignmentAction
{
    /**
     * Push-notify whoever should hear about a new/reassigned task. An
     * unassigned private task never notifies anyone — it's a plain
     * self-reminder the creator already knows about. Otherwise an explicit
     * assignee gets a direct push (this also covers a private task handed to
     * someone else — only they hear about it, never a broadcast); an
     * unassigned non-private task pushes every non-guest member (mirrors the
     * "unassigned = up for grabs" convention).
     */
    public function handle(Task $task, ?int $assigneeId): void
    {
        if ($task->is_private && $assigneeId === null) {
            return;
        }

        $recipients = $assigneeId !== null
            ? User::query()->whereKey($assigneeId)->get()
            : User::query()->where('role', '!=', Role::Guest)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TaskAssignedNotification($task));
    }
}
