<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Admins/members share one pool; guests are limited to tasks assigned to them.
 * Private tasks are only ever visible/actionable to the person who created them
 * (this takes precedence over role — not even an admin sees another's private task).
 * Admin-only: delete shared tasks, and complete on behalf of another user.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($this->isOthersPrivate($user, $task)) {
            return false;
        }

        if ($user->isGuest()) {
            return $this->assignedToGuest($user, $task);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return ! $user->isGuest();
    }

    public function update(User $user, Task $task): bool
    {
        if ($user->isGuest()) {
            return false;
        }

        // A private task is edited only by its creator.
        if ($task->is_private) {
            return $task->created_by === $user->id;
        }

        return true;
    }

    public function complete(User $user, Task $task): bool
    {
        if ($this->isOthersPrivate($user, $task)) {
            return false;
        }

        if ($user->isGuest()) {
            return $this->assignedToGuest($user, $task);
        }

        return true;
    }

    /** Attributing a completion to someone else is an admin action. */
    public function completeOnBehalf(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Task $task): bool
    {
        // Creators manage their own private tasks regardless of role.
        if ($task->is_private) {
            return $task->created_by === $user->id;
        }

        return $user->isAdmin();
    }

    /** True when the task is private and belongs to someone else. */
    private function isOthersPrivate(User $user, Task $task): bool
    {
        return $task->is_private && $task->created_by !== $user->id;
    }

    /** A guest may touch a task only if it is (or was) assigned to them. */
    private function assignedToGuest(User $user, Task $task): bool
    {
        return $task->default_assignee_id === $user->id
            || $task->occurrences()->where('assignee_id', $user->id)->exists();
    }
}
