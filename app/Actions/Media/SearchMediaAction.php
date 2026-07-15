<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Support\Tmdb\TmdbClient;
use Illuminate\Support\Facades\Cache;

class SearchMediaAction
{
    public function __construct(
        private readonly TmdbClient $tmdb,
    ) {}

    /**
     * @return list<array{tmdb_id: int, type: string, title: string, original_title: string, overview: string, poster_path: ?string, release_date: ?string}>
     */
    public function handle(string $query, MediaType $type): array
    {
        $cacheKey = 'media:search:'.$type->value.':'.md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->tmdb->search($query, $type));
    }
}
