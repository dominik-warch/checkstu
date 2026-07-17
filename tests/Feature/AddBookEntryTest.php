<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddBookEntryTest extends TestCase
{
    use RefreshDatabase;

    private const OPEN_LIBRARY_ID = '/books/OL1M';

    private function fakeBookDetails(): void
    {
        Http::fake([
            '*/books/OL1M.json' => Http::response([
                'key' => self::OPEN_LIBRARY_ID,
                'title' => 'Der Fänger im Roggen',
                'publish_date' => '1951',
                'works' => [['key' => '/works/OL1W']],
            ]),
            '*/works/OL1W.json' => Http::response([
                'description' => 'Ein Klassiker.',
                'authors' => [['author' => ['key' => '/authors/OL1A']]],
            ]),
            '*/authors/OL1A.json' => Http::response(['name' => 'J.D. Salinger']),
        ]);
    }

    public function test_adding_a_book_to_the_watchlist_creates_item_and_entry(): void
    {
        $this->fakeBookDetails();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('books.entries.store'), ['open_library_id' => self::OPEN_LIBRARY_ID, 'status' => 'watchlist'])
            ->assertRedirect();

        $item = BookItem::firstWhere('open_library_id', self::OPEN_LIBRARY_ID);
        $this->assertNotNull($item);
        $this->assertSame('Der Fänger im Roggen', $item->title);
        $this->assertSame('J.D. Salinger', $item->authors);

        $entry = BookEntry::where('user_id', $user->id)->where('book_item_id', $item->id)->first();
        $this->assertSame(WatchStatus::Watchlist, $entry->status);
    }

    public function test_marking_a_book_read_directly_sets_completed_status_and_date(): void
    {
        $this->fakeBookDetails();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('books.entries.store'), [
            'open_library_id' => self::OPEN_LIBRARY_ID, 'status' => 'completed', 'read_at' => '2026-07-10',
        ])->assertRedirect();

        $entry = BookEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertSame('2026-07-10', $entry->read_at->toDateString());
    }

    public function test_adding_the_same_book_twice_does_not_call_open_library_again(): void
    {
        $this->fakeBookDetails();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('books.entries.store'), ['open_library_id' => self::OPEN_LIBRARY_ID, 'status' => 'watchlist']);
        $this->actingAs($user)->post(route('books.entries.store'), ['open_library_id' => self::OPEN_LIBRARY_ID, 'status' => 'completed']);

        // 3 calls total (edition, work, author) — all from the first add. The second
        // add finds the cached BookItem and skips Open Library entirely.
        Http::assertSentCount(3);
        $this->assertSame(1, BookItem::count());
        $entry = BookEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
    }

    public function test_status_must_be_watchlist_or_completed(): void
    {
        $this->fakeBookDetails();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('books.entries.store'), ['open_library_id' => self::OPEN_LIBRARY_ID, 'status' => 'watching'])
            ->assertSessionHasErrors('status');
    }
}
