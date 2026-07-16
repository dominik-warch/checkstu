<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WatchStatus;
use App\Models\BookItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookEntry>
 */
class BookEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_item_id' => BookItem::factory(),
            'status' => WatchStatus::Watchlist,
            'read_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => WatchStatus::Completed, 'read_at' => now()]);
    }
}
