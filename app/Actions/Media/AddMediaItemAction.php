<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\MediaItem;
use App\Support\Tmdb\TmdbClient;
use Illuminate\Support\Facades\DB;

class AddMediaItemAction
{
    public function __construct(
        private readonly TmdbClient $tmdb,
    ) {}

    /**
     * Idempotent: returns the cached MediaItem if it already exists, otherwise
     * fetches full details (+ season summaries for tv, no episodes yet — those
     * are fetched lazily per season) from TMDb and caches them permanently.
     */
    public function handle(int $tmdbId, MediaType $type): MediaItem
    {
        $existing = MediaItem::where('tmdb_id', $tmdbId)->where('type', $type)->first();
        if ($existing !== null) {
            return $existing;
        }

        $details = $this->tmdb->details($tmdbId, $type);

        return DB::transaction(function () use ($details): MediaItem {
            $item = MediaItem::create([
                'tmdb_id' => $details['tmdb_id'],
                'type' => $details['type'],
                'title_de' => $details['title_de'],
                'title_en' => $details['title_en'],
                'overview' => $details['overview'],
                'poster_path' => $details['poster_path'],
                'release_date' => $details['release_date'],
                'tv_status' => $details['tv_status'],
            ]);

            foreach ($details['seasons'] as $season) {
                $item->seasons()->create($season);
            }

            return $item;
        });
    }
}
