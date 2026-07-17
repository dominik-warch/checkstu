<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\MediaEntry;
use Illuminate\Support\Collection;

class MediaEntryPresenter
{
    /**
     * @param  Collection<int, MediaEntry>|null  $sharedBy  other users' entries for the same media item
     * @return array<string, mixed>
     */
    public static function toArray(MediaEntry $entry, ?Collection $sharedBy = null): array
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
            'shared_by' => self::sharedByMembers($sharedBy),
        ];
    }

    /**
     * @param  Collection<int, MediaEntry>|null  $sharedBy
     * @return array<int, array<string, mixed>>
     */
    private static function sharedByMembers(?Collection $sharedBy): array
    {
        return ($sharedBy ?? collect())
            ->map(fn (MediaEntry $entry) => [
                'id' => $entry->user->id,
                'name' => $entry->user->name,
                'color' => $entry->user->color,
            ])
            ->values()
            ->all();
    }
}
