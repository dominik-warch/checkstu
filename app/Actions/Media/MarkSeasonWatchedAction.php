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
            $today = now()->toDateString();
            foreach ($airedIds as $episodeId) {
                MediaEpisodeWatch::updateOrCreate(
                    ['user_id' => $user->id, 'media_episode_id' => $episodeId],
                    ['watched_at' => $today],
                );
            }
        });

        $this->recompute->handle($user, $season->mediaItem);
    }
}
