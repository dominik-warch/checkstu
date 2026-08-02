<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BoardGameStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoardGame>
 */
class BoardGameFactory extends Factory
{
    private static int $sequence = 1;

    public function definition(): array
    {
        return [
            'bgg_id' => self::$sequence++,
            'name' => fake()->words(3, true),
            'thumbnail_url' => null,
            'year_published' => fake()->numberBetween(1990, 2026),
            'min_players' => 2,
            'max_players' => 4,
            'playtime_minutes' => 60,
            'status' => BoardGameStatus::Owned,
        ];
    }

    public function wishlist(): static
    {
        return $this->state(fn () => ['status' => BoardGameStatus::Wishlist]);
    }
}
