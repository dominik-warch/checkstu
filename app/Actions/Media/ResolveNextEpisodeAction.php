<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\MediaEpisodeWatch;
use App\Models\MediaItem;
use App\Models\User;

class ResolveNextEpisodeAction
{
    public function __construct(
        private readonly RefreshShowSeasonsAction $refreshSeasons,
        private readonly FetchSeasonEpisodesAction $fetchEpisodes,
    ) {}

    /**
     * Resolves the next unwatched aired episode for the Media home widget,
     * fetching whatever's missing along the way — a season's episodes if it
     * hasn't been expanded yet, or (as a last-resort fallback, only once
     * nothing turns up in what's already cached) the season *list* itself, in
     * case the very next episode is the first one of a season that hasn't
     * been discovered at all yet. Both are cheap, request-time-only TMDb
     * calls — no scheduled job, and the common case (next episode already in
     * a cached season) costs zero extra calls.
     *
     * @return array{id: int, season_number: int, episode_number: int, name: string}|null
     */
    public function handle(User $user, MediaItem $item): ?array
    {
        $next = $this->scan($item, $user);
        if ($next !== null) {
            return $next;
        }

        $this->refreshSeasons->handle($item);
        $item->load('seasons.episodes');

        return $this->scan($item, $user);
    }

    /**
     * @return array{id: int, season_number: int, episode_number: int, name: string}|null
     */
    private function scan(MediaItem $item, User $user): ?array
    {
        foreach ($item->seasons as $season) {
            if ($season->season_number === 0) {
                continue;
            }

            if (! $season->isEpisodesCached()) {
                $this->fetchEpisodes->handle($season);
                $season->load('episodes');
            }

            $watchedIds = MediaEpisodeWatch::where('user_id', $user->id)
                ->whereIn('media_episode_id', $season->episodes->pluck('id'))
                ->pluck('media_episode_id')
                ->all();

            foreach ($season->episodes as $episode) {
                if ($episode->hasAired() && ! in_array($episode->id, $watchedIds, true)) {
                    return [
                        'id' => $episode->id,
                        'season_number' => $season->season_number,
                        'episode_number' => $episode->episode_number,
                        'name' => $episode->name,
                    ];
                }
            }
        }

        return null;
    }
}
