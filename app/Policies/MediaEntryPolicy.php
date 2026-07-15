<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MediaEntry;
use App\Models\User;

/**
 * Media tracking is purely personal — no role-based rules like Tasks has.
 * A media_entries row is only ever visible/mutable by the user it belongs to.
 */
class MediaEntryPolicy
{
    public function view(User $user, MediaEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function update(User $user, MediaEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function delete(User $user, MediaEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
