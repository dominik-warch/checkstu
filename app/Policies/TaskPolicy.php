<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Admins/members share one pool; guests are limited to tasks assigned to them.
 * A private task always has an assignee (enforced at validation) and is
 * visible/actionable ONLY to that assignee — not even its own creator, and not
 * even an admin. Assigning it to yourself is how you make a plain
 * self-reminder; assigning it to someone else hands it off completely
 * ("give someone a private task, everyone else including you is blind to it").
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

        if ($task->is_private) {
            return $this->ownsPrivateTask($user, $task);
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
        if ($task->is_private) {
            return $this->ownsPrivateTask($user, $task);
        }

        return $user->isAdmin();
    }

    /** True when the task is private and the user is not its current owner. */
    private function isOthersPrivate(User $user, Task $task): bool
    {
        return $task->is_private && ! $this->ownsPrivateTask($user, $task);
    }

    /** Only a private task's assignee may see/act on it — full stop. */
    private function ownsPrivateTask(User $user, Task $task): bool
    {
        return $task->default_assignee_id === $user->id;
    }

    /** A guest may touch a task only if it is (or was) assigned to them. */
    private function assignedToGuest(User $user, Task $task): bool
    {
        return $task->default_assignee_id === $user->id
            || $task->occurrences()->where('assignee_id', $user->id)->exists();
    }
}
