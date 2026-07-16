<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\MarkAllEpisodesWatchedAction;
use App\Http\Requests\Media\MarkShowWatchedRequest;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Support\MediaItemPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaItemController extends Controller
{
    public function show(Request $request, MediaItem $mediaItem): Response
    {
        // Season list/episode freshness is media:refresh-upcoming's job (nightly,
        // and on add) — this page reads from cache alone, same as "coming up".
        // A no-op for movies (no seasons to load).
        $mediaItem->load('seasons.episodes');

        $entry = MediaEntry::where('user_id', $request->user()->id)
            ->where('media_item_id', $mediaItem->id)
            ->first();

        return Inertia::render('media/show', [
            'item' => MediaItemPresenter::detail($mediaItem, $entry, $request->user()),
        ]);
    }

    public function markAllWatched(MarkShowWatchedRequest $request, MediaItem $mediaItem, MarkAllEpisodesWatchedAction $action): RedirectResponse
    {
        $mediaItem->load('seasons');
        $action->handle($request->user(), $mediaItem);

        return back();
    }
}
