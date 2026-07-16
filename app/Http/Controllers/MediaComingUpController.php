<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Media\ResolveUpcomingEpisodeAction;
use App\Enums\MediaType;
use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Support\BookEntryPresenter;
use App\Support\MediaHomePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MediaComingUpController extends Controller
{
    public function index(Request $request, ResolveUpcomingEpisodeAction $resolveUpcoming): Response
    {
        $user = $request->user();

        $mediaEntries = MediaEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [WatchStatus::Watchlist, WatchStatus::Watching])
            ->with('mediaItem.seasons.episodes')
            ->get();

        $bookEntries = BookEntry::query()
            ->where('user_id', $user->id)
            ->where('status', WatchStatus::Watchlist)
            ->with('bookItem')
            ->get();

        $items = $mediaEntries
            ->map(fn (MediaEntry $entry) => $this->resolveMedia($entry->mediaItem, $resolveUpcoming))
            ->concat($bookEntries->map(fn (BookEntry $entry) => $this->resolveBook($entry->bookItem)))
            ->filter()
            ->sortBy('date')
            ->values()
            ->all();

        return Inertia::render('media/coming-up', [
            'items' => $items,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveMedia(MediaItem $item, ResolveUpcomingEpisodeAction $resolveUpcoming): ?array
    {
        if ($item->type === MediaType::Movie) {
            if ($item->release_date === null || $item->release_date->lt(Carbon::today())) {
                return null;
            }

            return [
                'kind' => 'media',
                'date' => $item->release_date->toDateString(),
                'media_item' => MediaHomePresenter::mediaItemSummary($item),
                'episode' => null,
            ];
        }

        $upcoming = $resolveUpcoming->handle($item);
        if ($upcoming === null) {
            return null;
        }

        return [
            'kind' => 'media',
            'date' => $upcoming['air_date'],
            'media_item' => MediaHomePresenter::mediaItemSummary($item),
            'episode' => [
                'season_number' => $upcoming['season_number'],
                'episode_number' => $upcoming['episode_number'],
                'name' => $upcoming['name'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveBook(BookItem $item): ?array
    {
        if ($item->published_date === null || $item->published_date->lt(Carbon::today())) {
            return null;
        }

        return [
            'kind' => 'book',
            'date' => $item->published_date->toDateString(),
            'book_item' => BookEntryPresenter::bookItemSummary($item),
        ];
    }
}
