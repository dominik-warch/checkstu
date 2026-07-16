<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateBookEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_book_entry_can_be_edited_to_read_with_a_specific_date(): void
    {
        $user = User::factory()->create();
        $item = BookItem::factory()->create();
        $entry = BookEntry::factory()->for($user)->for($item, 'bookItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)
            ->patch(route('books.entries.update', $entry), ['status' => 'completed', 'read_at' => '2026-06-01'])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertSame('2026-06-01', $entry->read_at->toDateString());
    }

    public function test_a_user_cannot_edit_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = BookItem::factory()->create();
        $entry = BookEntry::factory()->for($owner, 'user')->for($item, 'bookItem')->create();

        $this->actingAs($intruder)
            ->patch(route('books.entries.update', $entry), ['status' => 'completed'])
            ->assertForbidden();
    }

    public function test_removing_an_entry_works(): void
    {
        $user = User::factory()->create();
        $item = BookItem::factory()->create();
        $entry = BookEntry::factory()->for($user)->for($item, 'bookItem')->create();

        $this->actingAs($user)->delete(route('books.entries.destroy', $entry))->assertRedirect();

        $this->assertDatabaseMissing('book_entries', ['id' => $entry->id]);
    }

    public function test_a_user_cannot_delete_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = BookItem::factory()->create();
        $entry = BookEntry::factory()->for($owner, 'user')->for($item, 'bookItem')->create();

        $this->actingAs($intruder)->delete(route('books.entries.destroy', $entry))->assertForbidden();

        $this->assertDatabaseHas('book_entries', ['id' => $entry->id]);
    }
}
