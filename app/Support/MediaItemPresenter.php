<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\MediaEntry;
use App\Models\MediaEpisodeWatch;
use App\Models\MediaItem;
use App\Models\MediaSeason;
use App\Models\User;

class MediaItemPresenter
{
    /**
     * Shape a TV/movie item + the current user's progress for the detail page.
     * Assumes `seasons.episodes` is already eager-loaded on $item.
     *
     * @return array<string, mixed>
     */
    public static function detail(MediaItem $item, ?MediaEntry $entry, User $user): array
    {
        return [
            'id' => $item->id,
            'tmdb_id' => $item->tmdb_id,
            'type' => $item->type->value,
            'title_de' => $item->title_de,
            'title_en' => $item->title_en,
            'overview' => $item->overview,
            'poster_path' => $item->poster_path,
            'release_date' => $item->release_date?->toDateString(),
            'tv_status' => $item->tv_status,
            'entry' => $entry ? [
                'id' => $entry->id,
                'status' => $entry->status->value,
                'watched_at' => $entry->watched_at?->toDateString(),
            ] : null,
            'seasons' => $item->seasons->map(function (MediaSeason $season) use ($user) {
                $isCached = $season->isEpisodesCached();

                return [
                    'id' => $season->id,
                    'season_number' => $season->season_number,
                    'name' => $season->name,
                    'episode_count' => $season->episode_count,
                    'is_cached' => $isCached,
                    'watched_count' => $isCached
                        ? MediaEpisodeWatch::where('user_id', $user->id)
                            ->whereIn('media_episode_id', $season->episodes->pluck('id'))
                            ->count()
                        : 0,
                ];
            })->values()->all(),
        ];
    }
}
