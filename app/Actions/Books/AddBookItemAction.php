<?php

declare(strict_types=1);

namespace App\Actions\Books;

use App\Models\BookItem;
use App\Support\GoogleBooks\GoogleBooksClient;

class AddBookItemAction
{
    public function __construct(
        private readonly GoogleBooksClient $googleBooks,
    ) {}

    /**
     * Idempotent: returns the cached BookItem if it already exists, otherwise
     * fetches authoritative details from Google Books (not the client-supplied
     * search result) and caches them permanently.
     */
    public function handle(string $googleBooksId): BookItem
    {
        $existing = BookItem::where('google_books_id', $googleBooksId)->first();
        if ($existing !== null) {
            return $existing;
        }

        $details = $this->googleBooks->details($googleBooksId);

        return BookItem::create($details);
    }
}
