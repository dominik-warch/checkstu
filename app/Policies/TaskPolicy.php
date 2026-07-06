<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Admins/members share one pool: they may view/create/update/complete any task.
 * Guests are limited to tasks assigned to them and cannot create, edit or delete.
 * Admin-only: delete a task, and complete on behalf of another user.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
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
        return ! $user->isGuest();
    }

    public function complete(User $user, Task $task): bool
    {
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
        return $user->isAdmin();
    }

    /** A guest may touch a task only if it is (or was) assigned to them. */
    private function assignedToGuest(User $user, Task $task): bool
    {
        return $task->default_assignee_id === $user->id
            || $task->occurrences()->where('assignee_id', $user->id)->exists();
    }
}
