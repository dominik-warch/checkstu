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
            'open_library_id' => '/books/OL'.fake()->unique()->numerify('########').'M',
            'title' => fake()->sentence(3),
            'authors' => fake()->name(),
            'overview' => fake()->paragraph(),
            'thumbnail_url' => 'https://covers.openlibrary.org/b/id/'.fake()->numberBetween(1, 9999999).'-M.jpg',
            'published_date' => fake()->date(),
        ];
    }
}
