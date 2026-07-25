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

class EpisodeWatchHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeWatchedEpisode(User $user): MediaEpisode
    {
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['episode_count' => 1, 'episodes_fetched_at' => now()]);
        $episode = MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEpisodeWatch::factory()->for($user)->for($episode, 'episode')->create(['watched_at' => '2026-01-01']);

        return $episode;
    }

    public function test_logging_another_watch_adds_a_second_row_without_touching_the_first(): void
    {
        $user = User::factory()->create();
        $episode = $this->makeWatchedEpisode($user);

        $this->actingAs($user)->post(route('media.episodes.watch.store', $episode))->assertRedirect();

        $watches = MediaEpisodeWatch::where('user_id', $user->id)->where('media_episode_id', $episode->id)->orderBy('id')->get();
        $this->assertCount(2, $watches);
        $this->assertSame('2026-01-01', $watches[0]->watched_at->toDateString());
    }

    public function test_a_specific_watchs_date_can_be_edited(): void
    {
        $user = User::factory()->create();
        $episode = $this->makeWatchedEpisode($user);
        $watch = MediaEpisodeWatch::where('media_episode_id', $episode->id)->first();

        $this->actingAs($user)
            ->patch(route('media.episodeWatches.update', $watch), ['watched_at' => '2026-02-02'])
            ->assertRedirect();

        $this->assertSame('2026-02-02', $watch->fresh()->watched_at->toDateString());
    }

    public function test_a_specific_watch_can_be_removed_leaving_the_episode_still_watched(): void
    {
        $user = User::factory()->create();
        $episode = $this->makeWatchedEpisode($user);
        $this->actingAs($user)->post(route('media.episodes.watch.store', $episode));
        $watches = MediaEpisodeWatch::where('media_episode_id', $episode->id)->orderBy('id')->get();

        $this->actingAs($user)->delete(route('media.episodeWatches.destroy', $watches[0]))->assertRedirect();

        $this->assertSame(1, MediaEpisodeWatch::where('media_episode_id', $episode->id)->count());
        $this->assertSame(WatchStatus::Completed, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_removing_the_last_watch_demotes_the_shows_status(): void
    {
        $user = User::factory()->create();
        $episode = $this->makeWatchedEpisode($user);
        $watch = MediaEpisodeWatch::where('media_episode_id', $episode->id)->first();

        $this->actingAs($user)->delete(route('media.episodeWatches.destroy', $watch))->assertRedirect();

        $this->assertSame(0, MediaEpisodeWatch::where('media_episode_id', $episode->id)->count());
        $this->assertSame(WatchStatus::Watchlist, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_a_user_cannot_edit_another_users_watch(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $episode = $this->makeWatchedEpisode($owner);
        $watch = MediaEpisodeWatch::where('media_episode_id', $episode->id)->first();

        $this->actingAs($intruder)
            ->patch(route('media.episodeWatches.update', $watch), ['watched_at' => '2026-02-02'])
            ->assertForbidden();
    }

    public function test_a_user_cannot_delete_another_users_watch(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $episode = $this->makeWatchedEpisode($owner);
        $watch = MediaEpisodeWatch::where('media_episode_id', $episode->id)->first();

        $this->actingAs($intruder)->delete(route('media.episodeWatches.destroy', $watch))->assertForbidden();
    }

    public function test_the_season_episodes_endpoint_exposes_the_full_watch_list(): void
    {
        $user = User::factory()->create();
        $episode = $this->makeWatchedEpisode($user);
        $this->actingAs($user)->post(route('media.episodes.watch.store', $episode));

        $response = $this->actingAs($user)->getJson(route('media.seasons.episodes', $episode->season));

        $response->assertOk()->assertJsonCount(2, 'episodes.0.watches');
    }
}
