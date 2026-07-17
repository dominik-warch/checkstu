<?php

declare(strict_types=1);

namespace App\Actions\Books;

use App\Models\BookItem;
use App\Support\OpenLibrary\OpenLibraryClient;

class AddBookItemAction
{
    public function __construct(
        private readonly OpenLibraryClient $openLibrary,
    ) {}

    /**
     * Idempotent: returns the cached BookItem if it already exists, otherwise
     * fetches authoritative details from Open Library (not the client-supplied
     * search result) and caches them permanently.
     */
    public function handle(string $openLibraryId): BookItem
    {
        $existing = BookItem::where('open_library_id', $openLibraryId)->first();
        if ($existing !== null) {
            return $existing;
        }

        $details = $this->openLibrary->details($openLibraryId);

        return BookItem::create($details);
    }
}
