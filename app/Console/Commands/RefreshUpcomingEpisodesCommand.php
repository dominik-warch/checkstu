<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Media\RefreshUpcomingCacheAction;
use App\Enums\MediaType;
use App\Enums\WatchStatus;
use App\Models\MediaItem;
use Illuminate\Console\Command;

class RefreshUpcomingEpisodesCommand extends Command
{
    protected $signature = 'media:refresh-upcoming';

    protected $description = 'Refresh TMDb season/episode data for every TV show on a watchlist, so the "coming up" page can resolve from cache alone';

    public function handle(RefreshUpcomingCacheAction $action): int
    {
        $items = MediaItem::query()
            ->where('type', MediaType::Tv)
            ->whereHas('entries', fn ($q) => $q->whereIn('status', [WatchStatus::Watchlist, WatchStatus::Watching]))
            ->get();

        foreach ($items as $item) {
            $action->handle($item);
        }

        $this->info("Refreshed {$items->count()} show(s).");

        return self::SUCCESS;
    }
}
