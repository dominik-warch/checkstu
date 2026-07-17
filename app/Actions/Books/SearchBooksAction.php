<?php

declare(strict_types=1);

namespace App\Actions\Books;

use App\Support\OpenLibrary\OpenLibraryClient;
use Illuminate\Support\Facades\Cache;

class SearchBooksAction
{
    public function __construct(
        private readonly OpenLibraryClient $openLibrary,
    ) {}

    /**
     * @return list<array{open_library_id: string, title: string, authors: string, overview: string, thumbnail_url: ?string, published_date: ?string}>
     */
    public function handle(string $query): array
    {
        $cacheKey = 'books:search:'.md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->openLibrary->search($query));
    }
}
