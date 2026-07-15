<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEntry;

class UpdateMediaEntryAction
{
    /**
     * @param  array{status: string, watched_at?: string|null}  $data
     */
    public function handle(MediaEntry $entry, array $data): MediaEntry
    {
        $entry->update([
            'status' => $data['status'],
            'watched_at' => $data['watched_at'] ?? null,
        ]);

        return $entry;
    }
}
