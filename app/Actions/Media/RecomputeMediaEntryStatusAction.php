<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use App\Models\MediaItem;
use App\Models\MediaSeason;
use App\Models\User;

class RecomputeMediaEntryStatusAction
{
    /**
     * Re-derives a show's watchlist/watching/completed status from actual
     * episode-watch rows. Specials (season_number = 0) are excluded from both
     * the "must be cached" and "must be watched" checks. `completed` is a pure
     * snapshot — it can be reached again later if a new season airs, and it
     * demotes symmetrically all the way back to `watchlist` at zero watched
     * episodes.
     */
    public function handle(User $user, MediaItem $item): MediaEntry
    {
        $trackableSeasons = $item->seasons()
            ->where('season_number', '!=', 0)
            ->where('episode_count', '>', 0)
            ->with('episodes')
            ->get();

        $allCached = $trackableSeasons->every(fn (MediaSeason $season) => $season->isEpisodesCached());

        $airedEpisodeIds = $trackableSeasons
            ->flatMap(fn (MediaSeason $season) => $season->episodes)
            ->filter(fn (MediaEpisode $episode) => $episode->hasAired())
            ->pluck('id');

        $watchedAiredCount = MediaEpisodeWatch::where('user_id', $user->id)
            ->whereIn('media_episode_id', $airedEpisodeIds)
            ->count();

        $status = match (true) {
            $watchedAiredCount === 0 => WatchStatus::Watchlist,
            $allCached && $airedEpisodeIds->isNotEmpty() && $watchedAiredCount === $airedEpisodeIds->count() => WatchStatus::Completed,
            default => WatchStatus::Watching,
        };

        $entry = MediaEntry::firstOrNew(['user_id' => $user->id, 'media_item_id' => $item->id]);
        $wasCompleted = $entry->exists && $entry->status === WatchStatus::Completed;

        $entry->status = $status;
        $entry->watched_at = match (true) {
            $status === WatchStatus::Completed && ! $wasCompleted => now()->toDateString(),
            $status === WatchStatus::Completed && $wasCompleted => $entry->watched_at,
            default => null,
        };
        $entry->save();

        return $entry;
    }
}
