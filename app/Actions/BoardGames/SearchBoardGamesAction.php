<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Support\Bgg\BggClient;
use Illuminate\Support\Facades\Cache;

class SearchBoardGamesAction
{
    public function __construct(
        private readonly BggClient $bgg,
    ) {}

    /**
     * @return list<array{bgg_id: int, name: string, year_published: ?int}>
     */
    public function handle(string $query): array
    {
        $cacheKey = 'boardgames:search:'.md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->bgg->search($query));
    }
}
