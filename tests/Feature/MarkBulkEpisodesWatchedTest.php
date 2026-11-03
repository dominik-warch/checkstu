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
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarkBulkEpisodesWatchedTest extends TestCase
{
    use RefreshDatabase;

    private function makeCachedSeason(MediaItem $item, int $seasonNumber, int $episodeCount = 2): MediaSeason
    {
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create([
            'season_number' => $seasonNumber,
            'episode_count' => $episodeCount,
            'episodes_fetched_at' => now(),
        ]);

        for ($i = 1; $i <= $episodeCount; $i++) {
            MediaEpisode::factory()->for($season, 'season')->create([
                'episode_number' => $i,
                'air_date' => now()->subWeeks($episodeCount - $i + 1)->toDateString(),
            ]);
        }

        return $season;
    }

    public function test_marking_a_season_watched_marks_every_aired_episode_in_it(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 3);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.seasons.watchAll', $season))->assertRedirect();

        $this->assertSame(3, MediaEpisodeWatch::where('user_id', $user->id)->count());
        $this->assertSame(WatchStatus::Completed, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_marking_a_season_watched_leaves_the_watch_date_blank(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 2);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.seasons.watchAll', $season))->assertRedirect();

        $this->assertTrue(MediaEpisodeWatch::where('user_id', $user->id)->whereNull('watched_at')->exists());
        $this->assertSame(0, MediaEpisodeWatch::where('user_id', $user->id)->whereNotNull('watched_at')->count());
    }

    public function test_marking_a_season_watched_skips_unaired_episodes(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 2, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEpisode::factory()->for($season, 'season')->unaired()->create(['episode_number' => 2]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.seasons.watchAll', $season))->assertRedirect();

        // Completion only ever counts aired episodes — the unaired one simply isn't part
        // of the "must all be watched" set yet (same rule as RecomputeMediaEntryStatusAction
        // uses everywhere else), so watching everything aired so far is already "completed".
        $this->assertSame(1, MediaEpisodeWatch::where('user_id', $user->id)->count());
        $this->assertSame(WatchStatus::Completed, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_marking_a_season_watched_fetches_it_first_if_not_cached(): void
    {
        Http::fake([
            '*/tv/*/season/1*' => Http::response([
                'episodes' => [
                    ['id' => 1, 'episode_number' => 1, 'name' => 'Pilot', 'air_date' => now()->subDay()->toDateString()],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 1, 'episodes_fetched_at' => null]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.seasons.watchAll', $season))->assertRedirect();

        $this->assertSame(1, MediaEpisodeWatch::where('user_id', $user->id)->count());
        $this->assertNotNull($season->fresh()->episodes_fetched_at);
    }

    public function test_a_user_cannot_mark_a_season_watched_for_a_show_not_in_their_library(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 1);

        $this->actingAs($user)->post(route('media.seasons.watchAll', $season))->assertForbidden();
        $this->assertSame(0, MediaEpisodeWatch::count());
    }

    public function test_marking_the_whole_show_watched_marks_every_aired_episode_across_all_seasons(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $this->makeCachedSeason($item, 1, 2);
        $this->makeCachedSeason($item, 2, 2);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.items.watchAll', $item))->assertRedirect();

        $this->assertSame(4, MediaEpisodeWatch::where('user_id', $user->id)->count());
        $this->assertSame(WatchStatus::Completed, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_marking_the_whole_show_watched_leaves_the_watch_date_blank(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $this->makeCachedSeason($item, 1, 2);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.items.watchAll', $item))->assertRedirect();

        $this->assertSame(0, MediaEpisodeWatch::where('user_id', $user->id)->whereNotNull('watched_at')->count());
    }

    public function test_marking_the_whole_show_watched_fetches_any_uncached_seasons(): void
    {
        Http::fake([
            '*/tv/*/season/2*' => Http::response([
                'episodes' => [
                    ['id' => 2, 'episode_number' => 1, 'name' => 'S2E1', 'air_date' => now()->subDay()->toDateString()],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $this->makeCachedSeason($item, 1, 1);
        MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 2, 'episode_count' => 1, 'episodes_fetched_at' => null]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.items.watchAll', $item))->assertRedirect();

        $this->assertSame(2, MediaEpisodeWatch::where('user_id', $user->id)->count());
        $this->assertSame(WatchStatus::Completed, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_a_user_cannot_mark_a_whole_show_watched_that_is_not_in_their_library(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $this->makeCachedSeason($item, 1, 1);

        $this->actingAs($user)->post(route('media.items.watchAll', $item))->assertForbidden();
        $this->assertSame(0, MediaEpisodeWatch::count());
    }

    public function test_marking_the_whole_show_watched_excludes_specials_from_the_completion_check_but_still_marks_them(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $this->makeCachedSeason($item, 1, 1);
        $specials = MediaSeason::factory()->for($item, 'mediaItem')->specials()->create(['episode_count' => 1, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($specials, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.items.watchAll', $item))->assertRedirect();

        $this->assertSame(2, MediaEpisodeWatch::where('user_id', $user->id)->count());
        $this->assertSame(WatchStatus::Completed, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_undoing_mark_all_removes_only_the_watches_that_tap_created(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 3);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);
        $alreadyWatched = MediaEpisodeWatch::create([
            'user_id' => $user->id,
            'media_episode_id' => $season->episodes()->orderBy('episode_number')->first()->id,
            'watched_at' => '2026-10-01',
        ]);

        $response = $this->actingAs($user)->post(route('media.items.watchAll', $item));
        $created = $response->getSession()->get('undo')['watch_ids'];
        $this->assertCount(2, $created);
        $this->assertNotContains($alreadyWatched->id, $created);

        $this->actingAs($user)
            ->delete(route('media.items.unwatchAll', $item), ['watch_ids' => $created])
            ->assertRedirect();

        $this->assertSame([$alreadyWatched->id], MediaEpisodeWatch::where('user_id', $user->id)->pluck('id')->all());
        $this->assertSame(WatchStatus::Watching, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_undo_cannot_remove_someone_elses_watches(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 1);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create();
        $theirs = MediaEpisodeWatch::create(['user_id' => $other->id, 'media_episode_id' => $season->episodes()->first()->id, 'watched_at' => null]);

        $this->actingAs($user)->delete(route('media.items.unwatchAll', $item), ['watch_ids' => [$theirs->id]]);

        $this->assertModelExists($theirs);
    }

    public function test_undoing_a_manual_show_status_lets_the_episodes_decide_again(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 2);
        $entry = MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);
        MediaEpisodeWatch::create(['user_id' => $user->id, 'media_episode_id' => $season->episodes()->first()->id, 'watched_at' => null]);

        $this->actingAs($user)->patch(route('media.entries.update', $entry), ['status' => 'dropped']);
        $this->assertSame(WatchStatus::Dropped, $entry->fresh()->status);

        $this->actingAs($user)->post(route('media.entries.recompute', $entry))->assertRedirect();

        $this->assertSame(WatchStatus::Watching, $entry->fresh()->status);
    }

    public function test_only_the_owner_can_recompute_an_entry(): void
    {
        $entry = MediaEntry::factory()->for(User::factory())->for(MediaItem::factory()->tv(), 'mediaItem')->create();

        $this->actingAs(User::factory()->create())->post(route('media.entries.recompute', $entry))->assertForbidden();
    }
}
