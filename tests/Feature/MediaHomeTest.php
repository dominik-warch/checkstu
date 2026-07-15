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

    public function test_it_falls_back_to_continue_watching_when_the_next_season_is_not_cached_yet(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create();
        MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episodes_fetched_at' => null]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)->get(route('media.home'))->assertInertia(
            fn (Assert $page) => $page->has('nextEpisodes', 1)->where('nextEpisodes.0.next_episode', null),
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
