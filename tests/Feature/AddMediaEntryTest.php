<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddMediaEntryTest extends TestCase
{
    use RefreshDatabase;

    private function fakeMovieDetails(): void
    {
        Http::fake([
            '*/movie/550*' => Http::response([
                'id' => 550,
                'title' => 'Fight Club',
                'overview' => 'A depressed man...',
                'poster_path' => '/p.jpg',
                'release_date' => '1999-10-15',
                'translations' => ['translations' => [
                    ['iso_639_1' => 'de', 'data' => ['title' => 'Fight Club', 'overview' => 'Ein depressiver Mann...']],
                    ['iso_639_1' => 'en', 'data' => ['title' => 'Fight Club', 'overview' => 'A depressed man...']],
                ]],
            ]),
        ]);
    }

    private function fakeTvDetails(): void
    {
        Http::fake([
            '*/tv/1399*' => Http::response([
                'id' => 1399,
                'name' => 'Game of Thrones',
                'overview' => 'Fallback',
                'poster_path' => '/got.jpg',
                'first_air_date' => '2011-04-17',
                'status' => 'Ended',
                'seasons' => [
                    ['id' => 1, 'season_number' => 1, 'name' => 'Season 1', 'episode_count' => 10, 'air_date' => '2011-04-17'],
                ],
                'translations' => ['translations' => [
                    ['iso_639_1' => 'de', 'data' => ['name' => 'Game of Thrones', 'overview' => 'Deutsche Beschreibung']],
                    ['iso_639_1' => 'en', 'data' => ['name' => 'Game of Thrones', 'overview' => 'English']],
                ]],
            ]),
        ]);
    }

    public function test_adding_a_movie_to_the_watchlist_creates_item_and_entry(): void
    {
        $this->fakeMovieDetails();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('media.entries.store'), ['tmdb_id' => 550, 'type' => 'movie', 'status' => 'watchlist'])
            ->assertRedirect();

        $item = MediaItem::firstWhere('tmdb_id', 550);
        $this->assertNotNull($item);
        $this->assertSame('Fight Club', $item->title_de);

        $entry = MediaEntry::where('user_id', $user->id)->where('media_item_id', $item->id)->first();
        $this->assertSame(WatchStatus::Watchlist, $entry->status);
    }

    public function test_marking_a_movie_watched_directly_sets_completed_status_and_date(): void
    {
        $this->fakeMovieDetails();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('media.entries.store'), [
            'tmdb_id' => 550, 'type' => 'movie', 'status' => 'completed', 'watched_at' => '2026-07-10',
        ])->assertRedirect();

        $entry = MediaEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
        $this->assertSame('2026-07-10', $entry->watched_at->toDateString());
    }

    public function test_a_show_cannot_be_added_as_directly_completed(): void
    {
        $this->fakeTvDetails();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('media.entries.store'), ['tmdb_id' => 1399, 'type' => 'tv', 'status' => 'completed'])
            ->assertSessionHasErrors('status');
    }

    public function test_adding_a_show_creates_item_with_season_summaries_but_no_episodes_yet(): void
    {
        $this->fakeTvDetails();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('media.entries.store'), ['tmdb_id' => 1399, 'type' => 'tv', 'status' => 'watchlist'])
            ->assertRedirect();

        $item = MediaItem::firstWhere('tmdb_id', 1399);
        $this->assertCount(1, $item->seasons);
        $this->assertSame(0, $item->seasons->first()->episodes()->count());
    }

    public function test_adding_the_same_item_twice_does_not_call_tmdb_again(): void
    {
        $this->fakeMovieDetails();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('media.entries.store'), ['tmdb_id' => 550, 'type' => 'movie', 'status' => 'watchlist']);
        $this->actingAs($user)->post(route('media.entries.store'), ['tmdb_id' => 550, 'type' => 'movie', 'status' => 'completed']);

        Http::assertSentCount(1);
        $this->assertSame(1, MediaItem::count());
        $entry = MediaEntry::where('user_id', $user->id)->first();
        $this->assertSame(WatchStatus::Completed, $entry->status);
    }
}
