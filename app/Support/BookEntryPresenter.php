<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\BookEntry;
use App\Models\BookItem;

class BookEntryPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(BookEntry $entry): array
    {
        return [
            'kind' => 'book',
            'id' => $entry->id,
            'status' => $entry->status->value,
            'read_at' => $entry->read_at?->toDateString(),
            'book_item' => self::bookItemSummary($entry->bookItem),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function bookItemSummary(BookItem $item): array
    {
        return [
            'id' => $item->id,
            'google_books_id' => $item->google_books_id,
            'title' => $item->title,
            'authors' => $item->authors,
            'thumbnail_url' => $item->thumbnail_url,
            'published_date' => $item->published_date?->toDateString(),
        ];
    }
}
