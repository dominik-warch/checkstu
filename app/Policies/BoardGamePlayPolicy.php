<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BoardGamePlay;
use App\Models\User;

/**
 * Every family member can view/log plays. Editing or deleting an existing
 * play is limited to whoever logged it, or an admin — so kids can fix their
 * own typo'd entry but can't rewrite someone else's game night.
 */
class BoardGamePlayPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BoardGamePlay $play): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BoardGamePlay $play): bool
    {
        return $user->isAdmin() || $play->logged_by === $user->id;
    }

    public function delete(User $user, BoardGamePlay $play): bool
    {
        return $this->update($user, $play);
    }
}
