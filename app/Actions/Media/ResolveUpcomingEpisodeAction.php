<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaItem;
use App\Models\MediaSeason;

class ResolveUpcomingEpisodeAction
{
    public function __construct(
        private readonly RefreshShowSeasonsAction $refreshSeasons,
        private readonly FetchSeasonEpisodesAction $fetchEpisodes,
    ) {}

    /**
     * Resolves the next not-yet-aired episode for the "coming up" view. Unlike
     * ResolveNextEpisodeAction (which scans every season looking for something
     * already aired but unwatched), an upcoming episode can only realistically
     * be in the MOST RECENT known season — a show airs season by season, so
     * there's no point fetching every earlier season just to confirm they're
     * all in the past. If that last season has nothing upcoming (fully aired,
     * or the season itself hasn't started), refresh the season list once as a
     * last resort in case a newer season has since been announced.
     *
     * @return array{id: int, season_number: int, episode_number: int, name: string, air_date: string}|null
     */
    public function handle(MediaItem $item): ?array
    {
        $upcoming = $this->checkLastSeason($item);
        if ($upcoming !== null) {
            return $upcoming;
        }

        $this->refreshSeasons->handle($item);
        $item->load('seasons.episodes');

        return $this->checkLastSeason($item);
    }

    /**
     * @return array{id: int, season_number: int, episode_number: int, name: string, air_date: string}|null
     */
    private function checkLastSeason(MediaItem $item): ?array
    {
        /** @var MediaSeason|null $season */
        $season = $item->seasons->where('season_number', '!=', 0)->last();
        if ($season === null) {
            return null;
        }

        if (! $season->isEpisodesCached()) {
            $this->fetchEpisodes->handle($season);
            $season->load('episodes');
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
