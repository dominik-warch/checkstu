<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MediaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaSeason>
 */
class MediaSeasonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'media_item_id' => MediaItem::factory()->tv(),
            'tmdb_season_id' => fake()->unique()->numberBetween(1, 999_999),
            'season_number' => 1,
            'name' => 'Season 1',
            'episode_count' => 10,
            'air_date' => fake()->date(),
            'episodes_fetched_at' => null,
        ];
    }

    public function specials(): static
    {
        return $this->state(fn () => ['season_number' => 0, 'name' => 'Specials']);
    }

    public function episodesCached(): static
    {
        return $this->state(fn () => ['episodes_fetched_at' => now()]);
    }
}
