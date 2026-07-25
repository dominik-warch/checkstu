<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\MediaEntry;
use App\Support\BookEntryPresenter;
use App\Support\MediaEntryPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MediaLibraryController extends Controller
{
    /** Unifies movies/TV (media_entries) and books (book_entries) into one list — see IMPLEMENTATION_PLAN.md §Media. */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = WatchStatus::tryFrom($request->string('status', '')->toString());
        $typeParam = $request->string('type', 'all')->toString();
        $mediaType = MediaType::tryFrom($typeParam); // non-null only for 'movie'/'tv'
        $sortParam = $request->string('sort', 'alphabetical')->toString();
        $sort = in_array($sortParam, ['alphabetical', 'recent', 'release'], true) ? $sortParam : 'alphabetical';

        $includeMedia = $typeParam !== 'book';
        $includeBooks = $typeParam === 'all' || $typeParam === 'book';

        $mediaEntries = $includeMedia
            ? MediaEntry::query()
                ->where('user_id', $user->id)
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($mediaType, fn ($query) => $query->whereHas('mediaItem', fn ($q) => $q->where('type', $mediaType)))
                ->with('mediaItem')
                ->get()
            : collect();

        $bookEntries = $includeBooks
            ? BookEntry::query()
                ->where('user_id', $user->id)
                ->when($status, fn ($query) => $query->where('status', $status))
                ->with('bookItem')
                ->get()
            : collect();

        // Grouped once up front (rather than per-row) so the shared-by lookup for N entries
        // costs one query per kind, not N.
        $sharedMediaByItem = MediaEntry::whereIn('media_item_id', $mediaEntries->pluck('media_item_id'))
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->get()
            ->groupBy('media_item_id');

        $sharedBooksByItem = BookEntry::whereIn('book_item_id', $bookEntries->pluck('book_item_id'))
            ->where('user_id', '!=', $user->id)
            ->with('user')
            ->get()
            ->groupBy('book_item_id');

        $entries = $mediaEntries
            ->map(fn (MediaEntry $entry) => [
                'title' => $entry->mediaItem->title_de,
                'created_at' => $entry->created_at,
                'release_date' => $entry->mediaItem->release_date,
                'data' => MediaEntryPresenter::toArray($entry, $sharedMediaByItem->get($entry->media_item_id)),
            ])
            ->concat($bookEntries->map(fn (BookEntry $entry) => [
                'title' => $entry->bookItem->title,
                'created_at' => $entry->created_at,
                'release_date' => $entry->bookItem->published_date,
                'data' => BookEntryPresenter::toArray($entry, $sharedBooksByItem->get($entry->book_item_id)),
            ]));

        $entries = match ($sort) {
            'recent' => $entries->sortByDesc('created_at'),
            'release' => $entries->sortByDesc(fn (array $row) => $row['release_date']?->timestamp ?? -1),
            default => $entries->sortBy(fn (array $row) => mb_strtolower($row['title'])),
        };

        $entries = $entries->pluck('data')->values();

        return Inertia::render('media/library', [
            'entries' => $entries,
            'filters' => [
                'status' => $status?->value ?? 'all',
                'type' => in_array($typeParam, ['movie', 'tv', 'book'], true) ? $typeParam : 'all',
                'sort' => $sort,
            ],
        ]);
    }
}
