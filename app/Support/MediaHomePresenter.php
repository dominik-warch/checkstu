<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaEpisodeWatch;
use App\Models\MediaItem;
use App\Models\User;

class MediaHomePresenter
{
    /**
     * For every show the user is currently `watching`, resolve the lowest
     * uncompleted episode among CACHED episodes only. If the relevant season
     * hasn't been fetched yet, `next_episode` is null and the frontend shows a
     * "continue watching" link to the show page instead of silently omitting
     * it — opening that page lazily fetches the season.
     *
     * @return list<array<string, mixed>>
     */
    public static function nextEpisodes(User $user): array
    {
        $entries = MediaEntry::query()
            ->where('user_id', $user->id)
            ->where('status', WatchStatus::Watching)
            ->with('mediaItem.seasons.episodes')
            ->get();

        return $entries->map(function (MediaEntry $entry) use ($user) {
            $item = $entry->mediaItem;

            return [
                'media_item' => [
                    'id' => $item->id,
                    'tmdb_id' => $item->tmdb_id,
                    'type' => $item->type->value,
                    'title_de' => $item->title_de,
                    'title_en' => $item->title_en,
                    'poster_path' => $item->poster_path,
                    'release_date' => $item->release_date?->toDateString(),
                ],
                'next_episode' => self::findNextEpisode($item, $user),
            ];
        })->values()->all();
    }

    /**
     * @return array{season_number: int, episode_number: int, name: string}|null
     */
    private static function findNextEpisode(MediaItem $item, User $user): ?array
    {
        $episodeIds = $item->seasons->flatMap(fn ($season) => $season->episodes)->pluck('id');
        $watchedIds = MediaEpisodeWatch::where('user_id', $user->id)
            ->whereIn('media_episode_id', $episodeIds)
            ->pluck('media_episode_id')
            ->all();

        foreach ($item->seasons as $season) {
            if ($season->season_number === 0) {
                continue;
            }

            foreach ($season->episodes as $episode) {
                if ($episode->hasAired() && ! in_array($episode->id, $watchedIds, true)) {
                    return [
                        'season_number' => $season->season_number,
                        'episode_number' => $episode->episode_number,
                        'name' => $episode->name,
                    ];
                }
            }
        }

        return null;
    }
}
