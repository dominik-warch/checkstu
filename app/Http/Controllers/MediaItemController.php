<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\RefreshShowSeasonsAction;
use App\Enums\MediaType;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Support\MediaItemPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaItemController extends Controller
{
    public function show(Request $request, MediaItem $mediaItem, RefreshShowSeasonsAction $refresh): Response|RedirectResponse
    {
        // Movies have no detail page in v1 — everything happens inline from
        // search results and the library list.
        if ($mediaItem->type === MediaType::Movie) {
            return redirect()->route('media.library');
        }

        $refresh->handle($mediaItem);
        $mediaItem->load('seasons.episodes');

        $entry = MediaEntry::where('user_id', $request->user()->id)
            ->where('media_item_id', $mediaItem->id)
            ->first();

        return Inertia::render('media/show', [
            'item' => MediaItemPresenter::detail($mediaItem, $entry, $request->user()),
        ]);
    }
}
