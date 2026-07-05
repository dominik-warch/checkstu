<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Managing family accounts is admin-only. Everyone may view the member list.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        // Admins may delete accounts, but not their own (avoid lockout).
        return $user->isAdmin() && $user->isNot($target);
    }
}
