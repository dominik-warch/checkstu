<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BoardGame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoardGamePlay>
 */
class BoardGamePlayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_game_id' => BoardGame::factory(),
            'played_at' => fake()->date(),
            'notes' => null,
        ];
    }
}
