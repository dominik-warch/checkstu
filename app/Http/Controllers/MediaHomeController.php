<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\ResolveNextEpisodeAction;
use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Support\MediaHomePresenter;
use Illuminate\Http\Request;
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

        $nextEpisodes = $entries->map(fn (MediaEntry $entry) => [
            'media_item' => MediaHomePresenter::mediaItemSummary($entry->mediaItem),
            'next_episode' => $resolveNextEpisode->handle($user, $entry->mediaItem),
        ])->values()->all();

        return Inertia::render('media/home', [
            'nextEpisodes' => $nextEpisodes,
        ]);
    }
}
