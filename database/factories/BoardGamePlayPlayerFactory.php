<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BoardGamePlay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoardGamePlayPlayer>
 */
class BoardGamePlayPlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_game_play_id' => BoardGamePlay::factory(),
            'user_id' => null,
            'guest_name' => fake()->firstName(),
            'is_winner' => false,
        ];
    }
}
