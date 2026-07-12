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
     * Push-notify whoever should hear about a new/reassigned task. Private
     * tasks never notify — only the creator can ever see them. An explicit
     * assignee gets a direct push; an unassigned task pushes every non-guest
     * member (mirrors the "unassigned = up for grabs" convention).
     */
    public function handle(Task $task, ?int $assigneeId): void
    {
        if ($task->is_private) {
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
