<?php

declare(strict_types=1);

namespace App\Actions\Tasks;

use App\Enums\Role;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class NotifyOverdueOccurrencesAction
{
    /**
     * Push-notify about every currently-overdue occurrence: the assignee gets
     * a batched push listing their own overdue tasks (this safely includes
     * private ones handed to them — it only ever goes to that one person, and
     * the notification body never reveals a private task's title); occurrences
     * left unassigned push every non-guest member (same "up for grabs" rule as
     * task-assignment pushes) — but a private task can never be unassigned AND
     * broadcast, so that bucket excludes private tasks entirely. Runs daily and
     * intentionally re-notifies for as long as a task stays overdue.
     */
    public function handle(): void
    {
        $overdue = TaskOccurrence::query()
            ->whereNull('completed_at')
            ->where('is_skipped', false)
            ->whereDate('due_date', '<', Carbon::today())
            ->with('task')
            ->get();

        if ($overdue->isEmpty()) {
            return;
        }

        $assigned = $overdue->whereNotNull('assignee_id')->groupBy('assignee_id');
        foreach ($assigned as $assigneeId => $occurrences) {
            $user = User::find($assigneeId);
            if ($user !== null) {
                Notification::send($user, new TaskOverdueNotification($occurrences));
            }
        }

        $unassigned = $overdue->whereNull('assignee_id')->reject(fn (TaskOccurrence $o) => $o->task->is_private);
        if ($unassigned->isNotEmpty()) {
            $recipients = User::query()->where('role', '!=', Role::Guest)->get();
            Notification::send($recipients, new TaskOverdueNotification($unassigned));
        }
    }
}
