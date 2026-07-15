<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MediaItem>
 */
class MediaItemFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'tmdb_id' => fake()->unique()->numberBetween(1, 999_999),
            'type' => MediaType::Movie,
            'title_de' => $title,
            'title_en' => $title,
            'overview' => fake()->paragraph(),
            'poster_path' => '/'.fake()->uuid().'.jpg',
            'release_date' => fake()->date(),
            'tv_status' => null,
        ];
    }

    public function tv(): static
    {
        return $this->state(fn () => [
            'type' => MediaType::Tv,
            'tv_status' => 'Returning Series',
        ]);
    }
}
