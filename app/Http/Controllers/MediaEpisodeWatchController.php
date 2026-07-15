<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\MarkEpisodeWatchedAction;
use App\Actions\Media\UnmarkEpisodeWatchedAction;
use App\Http\Requests\Media\MediaEpisodeWatchRequest;
use App\Models\MediaEpisode;
use Illuminate\Http\RedirectResponse;

class MediaEpisodeWatchController extends Controller
{
    public function store(MediaEpisodeWatchRequest $request, MediaEpisode $episode, MarkEpisodeWatchedAction $action): RedirectResponse
    {
        $action->handle($request->user(), $episode);

        return back();
    }

    public function destroy(MediaEpisodeWatchRequest $request, MediaEpisode $episode, UnmarkEpisodeWatchedAction $action): RedirectResponse
    {
        $action->handle($request->user(), $episode);

        return back();
    }
}
