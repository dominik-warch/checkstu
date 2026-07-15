<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEntry;

class DeleteMediaEntryAction
{
    /**
     * Removes the entry only — episode-watch history is intentionally kept
     * (no FK from media_episode_watches to media_entries), so re-adding the
     * same show later picks up right where progress left off.
     */
    public function handle(MediaEntry $entry): void
    {
        $entry->delete();
    }
}
