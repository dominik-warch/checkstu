<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MediaEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaEntryWatch>
 */
class MediaEntryWatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'media_entry_id' => MediaEntry::factory(),
            'watched_at' => now(),
        ];
    }
}
