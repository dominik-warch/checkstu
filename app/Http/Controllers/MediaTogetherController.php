<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\User;
use App\Support\MediaHomePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaTogetherController extends Controller
{
    /** Pick another member and see which movies/shows are on both watchlists — a shortlist for what to watch together. */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $members = User::where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $selected = $members->firstWhere('id', $request->integer('member'));

        $items = $selected
            ? MediaItem::query()
                ->whereIn('id', MediaEntry::where('user_id', $user->id)->where('status', WatchStatus::Watchlist)->pluck('media_item_id'))
                ->whereHas('entries', fn ($q) => $q->where('user_id', $selected->id)->where('status', WatchStatus::Watchlist))
                ->orderBy('title_de')
                ->get()
                ->map(fn (MediaItem $item) => MediaHomePresenter::mediaItemSummary($item))
                ->values()
                ->all()
            : [];

        return Inertia::render('media/together', [
            'members' => $members->map(fn (User $m) => ['id' => $m->id, 'name' => $m->name, 'color' => $m->color])->values()->all(),
            'selectedMemberId' => $selected?->id,
            'items' => $items,
        ]);
    }
}
