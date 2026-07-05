<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * One shared space: any signed-in user may view/create/update/complete tasks.
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
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return true;
    }

    public function complete(User $user, Task $task): bool
    {
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
}
