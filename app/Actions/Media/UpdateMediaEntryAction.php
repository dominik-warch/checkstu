<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;

class UpdateMediaEntryAction
{
    /**
     * @param  array{status: string}  $data
     */
    public function handle(MediaEntry $entry, array $data): MediaEntry
    {
        $entry->update(['status' => $data['status']]);

        // A completed movie always has at least one watch (left blank if no date
        // was given yet, editable via the watch-history list); leaving completed
        // status wipes them — watches only ever make sense while completed.
        if ($entry->status === WatchStatus::Completed) {
            if ($entry->watches()->doesntExist()) {
                $entry->watches()->create(['watched_at' => null]);
            }
        } else {
            $entry->watches()->delete();
        }

        return $entry;
    }
}
