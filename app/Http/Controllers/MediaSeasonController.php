<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\FetchSeasonEpisodesAction;
use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use App\Models\MediaSeason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaSeasonController extends Controller
{
    /** Lazy fetch-on-expand: episodes for a season are only loaded when the UI asks for them. */
    public function episodes(Request $request, MediaSeason $season, FetchSeasonEpisodesAction $fetch): JsonResponse
    {
        $episodes = $fetch->handle($season);

        $watchedIds = MediaEpisodeWatch::where('user_id', $request->user()->id)
            ->whereIn('media_episode_id', $episodes->pluck('id'))
            ->pluck('media_episode_id')
            ->all();

        return response()->json([
            'episodes' => $episodes->map(fn (MediaEpisode $episode) => [
                'id' => $episode->id,
                'episode_number' => $episode->episode_number,
                'name' => $episode->name,
                'air_date' => $episode->air_date?->toDateString(),
                'watched' => in_array($episode->id, $watchedIds, true),
            ])->values(),
        ]);
    }
}
