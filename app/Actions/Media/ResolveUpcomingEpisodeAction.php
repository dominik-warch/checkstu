<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaItem;
use App\Models\MediaSeason;

class ResolveUpcomingEpisodeAction
{
    /**
     * Resolves the next not-yet-aired episode for the "coming up" view. Unlike
     * ResolveNextEpisodeAction (which scans every season looking for something
     * already aired but unwatched), an upcoming episode can only realistically
     * be in the MOST RECENT known season — a show airs season by season, so
     * there's no point looking at every earlier season just to confirm they're
     * all in the past.
     *
     * Reads purely from already-cached season/episode data — RefreshUpcomingCacheAction
     * (run nightly, and once when a show is added) is what keeps that cache fresh, so
     * this never calls TMDb itself and stays cheap on every "coming up" page load.
     *
     * @return array{id: int, season_number: int, episode_number: int, name: string, air_date: string}|null
     */
    public function handle(MediaItem $item): ?array
    {
        /** @var MediaSeason|null $season */
        $season = $item->seasons->where('season_number', '!=', 0)->last();
        if ($season === null) {
            return null;
        }

        foreach ($season->episodes as $episode) {
            if ($episode->isUpcoming()) {
                return [
                    'id' => $episode->id,
                    'season_number' => $season->season_number,
                    'episode_number' => $episode->episode_number,
                    'name' => $episode->name,
                    'air_date' => $episode->air_date->toDateString(),
                ];
            }
        }

        return null;
    }
}
