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

    public function handle(User $user, MediaEpisode $episode, ?string $watchedAt = null): void
    {
        DB::transaction(function () use ($user, $episode, $watchedAt): void {
            MediaEpisodeWatch::updateOrCreate(
                ['user_id' => $user->id, 'media_episode_id' => $episode->id],
                ['watched_at' => $watchedAt ?? now()->toDateString()],
            );

            $this->recompute->handle($user, $episode->season->mediaItem);
        });
    }
}
