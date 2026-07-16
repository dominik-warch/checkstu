<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\MediaEntry;

class MediaEntryPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(MediaEntry $entry): array
    {
        $item = $entry->mediaItem;

        return [
            'kind' => 'media',
            'id' => $entry->id,
            'status' => $entry->status->value,
            'watched_at' => $entry->watched_at?->toDateString(),
            'media_item' => [
                'id' => $item->id,
                'tmdb_id' => $item->tmdb_id,
                'type' => $item->type->value,
                'title_de' => $item->title_de,
                'title_en' => $item->title_en,
                'poster_path' => $item->poster_path,
                'release_date' => $item->release_date?->toDateString(),
            ],
        ];
    }
}
