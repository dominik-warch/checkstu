<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkEpisodeWatchedAction
{
    public function __construct(
        private readonly RecomputeMediaEntryStatusAction $recompute,
    ) {}

    /**
     * Always adds a new watch row — an episode can be watched more than once,
     * so this both marks it watched the first time (via the checkbox) and logs
     * an additional watch (via the watch-history list), depending on the caller.
     */
    public function handle(User $user, MediaEpisode $episode, ?string $watchedAt = null): void
    {
        DB::transaction(function () use ($user, $episode, $watchedAt): void {
            MediaEpisodeWatch::create([
                'user_id' => $user->id,
                'media_episode_id' => $episode->id,
                'watched_at' => $watchedAt ?? now()->toDateString(),
            ]);

            $this->recompute->handle($user, $episode->season->mediaItem);
        });
    }
}
