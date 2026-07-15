<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEpisode;
use App\Models\MediaSeason;
use App\Support\Tmdb\TmdbClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FetchSeasonEpisodesAction
{
    private const TTL_HOURS = 24;

    public function __construct(
        private readonly TmdbClient $tmdb,
    ) {}

    /**
     * Episodes for a season are fetched lazily, on first expand in the UI —
     * not eagerly for every season when a show is added. Cached indefinitely
     * until the TTL elapses (a season that already aired fully never changes,
     * but a currently-airing one needs to pick up newly aired episodes).
     *
     * @return Collection<int, MediaEpisode>
     */
    public function handle(MediaSeason $season): Collection
    {
        $isStale = $season->episodes_fetched_at === null
            || $season->episodes_fetched_at->lt(now()->subHours(self::TTL_HOURS));

        if (! $isStale) {
            return $season->episodes()->get();
        }

        $episodes = $this->tmdb->seasonEpisodes($season->mediaItem->tmdb_id, $season->season_number);

        DB::transaction(function () use ($season, $episodes): void {
            foreach ($episodes as $episode) {
                $season->episodes()->updateOrCreate(
                    ['episode_number' => $episode['episode_number']],
                    ['tmdb_episode_id' => $episode['tmdb_episode_id'], 'name' => $episode['name'], 'air_date' => $episode['air_date']],
                );
            }
            $season->update(['episodes_fetched_at' => now()]);
        });

        return $season->episodes()->get();
    }
}
