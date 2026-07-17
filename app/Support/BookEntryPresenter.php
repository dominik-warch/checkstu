<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\BookEntry;
use App\Models\BookItem;
use Illuminate\Support\Collection;

class BookEntryPresenter
{
    /**
     * @param  Collection<int, BookEntry>|null  $sharedBy  other users' entries for the same book item
     * @return array<string, mixed>
     */
    public static function toArray(BookEntry $entry, ?Collection $sharedBy = null): array
    {
        return [
            'kind' => 'book',
            'id' => $entry->id,
            'status' => $entry->status->value,
            'read_at' => $entry->read_at?->toDateString(),
            'book_item' => self::bookItemSummary($entry->bookItem),
            'shared_by' => self::sharedByMembers($sharedBy),
        ];
    }

    /**
     * @param  Collection<int, BookEntry>|null  $sharedBy
     * @return array<int, array<string, mixed>>
     */
    private static function sharedByMembers(?Collection $sharedBy): array
    {
        return ($sharedBy ?? collect())
            ->map(fn (BookEntry $entry) => [
                'id' => $entry->user->id,
                'name' => $entry->user->name,
                'color' => $entry->user->color,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function bookItemSummary(BookItem $item): array
    {
        return [
            'id' => $item->id,
            'open_library_id' => $item->open_library_id,
            'title' => $item->title,
            'authors' => $item->authors,
            'thumbnail_url' => $item->thumbnail_url,
            'published_date' => $item->published_date?->toDateString(),
        ];
    }

    /**
     * Shape a book item + the current user's entry for the detail page.
     *
     * @param  Collection<int, BookEntry>  $sharedBy  other users' entries for this item, with `user` eager-loaded
     * @return array<string, mixed>
     */
    public static function detail(BookItem $item, ?BookEntry $entry, Collection $sharedBy): array
    {
        return [
            ...self::bookItemSummary($item),
            'overview' => $item->overview,
            'entry' => $entry ? [
                'id' => $entry->id,
                'status' => $entry->status->value,
                'read_at' => $entry->read_at?->toDateString(),
            ] : null,
            'shared_by' => $sharedBy->map(fn (BookEntry $entry) => [
                'id' => $entry->user->id,
                'name' => $entry->user->name,
                'color' => $entry->user->color,
                'status' => $entry->status->value,
            ])->values()->all(),
        ];
    }
}
