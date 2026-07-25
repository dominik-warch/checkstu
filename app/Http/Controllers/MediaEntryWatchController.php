<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WatchStatus;
use App\Http\Requests\Media\MediaEntryWatchRequest;
use App\Models\MediaEntry;
use App\Models\MediaEntryWatch;
use Illuminate\Http\RedirectResponse;

class MediaEntryWatchController extends Controller
{
    /** Logging an extra watch is a deliberate "I just (re)watched this" action, so it defaults to today. */
    public function store(MediaEntryWatchRequest $request, MediaEntry $entry): RedirectResponse
    {
        $entry->watches()->create(['watched_at' => now()->toDateString()]);

        return back();
    }

    public function update(MediaEntryWatchRequest $request, MediaEntryWatch $watch): RedirectResponse
    {
        $watch->update(['watched_at' => $request->validated('watched_at')]);

        return back();
    }

    /** Removing the last remaining watch demotes the entry back to watchlist, symmetric with UpdateMediaEntryAction. */
    public function destroy(MediaEntryWatchRequest $request, MediaEntryWatch $watch): RedirectResponse
    {
        $entry = $watch->entry;
        $watch->delete();

        if ($entry->watches()->doesntExist()) {
            $entry->update(['status' => WatchStatus::Watchlist]);
        }

        return back();
    }
}
