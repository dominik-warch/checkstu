<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\MediaItem;

class MediaHomePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function mediaItemSummary(MediaItem $item): array
    {
        return [
            'id' => $item->id,
            'tmdb_id' => $item->tmdb_id,
            'type' => $item->type->value,
            'title_de' => $item->title_de,
            'title_en' => $item->title_en,
            'poster_path' => $item->poster_path,
            'release_date' => $item->release_date?->toDateString(),
        ];
    }
}
