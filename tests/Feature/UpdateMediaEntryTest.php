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

    public function test_marking_a_movie_entry_completed_creates_a_blank_first_watch(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create();
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'completed'])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertCount(1, $entry->watches);
        $this->assertNull($entry->watches->first()->watched_at);
    }

    public function test_un_completing_a_movie_entry_clears_its_watch_history(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create();
        $entry = MediaEntry::factory()->completed()->for($user)->for($item, 'mediaItem')->create();
        $entry->watches()->create(['watched_at' => '2020-01-01']);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'watchlist'])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(WatchStatus::Watchlist, $entry->status);
        $this->assertCount(0, $entry->watches);
    }

    public function test_a_tv_entry_cannot_be_set_to_watchlist_or_watching_directly(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'watchlist'])
            ->assertSessionHasErrors('status');
    }

    public function test_a_tv_entry_can_be_manually_dropped_even_with_unwatched_aired_episodes(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 2, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 2, 'air_date' => now()->subWeek()]);
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'dropped'])
            ->assertRedirect();

        $this->assertSame(WatchStatus::Dropped, $entry->fresh()->status);
    }

    public function test_a_tv_entry_can_be_manually_completed_with_unwatched_filler_episodes(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 2, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 2, 'air_date' => now()->subWeek()]);
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'completed'])
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        // Manual TV completion doesn't log a blank movie-style watch — TV watch history lives on episodes.
        $this->assertCount(0, $entry->watches);
    }

    public function test_dropped_status_is_not_a_valid_movie_status(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create();
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)
            ->patch(route('media.entries.update', $entry), ['status' => 'dropped'])
            ->assertSessionHasErrors('status');
    }

    public function test_watching_an_episode_again_reclaims_a_manually_closed_out_show_from_the_next_episode_recompute(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 2, 'episodes_fetched_at' => now()]);
        $episode1 = MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 2, 'air_date' => now()->subWeek()]);
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Dropped]);

        $this->actingAs($user)->post(route('media.episodes.watch.store', $episode1))->assertRedirect();

        // Only one of two aired episodes is watched, so the episode-driven recompute
        // takes the entry back to "watching" — the manual "dropped" status doesn't stick
        // once episodes are touched again (chosen behavior: any episode interaction
        // means the user is back in it).
        $this->assertSame(WatchStatus::Watching, $entry->fresh()->status);
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
