<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\MediaItem;

/**
 * Warms the season-list and every season's episode cache for a TV item, so
 * ResolveUpcomingEpisodeAction ("coming up") and the mark-watched actions
 * (a season, or a whole show) can all work purely from already-cached data
 * and never call TMDb themselves. Run nightly for every show on a watchlist
 * (media:refresh-upcoming), and once when a show is added.
 */
class RefreshUpcomingCacheAction
{
    public function __construct(
        private readonly RefreshShowSeasonsAction $refreshSeasons,
        private readonly FetchSeasonEpisodesAction $fetchEpisodes,
    ) {}

    public function handle(MediaItem $item): void
    {
        if ($item->type !== MediaType::Tv) {
            return;
        }

        $this->refreshSeasons->handle($item);
        $item->load('seasons');

        foreach ($item->seasons->where('season_number', '!=', 0) as $season) {
            $this->fetchEpisodes->handle($season);
        }
    }
}
