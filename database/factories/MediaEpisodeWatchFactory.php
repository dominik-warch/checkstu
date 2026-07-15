<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MediaEpisode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaEpisodeWatch>
 */
class MediaEpisodeWatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'media_episode_id' => MediaEpisode::factory(),
            'watched_at' => now(),
        ];
    }
}
