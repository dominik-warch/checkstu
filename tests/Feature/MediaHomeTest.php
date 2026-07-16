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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_next_unwatched_episode_for_a_watching_show(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['title_de' => 'Testserie']);
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episodes_fetched_at' => now()]);
        $ep1 = MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'name' => 'Erste Folge', 'air_date' => now()->subWeek()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 2, 'name' => 'Zweite Folge', 'air_date' => now()->subDay()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);
        MediaEpisodeWatch::factory()->for($user)->for($ep1, 'episode')->create();

        $this->actingAs($user)->get(route('media.home'))->assertInertia(
            fn (Assert $page) => $page
                ->component('media/home')
                ->has('nextEpisodes', 1)
                ->where('nextEpisodes.0.media_item.title_de', 'Testserie')
                ->where('nextEpisodes.0.next_episode.episode_number', 2)
                ->where('nextEpisodes.0.next_episode.name', 'Zweite Folge'),
        );
    }

    public function test_marking_the_exposed_next_episode_id_watched_advances_the_widget(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'name' => 'Erste Folge', 'air_date' => now()->subWeek()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 2, 'name' => 'Zweite Folge', 'air_date' => now()->subDay()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $before = $this->actingAs($user)->get(route('media.home'));
        $nextEpisodeId = $before->viewData('page')['props']['nextEpisodes'][0]['next_episode']['id'];

        $this->actingAs($user)->post(route('media.episodes.watch.store', $nextEpisodeId))->assertRedirect();

        $this->actingAs($user)->get(route('media.home'))->assertInertia(
            fn (Assert $page) => $page->where('nextEpisodes.0.next_episode.episode_number', 2)->where('nextEpisodes.0.next_episode.name', 'Zweite Folge'),
        );
    }

    public function test_it_auto_fetches_an_uncached_seasons_episodes_so_the_next_episode_is_visible_immediately(): void
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
        MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 1, 'episodes_fetched_at' => null]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)->get(route('media.home'))->assertInertia(
            fn (Assert $page) => $page
                ->has('nextEpisodes', 1)
                ->where('nextEpisodes.0.next_episode.season_number', 1)
                ->where('nextEpisodes.0.next_episode.episode_number', 1)
                ->where('nextEpisodes.0.next_episode.name', 'Pilot'),
        );

        $this->assertNotNull(MediaSeason::first()->episodes_fetched_at);
    }

    public function test_the_first_episode_of_a_not_yet_discovered_new_season_becomes_visible_automatically(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Returning Series']);
        $season1 = MediaSeason::factory()->for($item, 'mediaItem')->create([
            'season_number' => 1, 'episode_count' => 1, 'episodes_fetched_at' => now(),
        ]);
        $ep1 = MediaEpisode::factory()->for($season1, 'season')->create(['episode_number' => 1, 'air_date' => now()->subMonth()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);
        MediaEpisodeWatch::factory()->for($user)->for($ep1, 'episode')->create();
        // Deliberately no season 2 row at all — it only exists on TMDb so far.

        Http::fake([
            '*/tv/*/season/2*' => Http::response([
                'episodes' => [
                    ['id' => 20, 'episode_number' => 1, 'name' => 'Neue Staffel, Folge 1', 'air_date' => now()->subDay()->toDateString()],
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

        $this->actingAs($user)->get(route('media.home'))->assertInertia(
            fn (Assert $page) => $page
                ->has('nextEpisodes', 1)
                ->where('nextEpisodes.0.next_episode.season_number', 2)
                ->where('nextEpisodes.0.next_episode.episode_number', 1)
                ->where('nextEpisodes.0.next_episode.name', 'Neue Staffel, Folge 1'),
        );

        $this->assertDatabaseHas('media_seasons', ['media_item_id' => $item->id, 'season_number' => 2]);
    }

    public function test_a_show_with_nothing_new_available_is_excluded(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Returning Series']);
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create([
            'season_number' => 1, 'episode_count' => 1, 'episodes_fetched_at' => now(),
        ]);
        $ep1 = MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subMonth()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);
        MediaEpisodeWatch::factory()->for($user)->for($ep1, 'episode')->create();

        Http::fake([
            '*/tv/*' => Http::response([
                'id' => $item->tmdb_id,
                'name' => $item->title_de,
                'overview' => $item->overview,
                'poster_path' => $item->poster_path,
                'first_air_date' => $item->release_date?->toDateString(),
                'status' => 'Returning Series',
                'seasons' => [
                    ['id' => 100, 'season_number' => 1, 'name' => 'Staffel 1', 'episode_count' => 1, 'air_date' => null],
                ],
                'translations' => ['translations' => []],
            ]),
        ]);

        $this->actingAs($user)->get(route('media.home'))->assertInertia(fn (Assert $page) => $page->has('nextEpisodes', 0));
    }

    public function test_shows_are_sorted_by_most_recently_watched_episode(): void
    {
        $user = User::factory()->create();

        $itemA = MediaItem::factory()->tv()->create(['title_de' => 'Serie A']);
        $seasonA = MediaSeason::factory()->for($itemA, 'mediaItem')->create(['episode_count' => 2, 'episodes_fetched_at' => now()]);
        $epA1 = MediaEpisode::factory()->for($seasonA, 'season')->create(['episode_number' => 1, 'air_date' => now()->subMonth()]);
        MediaEpisode::factory()->for($seasonA, 'season')->create(['episode_number' => 2, 'air_date' => now()->subDay()]);
        MediaEntry::factory()->for($user)->for($itemA, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $itemB = MediaItem::factory()->tv()->create(['title_de' => 'Serie B']);
        $seasonB = MediaSeason::factory()->for($itemB, 'mediaItem')->create(['episode_count' => 2, 'episodes_fetched_at' => now()]);
        $epB1 = MediaEpisode::factory()->for($seasonB, 'season')->create(['episode_number' => 1, 'air_date' => now()->subMonth()]);
        MediaEpisode::factory()->for($seasonB, 'season')->create(['episode_number' => 2, 'air_date' => now()->subDay()]);
        MediaEntry::factory()->for($user)->for($itemB, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        // Watch A's episode an hour "ago", then B's just now — B checked more recently should sort first.
        Carbon::setTestNow(now()->subHour());
        MediaEpisodeWatch::factory()->for($user)->for($epA1, 'episode')->create();

        Carbon::setTestNow();
        MediaEpisodeWatch::factory()->for($user)->for($epB1, 'episode')->create();

        $this->actingAs($user)->get(route('media.home'))->assertInertia(
            fn (Assert $page) => $page
                ->has('nextEpisodes', 2)
                ->where('nextEpisodes.0.media_item.title_de', 'Serie B')
                ->where('nextEpisodes.1.media_item.title_de', 'Serie A'),
        );
    }

    public function test_it_excludes_watchlist_and_completed_entries(): void
    {
        $user = User::factory()->create();
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->tv(), 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEntry::factory()->for($user)->for(MediaItem::factory()->tv(), 'mediaItem')->create(['status' => WatchStatus::Completed]);

        $this->actingAs($user)->get(route('media.home'))->assertInertia(fn (Assert $page) => $page->has('nextEpisodes', 0));
    }

    public function test_it_never_shows_another_users_entries(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        MediaEntry::factory()->for($other, 'user')->for(MediaItem::factory()->tv(), 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)->get(route('media.home'))->assertInertia(fn (Assert $page) => $page->has('nextEpisodes', 0));
    }
}
