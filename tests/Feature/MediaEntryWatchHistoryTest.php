<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaEntryWatchHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_logging_another_watch_defaults_to_today(): void
    {
        $user = User::factory()->create();
        $entry = MediaEntry::factory()->completed()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();

        $this->actingAs($user)->post(route('media.entries.watches.store', $entry))->assertRedirect();

        $entry->refresh();
        $this->assertCount(2, $entry->watches);
        $this->assertSame(now()->toDateString(), $entry->watches->last()->watched_at->toDateString());
    }

    public function test_a_watch_cannot_be_added_to_a_movie_thats_not_completed(): void
    {
        $user = User::factory()->create();
        $entry = MediaEntry::factory()->for($user)->for(MediaItem::factory(), 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.entries.watches.store', $entry))->assertForbidden();
    }

    public function test_a_specific_watchs_date_can_be_edited(): void
    {
        $user = User::factory()->create();
        $entry = MediaEntry::factory()->completed()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();
        $watch = $entry->watches->first();

        $this->actingAs($user)
            ->patch(route('media.entryWatches.update', $watch), ['watched_at' => '2026-03-03'])
            ->assertRedirect();

        $this->assertSame('2026-03-03', $watch->fresh()->watched_at->toDateString());
    }

    public function test_removing_one_of_several_watches_keeps_the_entry_completed(): void
    {
        $user = User::factory()->create();
        $entry = MediaEntry::factory()->completed()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();
        $second = $entry->watches()->create(['watched_at' => '2026-04-04']);

        $this->actingAs($user)->delete(route('media.entryWatches.destroy', $second))->assertRedirect();

        $entry->refresh();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertCount(1, $entry->watches);
    }

    public function test_removing_the_last_watch_reverts_the_entry_to_watchlist(): void
    {
        $user = User::factory()->create();
        $entry = MediaEntry::factory()->completed()->for($user)->for(MediaItem::factory(), 'mediaItem')->create();
        $watch = $entry->watches->first();

        $this->actingAs($user)->delete(route('media.entryWatches.destroy', $watch))->assertRedirect();

        $entry->refresh();
        $this->assertSame(WatchStatus::Watchlist, $entry->status);
        $this->assertCount(0, $entry->watches);
    }

    public function test_a_user_cannot_add_a_watch_to_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $entry = MediaEntry::factory()->completed()->for($owner, 'user')->for(MediaItem::factory(), 'mediaItem')->create();

        $this->actingAs($intruder)->post(route('media.entries.watches.store', $entry))->assertForbidden();
    }

    public function test_a_user_cannot_edit_another_users_watch(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $entry = MediaEntry::factory()->completed()->for($owner, 'user')->for(MediaItem::factory(), 'mediaItem')->create();
        $watch = $entry->watches->first();

        $this->actingAs($intruder)
            ->patch(route('media.entryWatches.update', $watch), ['watched_at' => '2026-03-03'])
            ->assertForbidden();
    }
}
