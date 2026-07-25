<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkAllEpisodesWatchedAction
{
    public function __construct(
        private readonly FetchSeasonEpisodesAction $fetchEpisodes,
        private readonly RecomputeMediaEntryStatusAction $recompute,
    ) {}

    /**
     * Marks every already-aired episode across every season watched — fetching
     * any season that isn't cached yet along the way. HTTP calls (one per
     * uncached season, via FetchSeasonEpisodesAction) happen outside any DB
     * transaction; each season's episode-watch upserts are batched into their
     * own short transaction, and the entry status is recomputed once at the end.
     */
    public function handle(User $user, MediaItem $item): void
    {
        foreach ($item->seasons as $season) {
            $episodes = $this->fetchEpisodes->handle($season);
            $airedIds = $episodes->filter(fn (MediaEpisode $episode) => $episode->hasAired())->pluck('id');

            DB::transaction(function () use ($user, $airedIds): void {
                foreach ($airedIds as $episodeId) {
                    // No known watch date for a bulk mark — left blank rather than
                    // defaulting to today, which would misrepresent when it was actually watched.
                    MediaEpisodeWatch::updateOrCreate(
                        ['user_id' => $user->id, 'media_episode_id' => $episodeId],
                        ['watched_at' => null],
                    );
                }
            });
        }

        $this->recompute->handle($user, $item);
    }
}
