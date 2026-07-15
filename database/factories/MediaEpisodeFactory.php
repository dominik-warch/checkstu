<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MediaSeason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaEpisode>
 */
class MediaEpisodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'media_season_id' => MediaSeason::factory(),
            'tmdb_episode_id' => fake()->unique()->numberBetween(1, 999_999),
            'episode_number' => 1,
            'name' => fake()->sentence(3),
            'air_date' => fake()->date(),
        ];
    }

    public function unaired(): static
    {
        return $this->state(fn () => ['air_date' => now()->addMonth()->toDateString()]);
    }
}
