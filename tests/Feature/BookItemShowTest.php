<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookItemShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_books_detail_page_renders_with_the_users_entry(): void
    {
        $user = User::factory()->create();
        $item = BookItem::factory()->create(['title' => 'Beispielbuch']);
        BookEntry::factory()->for($user)->for($item, 'bookItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('books.items.show', $item))->assertInertia(
            fn (Assert $page) => $page->component('media/book-show')->where('item.title', 'Beispielbuch')->where('item.entry.status', 'watchlist'),
        );
    }

    public function test_a_books_detail_page_renders_without_an_entry(): void
    {
        $user = User::factory()->create();
        $item = BookItem::factory()->create();

        $this->actingAs($user)->get(route('books.items.show', $item))->assertInertia(
            fn (Assert $page) => $page->component('media/book-show')->where('item.entry', null),
        );
    }

    public function test_it_never_shows_another_users_entry(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = BookItem::factory()->create();
        BookEntry::factory()->for($other, 'user')->for($item, 'bookItem')->create(['status' => WatchStatus::Completed]);

        $this->actingAs($user)->get(route('books.items.show', $item))->assertInertia(fn (Assert $page) => $page->where('item.entry', null));
    }
}
