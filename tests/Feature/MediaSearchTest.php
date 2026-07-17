<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MediaEntry;
use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_movies_returns_normalized_tmdb_results(): void
    {
        Http::fake([
            '*/search/movie*' => Http::response([
                'results' => [
                    ['id' => 550, 'title' => 'Fight Club', 'original_title' => 'Fight Club', 'overview' => '...', 'poster_path' => '/p.jpg', 'release_date' => '1999-10-15'],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('media.search', ['query' => 'Fight Club', 'type' => 'movie']));

        $response->assertOk();
        $response->assertJsonPath('results.0.tmdb_id', 550);
        $response->assertJsonPath('results.0.title', 'Fight Club');
        $response->assertJsonPath('results.0.in_library', false);
    }

    public function test_a_result_already_in_the_users_library_is_flagged(): void
    {
        Http::fake([
            '*/search/movie*' => Http::response([
                'results' => [
                    ['id' => 550, 'title' => 'Fight Club', 'original_title' => 'Fight Club', 'overview' => '...', 'poster_path' => '/p.jpg', 'release_date' => '1999-10-15'],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $item = MediaItem::factory()->create(['tmdb_id' => 550, 'type' => 'movie']);
        MediaEntry::factory()->for($user)->for($item, 'mediaItem')->create();

        $response = $this->actingAs($user)->getJson(route('media.search', ['query' => 'Fight Club', 'type' => 'movie']));

        $response->assertJsonPath('results.0.in_library', true);
    }

    public function test_another_users_library_does_not_flag_a_result_as_owned(): void
    {
        Http::fake([
            '*/search/movie*' => Http::response([
                'results' => [
                    ['id' => 550, 'title' => 'Fight Club', 'original_title' => 'Fight Club', 'overview' => '...', 'poster_path' => '/p.jpg', 'release_date' => '1999-10-15'],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = MediaItem::factory()->create(['tmdb_id' => 550, 'type' => 'movie']);
        MediaEntry::factory()->for($otherUser)->for($item, 'mediaItem')->create();

        $response = $this->actingAs($user)->getJson(route('media.search', ['query' => 'Fight Club', 'type' => 'movie']));

        $response->assertJsonPath('results.0.in_library', false);
    }

    public function test_an_empty_query_returns_no_results_without_calling_tmdb(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('media.search', ['query' => '', 'type' => 'movie']));

        $response->assertOk();
        $response->assertJson(['results' => []]);
        Http::assertNothingSent();
    }

    public function test_repeated_identical_searches_hit_tmdb_only_once(): void
    {
        Http::fake([
            '*/search/tv*' => Http::response(['results' => []]),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('media.search', ['query' => 'Severance', 'type' => 'tv']));
        $this->actingAs($user)->getJson(route('media.search', ['query' => 'Severance', 'type' => 'tv']));

        Http::assertSentCount(1);
    }
}
