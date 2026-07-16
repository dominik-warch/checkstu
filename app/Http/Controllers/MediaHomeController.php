<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\ResolveNextEpisodeAction;
use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaEpisodeWatch;
use App\Models\User;
use App\Support\MediaHomePresenter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MediaHomeController extends Controller
{
    public function index(Request $request, ResolveNextEpisodeAction $resolveNextEpisode): Response
    {
        $user = $request->user();

        $entries = MediaEntry::query()
            ->where('user_id', $user->id)
            ->where('status', WatchStatus::Watching)
            ->with('mediaItem.seasons.episodes')
            ->get();

        $entries = $this->sortByLastWatched($entries, $user);

        // A show with nothing aired-and-unwatched right now (caught up, next
        // episode not out yet) has no place on a "next episode" list — it
        // would only ever show a "continue watching" placeholder with nothing
        // to actually watch.
        $nextEpisodes = $entries
            ->map(fn (MediaEntry $entry) => [
                'media_item' => MediaHomePresenter::mediaItemSummary($entry->mediaItem),
                'next_episode' => $resolveNextEpisode->handle($user, $entry->mediaItem),
            ])
            ->filter(fn (array $row) => $row['next_episode'] !== null)
            ->values()
            ->all();

        return Inertia::render('media/home', [
            'nextEpisodes' => $nextEpisodes,
        ]);
    }

    /**
     * Most-recently-watched-episode first, so the show you last checked off
     * stays at the top instead of being ordered arbitrarily.
     *
     * @param  Collection<int, MediaEntry>  $entries
     * @return Collection<int, MediaEntry>
     */
    private function sortByLastWatched(Collection $entries, User $user): Collection
    {
        $episodeToItem = [];
        foreach ($entries as $entry) {
            foreach ($entry->mediaItem->seasons as $season) {
                foreach ($season->episodes as $episode) {
                    $episodeToItem[$episode->id] = $entry->media_item_id;
                }
            }
        }

        /** @var array<int, Carbon> $lastWatchedByItem */
        $lastWatchedByItem = [];
        if ($episodeToItem !== []) {
            MediaEpisodeWatch::where('user_id', $user->id)
                ->whereIn('media_episode_id', array_keys($episodeToItem))
                ->get(['media_episode_id', 'updated_at'])
                ->each(function (MediaEpisodeWatch $watch) use ($episodeToItem, &$lastWatchedByItem): void {
                    $itemId = $episodeToItem[$watch->media_episode_id];
                    if (! isset($lastWatchedByItem[$itemId]) || $watch->updated_at->gt($lastWatchedByItem[$itemId])) {
                        $lastWatchedByItem[$itemId] = $watch->updated_at;
                    }
                });
        }

        return $entries->sortByDesc(
            fn (MediaEntry $entry) => $lastWatchedByItem[$entry->media_item_id]?->timestamp ?? 0,
        )->values();
    }
}
