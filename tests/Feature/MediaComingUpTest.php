<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\MediaEntry;
use App\Models\MediaEpisode;
use App\Models\MediaItem;
use App\Models\MediaSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaComingUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_watchlist_movie_with_a_future_release_date_appears(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create(['title_de' => 'Kommender Film', 'release_date' => now()->addWeek()->toDateString()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(
            fn (Assert $page) => $page->has('items', 1)->where('items.0.media_item.title_de', 'Kommender Film')->where('items.0.episode', null),
        );
    }

    public function test_an_already_released_movie_does_not_appear(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create(['release_date' => now()->subWeek()->toDateString()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_a_movie_with_no_release_date_does_not_appear(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create(['release_date' => null]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_a_shows_upcoming_episode_in_the_last_cached_season_appears(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Ended']);
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 2, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 2, 'name' => 'Bald da', 'air_date' => now()->addDays(3)->toDateString()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(
            fn (Assert $page) => $page
                ->has('items', 1)
                ->where('items.0.episode.episode_number', 2)
                ->where('items.0.episode.name', 'Bald da')
                ->where('items.0.date', now()->addDays(3)->toDateString()),
        );
    }

    public function test_a_show_with_nothing_upcoming_does_not_appear(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Ended']);
        $season = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 1, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season, 'season')->create(['episode_number' => 1, 'air_date' => now()->subWeek()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_a_newly_announced_season_not_yet_discovered_does_not_appear_without_a_refresh(): void
    {
        // The page resolves purely from cache — discovering a season that only
        // exists on TMDb so far is RefreshUpcomingCacheAction's job (nightly, or
        // on add), not something a page load triggers. See
        // RefreshUpcomingEpisodesCommandTest for the discovery behavior itself.
        $user = User::factory()->create();
        $item = MediaItem::factory()->tv()->create(['tv_status' => 'Returning Series']);
        $season1 = MediaSeason::factory()->for($item, 'mediaItem')->create(['season_number' => 1, 'episode_count' => 1, 'episodes_fetched_at' => now()]);
        MediaEpisode::factory()->for($season1, 'season')->create(['episode_number' => 1, 'air_date' => now()->subMonth()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Watching]);
        // No season 2 row at all yet — it only exists on TMDb so far.

        Http::fake();

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
        Http::assertNothingSent();
    }

    public function test_completed_entries_are_excluded(): void
    {
        $user = User::factory()->create();
        $item = MediaItem::factory()->create(['release_date' => now()->addWeek()->toDateString()]);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create(['status' => WatchStatus::Completed]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_items_are_sorted_chronologically(): void
    {
        $user = User::factory()->create();
        $later = MediaItem::factory()->create(['title_de' => 'Später', 'release_date' => now()->addMonth()->toDateString()]);
        $sooner = MediaItem::factory()->create(['title_de' => 'Bald', 'release_date' => now()->addDay()->toDateString()]);
        MediaEntry::factory()->for($user)->for($later, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        MediaEntry::factory()->for($user)->for($sooner, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(
            fn (Assert $page) => $page->has('items', 2)->where('items.0.media_item.title_de', 'Bald')->where('items.1.media_item.title_de', 'Später'),
        );
    }

    public function test_it_never_shows_another_users_entries(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = MediaItem::factory()->create(['release_date' => now()->addWeek()->toDateString()]);
        MediaEntry::factory()->for($other, 'user')->for($item, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_a_watchlist_book_with_a_future_published_date_appears(): void
    {
        $user = User::factory()->create();
        $item = BookItem::factory()->create(['title' => 'Kommendes Buch', 'published_date' => now()->addWeek()->toDateString()]);
        BookEntry::factory()->for($user)->for($item, 'bookItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(
            fn (Assert $page) => $page->has('items', 1)->where('items.0.kind', 'book')->where('items.0.book_item.title', 'Kommendes Buch'),
        );
    }

    public function test_an_already_published_book_does_not_appear(): void
    {
        $user = User::factory()->create();
        $item = BookItem::factory()->create(['published_date' => now()->subWeek()->toDateString()]);
        BookEntry::factory()->for($user)->for($item, 'bookItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_a_completed_book_never_appears_even_with_a_future_published_date(): void
    {
        $user = User::factory()->create();
        $item = BookItem::factory()->create(['published_date' => now()->addWeek()->toDateString()]);
        BookEntry::factory()->for($user)->for($item, 'bookItem')->create(['status' => WatchStatus::Completed]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_books_and_media_are_sorted_together_chronologically(): void
    {
        $user = User::factory()->create();
        $movie = MediaItem::factory()->create(['title_de' => 'Später dran', 'release_date' => now()->addMonth()->toDateString()]);
        $book = BookItem::factory()->create(['title' => 'Bald dran', 'published_date' => now()->addDay()->toDateString()]);
        MediaEntry::factory()->for($user)->for($movie, 'mediaItem')->create(['status' => WatchStatus::Watchlist]);
        BookEntry::factory()->for($user)->for($book, 'bookItem')->create(['status' => WatchStatus::Watchlist]);

        $this->actingAs($user)->get(route('media.comingUp'))->assertInertia(
            fn (Assert $page) => $page->has('items', 2)->where('items.0.book_item.title', 'Bald dran')->where('items.1.media_item.title_de', 'Später dran'),
        );
    }
}
