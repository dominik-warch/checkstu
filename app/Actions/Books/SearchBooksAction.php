<?php

declare(strict_types=1);

namespace App\Actions\Books;

use App\Support\GoogleBooks\GoogleBooksClient;
use Illuminate\Support\Facades\Cache;

class SearchBooksAction
{
    public function __construct(
        private readonly GoogleBooksClient $googleBooks,
    ) {}

    /**
     * @return list<array{google_books_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}>
     */
    public function handle(string $query): array
    {
        $cacheKey = 'books:search:'.md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->googleBooks->search($query));
    }
}
