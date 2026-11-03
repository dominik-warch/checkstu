<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\MarkAllEpisodesWatchedAction;
use App\Actions\Media\RecomputeMediaEntryStatusAction;
use App\Http\Requests\Media\MarkShowWatchedRequest;
use App\Models\MediaEntry;
use App\Models\MediaEpisodeWatch;
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

        $sharedBy = MediaEntry::where('media_item_id', $mediaItem->id)
            ->where('user_id', '!=', $request->user()->id)
            ->with('user')
            ->get();

        return Inertia::render('media/show', [
            'item' => MediaItemPresenter::detail($mediaItem, $entry, $request->user(), $sharedBy),
        ]);
    }

    public function markAllWatched(MarkShowWatchedRequest $request, MediaItem $mediaItem, MarkAllEpisodesWatchedAction $action): RedirectResponse
    {
        $mediaItem->load('seasons');
        $created = $action->handle($request->user(), $mediaItem);

        // One-shot, for the undo toast: exactly the watches this tap created.
        return back()->with('undo', ['watch_ids' => $created]);
    }

    /** Undo "Alle Folgen abhaken": remove only the watches that tap created, then recompute the status. */
    public function unmarkWatched(MarkShowWatchedRequest $request, MediaItem $mediaItem, RecomputeMediaEntryStatusAction $recompute): RedirectResponse
    {
        $ids = array_map('intval', (array) $request->input('watch_ids', []));

        MediaEpisodeWatch::query()
            ->whereIn('id', $ids)
            ->where('user_id', $request->user()->id)
            ->whereHas('episode.season', fn ($q) => $q->where('media_item_id', $mediaItem->id))
            ->delete();

        $recompute->handle($request->user(), $mediaItem);

        return back();
    }
}
