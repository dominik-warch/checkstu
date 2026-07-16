<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookItem>
 */
class BookItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'google_books_id' => fake()->unique()->bothify('########'),
            'title' => fake()->sentence(3),
            'authors' => fake()->name(),
            'overview' => fake()->paragraph(),
            'thumbnail_url' => 'https://books.google.com/'.fake()->uuid().'.jpg',
            'published_date' => fake()->date(),
        ];
    }
}
