<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WatchStatus;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaEntry>
 */
class MediaEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'media_item_id' => MediaItem::factory(),
            'status' => WatchStatus::Watchlist,
            'watched_at' => null,
        ];
    }

    public function watching(): static
    {
        return $this->state(fn () => ['status' => WatchStatus::Watching]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => WatchStatus::Completed, 'watched_at' => now()]);
    }
}
