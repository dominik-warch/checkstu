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
     *
     * Returns the ids of the watches it newly created (episodes already watched are left
     * alone and not included), so the undo toast can remove exactly those again.
     *
     * @return list<int>
     */
    public function handle(User $user, MediaItem $item): array
    {
        $created = [];

        foreach ($item->seasons as $season) {
            $episodes = $this->fetchEpisodes->handle($season);
            $airedIds = $episodes->filter(fn (MediaEpisode $episode) => $episode->hasAired())->pluck('id');

            DB::transaction(function () use ($user, $airedIds, &$created): void {
                foreach ($airedIds as $episodeId) {
                    // firstOrCreate, not updateOrCreate — an already-watched episode (however
                    // many times, and whenever) must be left alone, not have its date wiped.
                    // No known watch date for a genuinely new one — left blank rather than
                    // defaulting to today, which would misrepresent when it was actually watched.
                    $watch = MediaEpisodeWatch::firstOrCreate(
                        ['user_id' => $user->id, 'media_episode_id' => $episodeId],
                        ['watched_at' => null],
                    );
                    if ($watch->wasRecentlyCreated) {
                        $created[] = $watch->id;
                    }
                }
            });
        }

        $this->recompute->handle($user, $item);

        return $created;
    }
}
