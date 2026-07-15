<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaEpisode;
use App\Models\MediaItem;
use App\Models\MediaSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EpisodeWatchPromotionTest extends TestCase
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

    public function test_marking_the_first_episode_promotes_watchlist_to_watching(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)
            ->post(route('media.episodes.watch.store', $season->episodes->first()))
            ->assertRedirect();

        $entry = MediaEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Watching, $entry->status);
    }

    public function test_marking_all_aired_cached_episodes_completes_the_entry(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 2);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        foreach ($season->episodes as $episode) {
            $this->actingAs($user)->post(route('media.episodes.watch.store', $episode))->assertRedirect();
        }

        $entry = MediaEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertNotNull($entry->watched_at);
    }

    public function test_specials_season_does_not_block_completion(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 1);
        // An UNcached specials season would normally block completion — it must be excluded.
        MediaSeason::factory()->for($item, 'mediaItem')->specials()->create(['episode_count' => 3, 'episodes_fetched_at' => null]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.episodes.watch.store', $season->episodes->first()))->assertRedirect();

        $entry = MediaEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
    }

    public function test_an_uncached_trackable_season_blocks_completion(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season1 = $this->makeCachedSeason($item, 1, 1);
        MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 2, 'episode_count' => 5, 'episodes_fetched_at' => null]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->post(route('media.episodes.watch.store', $season1->episodes->first()))->assertRedirect();

        $entry = MediaEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Watching, $entry->status);
    }

    public function test_unmarking_demotes_symmetrically_back_to_watchlist(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 2);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        [$ep1, $ep2] = $season->episodes;
        $this->actingAs($user)->post(route('media.episodes.watch.store', $ep1));
        $this->actingAs($user)->post(route('media.episodes.watch.store', $ep2));
        $this->assertSame(WatchStatus::Completed, MediaEntry::where('user_id', $user->id)->first()->status);

        $this->actingAs($user)->delete(route('media.episodes.watch.destroy', $ep1))->assertRedirect();
        $this->assertSame(WatchStatus::Watching, MediaEntry::where('user_id', $user->id)->first()->status);

        $this->actingAs($user)->delete(route('media.episodes.watch.destroy', $ep2))->assertRedirect();
        $this->assertSame(WatchStatus::Watchlist, MediaEntry::where('user_id', $user->id)->first()->status);
    }

    public function test_a_user_cannot_mark_an_episode_for_a_show_not_in_their_library(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = $this->makeCachedSeason($item, 1, 1);
        // Deliberately no MediaEntry for this user.

        $this->actingAs($user)
            ->post(route('media.episodes.watch.store', $season->episodes->first()))
            ->assertForbidden();
    }

    public function test_fetching_season_episodes_hits_tmdb_once_and_caches(): void
    {
        Http::fake([
            '*/tv/*/season/*' => Http::response([
                'episodes' => [
                    ['id' => 1, 'episode_number' => 1, 'name' => 'Pilot', 'air_date' => '2020-01-01'],
                    ['id' => 2, 'episode_number' => 2, 'name' => 'Episode 2', 'air_date' => '2020-01-08'],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 2, 'episodes_fetched_at' => null]);

        $this->actingAs($user)->getJson(route('media.seasons.episodes', $season))->assertOk()->assertJsonCount(2, 'episodes');
        $this->actingAs($user)->getJson(route('media.seasons.episodes', $season));

        Http::assertSentCount(1);
        $this->assertSame(2, MediaEpisode::where('media_season_id', $season->id)->count());
        $this->assertNotNull($season->fresh()->episodes_fetched_at);
    }
}
