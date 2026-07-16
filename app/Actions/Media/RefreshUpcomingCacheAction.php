<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\MediaItem;

/**
 * Warms the season-list and last-season-episode cache for a TV item, so
 * ResolveUpcomingEpisodeAction can resolve the "coming up" page purely from
 * already-cached data and never call TMDb itself. Run nightly for every show
 * on a watchlist (media:refresh-upcoming), and once when a show is added.
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

        $season = $item->seasons->where('season_number', '!=', 0)->last();
        if ($season !== null) {
            $this->fetchEpisodes->handle($season);
        }
    }
}
