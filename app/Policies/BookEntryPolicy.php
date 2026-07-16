<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BookEntry;
use App\Models\User;

/** Mirrors MediaEntryPolicy — book tracking is just as personal as media tracking. */
class BookEntryPolicy
{
    public function view(User $user, BookEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function update(User $user, BookEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function delete(User $user, BookEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
