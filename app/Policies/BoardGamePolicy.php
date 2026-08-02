<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BoardGame;
use App\Models\User;

/**
 * The board game catalogue is shared by the whole household — no privacy
 * dimension like Tasks/private tasks. Only removing a game (which cascades
 * its play history) is admin-only, mirroring Tasks' admin-only delete.
 */
class BoardGamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BoardGame $game): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, BoardGame $game): bool
    {
        return true;
    }

    public function delete(User $user, BoardGame $game): bool
    {
        return $user->isAdmin();
    }
}
