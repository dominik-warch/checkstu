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

    private function fakeBookDetails(): void
    {
        Http::fake([
            '*/volumes/zyTCAlFPjgYC*' => Http::response([
                'id' => 'zyTCAlFPjgYC',
                'volumeInfo' => [
                    'title' => 'Der Fänger im Roggen',
                    'authors' => ['J.D. Salinger'],
                    'description' => 'Ein Klassiker.',
                    'publishedDate' => '1951-07-16',
                ],
            ]),
        ]);
    }

    public function test_adding_a_book_to_the_watchlist_creates_item_and_entry(): void
    {
        $this->fakeBookDetails();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('books.entries.store'), ['google_books_id' => 'zyTCAlFPjgYC', 'status' => 'watchlist'])
            ->assertRedirect();

        $item = BookItem::firstWhere('google_books_id', 'zyTCAlFPjgYC');
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
            'google_books_id' => 'zyTCAlFPjgYC', 'status' => 'completed', 'read_at' => '2026-07-10',
        ])->assertRedirect();

        $entry = BookEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertSame('2026-07-10', $entry->read_at->toDateString());
    }

    public function test_adding_the_same_book_twice_does_not_call_google_books_again(): void
    {
        $this->fakeBookDetails();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('books.entries.store'), ['google_books_id' => 'zyTCAlFPjgYC', 'status' => 'watchlist']);
        $this->actingAs($user)->post(route('books.entries.store'), ['google_books_id' => 'zyTCAlFPjgYC', 'status' => 'completed']);

        Http::assertSentCount(1);
        $this->assertSame(1, BookItem::count());
        $entry = BookEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
    }

    public function test_status_must_be_watchlist_or_completed(): void
    {
        $this->fakeBookDetails();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('books.entries.store'), ['google_books_id' => 'zyTCAlFPjgYC', 'status' => 'watching'])
            ->assertSessionHasErrors('status');
    }
}
