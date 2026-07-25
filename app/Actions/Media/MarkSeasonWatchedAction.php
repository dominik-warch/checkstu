<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use App\Models\MediaSeason;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkSeasonWatchedAction
{
    public function __construct(
        private readonly FetchSeasonEpisodesAction $fetchEpisodes,
        private readonly RecomputeMediaEntryStatusAction $recompute,
    ) {}

    /** Marks every already-aired episode of a season watched — fetching it first if not yet cached. */
    public function handle(User $user, MediaSeason $season): void
    {
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

        $this->recompute->handle($user, $season->mediaItem);
    }
}
