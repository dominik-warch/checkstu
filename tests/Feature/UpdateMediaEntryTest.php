<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaEpisode;
use App\Models\MediaEpisodeWatch;
use App\Models\MediaItem;
use App\Models\MediaSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateMediaEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_movie_entry_can_be_edited_to_watched_with_a_specific_date(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create();
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'completed', 'watched_at' => '2026-06-01'])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertSame('2026-06-01', $entry->watched_at->toDateString());
    }

    public function test_a_tv_entrys_status_cannot_be_edited_directly(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'completed'])
            ->assertSessionHasErrors('status');
    }

    public function test_a_user_cannot_edit_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = MediaItem::factory()->create();
        $entry = MediaEntry::factory()->for($owner, 'user')->for($item, 'mediaItem')->create();

        $this->actingAs($intruder)
            ->patch(route('media.entries.update', $entry), ['status' => 'completed'])
            ->assertForbidden();
    }

    public function test_removing_an_entry_keeps_episode_watch_history(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create();
        $episode = MediaEpisode::factory()->for($season, 'season')->create();
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create();
        MediaEpisodeWatch::factory()->for($user)->for($episode, 'episode')->create();

        $this->actingAs($user)->delete(route('media.entries.destroy', $entry))->assertRedirect();

        $this->assertDatabaseMissing('media_entries', ['id' => $entry->id]);
        $this->assertDatabaseCount('media_episode_watches', 1);
    }

    public function test_a_user_cannot_delete_another_users_entry(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $item = MediaItem::factory()->create();
        $entry = MediaEntry::factory()->for($owner, 'user')->for($item, 'mediaItem')->create();

        $this->actingAs($intruder)->delete(route('media.entries.destroy', $entry))->assertForbidden();

        $this->assertDatabaseHas('media_entries', ['id' => $entry->id]);
    }
}
