<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\MarkEpisodeWatchedAction;
use App\Actions\Media\RecomputeMediaEntryStatusAction;
use App\Actions\Media\UnmarkEpisodeWatchedAction;
use App\Http\Requests\Media\MediaEpisodeWatchRequest;
use App\Http\Requests\Media\MediaEpisodeWatchRowRequest;
use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use Illuminate\Http\RedirectResponse;

class MediaEpisodeWatchController extends Controller
{
    /** Also used to log an additional watch on an already-watched episode — see MarkEpisodeWatchedAction. */
    public function store(MediaEpisodeWatchRequest $request, MediaEpisode $episode, MarkEpisodeWatchedAction $action): RedirectResponse
    {
        $action->handle($request->user(), $episode, $request->validated('watched_at'));

        return back();
    }

    /** Clears every watch for this episode, not just one. */
    public function destroy(MediaEpisodeWatchRequest $request, MediaEpisode $episode, UnmarkEpisodeWatchedAction $action): RedirectResponse
    {
        $action->handle($request->user(), $episode);

        return back();
    }

    public function update(MediaEpisodeWatchRowRequest $request, MediaEpisodeWatch $watch): RedirectResponse
    {
        $watch->update(['watched_at' => $request->validated('watched_at')]);

        return back();
    }

    /** Unlike destroy(), removes only this one watch — the episode may still be watched via others. */
    public function destroyOne(
        MediaEpisodeWatchRowRequest $request,
        MediaEpisodeWatch $watch,
        RecomputeMediaEntryStatusAction $recompute,
    ): RedirectResponse {
        $episode = $watch->episode;
        $watch->delete();
        $recompute->handle($request->user(), $episode->season->mediaItem);

        return back();
    }
}
