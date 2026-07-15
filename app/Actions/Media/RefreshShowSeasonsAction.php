<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\MediaItem;
use App\Support\Tmdb\TmdbClient;

class RefreshShowSeasonsAction
{
    private const ENDED_STATUSES = ['Ended', 'Canceled'];

    public function __construct(
        private readonly TmdbClient $tmdb,
    ) {}

    /**
     * Re-syncs the season *list* (not episodes) for a still-running show every
     * time its detail page is opened, so a newly aired season is discovered
     * without a scheduled background job. A no-op for movies and for shows
     * TMDb already reports as finished.
     */
    public function handle(MediaItem $item): void
    {
        if ($item->type !== MediaType::Tv || in_array($item->tv_status, self::ENDED_STATUSES, true)) {
            return;
        }

        $details = $this->tmdb->details($item->tmdb_id, MediaType::Tv);

        $item->update([
            'title_de' => $details['title_de'],
            'title_en' => $details['title_en'],
            'overview' => $details['overview'],
            'poster_path' => $details['poster_path'],
            'tv_status' => $details['tv_status'],
        ]);

        foreach ($details['seasons'] as $season) {
            $item->seasons()->updateOrCreate(
                ['season_number' => $season['season_number']],
                [
                    'tmdb_season_id' => $season['tmdb_season_id'],
                    'name' => $season['name'],
                    'episode_count' => $season['episode_count'],
                    'air_date' => $season['air_date'],
                ],
            );
        }
    }
}
