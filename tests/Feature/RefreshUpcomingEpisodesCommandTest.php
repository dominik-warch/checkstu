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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RefreshUpcomingEpisodesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_discovers_a_newly_announced_season_and_the_coming_up_page_then_shows_it(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Returning Series']);
        $season1 = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 1, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season1, 'season')->create(['episode_number' => 1, 'air_date' => now()->subMonth()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);
        // No season 2 row at all yet — it only exists on TMDb so far.

        Http::fake([
            '*/tv/*/season/2*' => Http::response([
                'episodes' => [
                    ['id' => 20, 'episode_number' => 1, 'name' => 'Staffel 2 kommt', 'air_date' => now()->addDays(10)->toDateString()],
                ],
            ]),
            '*/tv/*' => Http::response([
                'id' => $item->tmdb_id,
                'name' => $item->title_de,
                'overview' => $item->overview,
                'poster_path' => $item->poster_path,
                'first_air_date' => $item->release_date?->toDateString(),
                'status' => 'Returning Series',
                'seasons' => [
                    ['id' => 100, 'season_number' => 1, 'name' => 'Staffel 1', 'episode_count' => 1, 'air_date' => null],
                    ['id' => 101, 'season_number' => 2, 'name' => 'Staffel 2', 'episode_count' => 1, 'air_date' => null],
                ],
                'translations' => ['translations' => []],
            ]),
        ]);

        $this->artisan('media:refresh-upcoming')->assertSuccessful();

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(
            fn (Assert $page) => $page
                ->has('items', 1)
                ->where('items.0.episode.season_number', 2)
                ->where('items.0.episode.name', 'Staffel 2 kommt'),
        );
    }

    public function test_it_skips_shows_nobody_has_on_a_watchlist(): void
    {
        MediaItem::factory()->tv()->create(['tv_status' => 'Returning Series']);

        Http::fake();

        $this->artisan('media:refresh-upcoming')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_it_skips_movies(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create();
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        Http::fake();

        $this->artisan('media:refresh-upcoming')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_it_skips_ended_shows(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Ended']);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        Http::fake();

        $this->artisan('media:refresh-upcoming')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_it_skips_shows_only_marked_completed(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Returning Series']);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Completed]);

        Http::fake();

        $this->artisan('media:refresh-upcoming')->assertSuccessful();
        Http::assertNothingSent();
    }
}
