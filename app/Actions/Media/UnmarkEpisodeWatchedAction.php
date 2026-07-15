<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnmarkEpisodeWatchedAction
{
    public function __construct(
        private readonly RecomputeMediaEntryStatusAction $recompute,
    ) {}

    public function handle(User $user, MediaEpisode $episode): void
    {
        DB::transaction(function () use ($user, $episode): void {
            MediaEpisodeWatch::where('user_id', $user->id)
                ->where('media_episode_id', $episode->id)
                ->delete();

            $this->recompute->handle($user, $episode->season->mediaItem);
        });
    }
}
