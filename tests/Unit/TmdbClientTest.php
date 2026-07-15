<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\MediaType;
use App\Support\Tmdb\TmdbClient;
use App\Support\Tmdb\TmdbRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TmdbClientTest extends TestCase
{
    public function test_search_normalizes_movie_results(): void
    {
        Http::fake([
            '*/search/movie*' => Http::response([
                'results' => [
                    [
                        'id' => 550,
                        'title' => 'Fight Club',
                        'original_title' => 'Fight Club',
                        'overview' => 'Ein Insomniac...',
                        'poster_path' => '/poster.jpg',
                        'release_date' => '1999-10-15',
                    ],
                ],
            ]),
        ]);

        $results = (new TmdbClient)->search('Fight Club', MediaType::Movie);

        $this->assertSame([
            'tmdb_id' => 550,
            'type' => 'movie',
            'title' => 'Fight Club',
            'original_title' => 'Fight Club',
            'overview' => 'Ein Insomniac...',
            'poster_path' => '/poster.jpg',
            'release_date' => '1999-10-15',
        ], $results[0]);
    }

    public function test_details_picks_german_and_english_translations(): void
    {
        Http::fake([
            '*/tv/1399*' => Http::response([
                'id' => 1399,
                'name' => 'Game of Thrones',
                'overview' => 'Fallback overview',
                'poster_path' => '/got.jpg',
                'first_air_date' => '2011-04-17',
                'status' => 'Ended',
                'seasons' => [
                    ['id' => 1, 'season_number' => 0, 'name' => 'Specials', 'episode_count' => 2, 'air_date' => null],
                    ['id' => 2, 'season_number' => 1, 'name' => 'Season 1', 'episode_count' => 10, 'air_date' => '2011-04-17'],
                ],
                'translations' => [
                    'translations' => [
                        ['iso_639_1' => 'de', 'data' => ['name' => 'Game of Thrones (DE)', 'overview' => 'Deutsche Beschreibung']],
                        ['iso_639_1' => 'en', 'data' => ['name' => 'Game of Thrones', 'overview' => 'English overview']],
                    ],
                ],
            ]),
        ]);

        $details = (new TmdbClient)->details(1399, MediaType::Tv);

        $this->assertSame('Game of Thrones (DE)', $details['title_de']);
        $this->assertSame('Game of Thrones', $details['title_en']);
        $this->assertSame('Deutsche Beschreibung', $details['overview']);
        $this->assertSame('Ended', $details['tv_status']);
        $this->assertCount(2, $details['seasons']);
        $this->assertSame(1, $details['seasons'][1]['season_number']);
    }

    public function test_details_falls_back_when_no_translation_exists(): void
    {
        Http::fake([
            '*/movie/1*' => Http::response([
                'id' => 1,
                'title' => 'Untranslated Movie',
                'overview' => 'Only overview available',
                'poster_path' => null,
                'release_date' => '2020-01-01',
                'translations' => ['translations' => []],
            ]),
        ]);

        $details = (new TmdbClient)->details(1, MediaType::Movie);

        $this->assertSame('Untranslated Movie', $details['title_de']);
        $this->assertSame('Untranslated Movie', $details['title_en']);
        $this->assertSame('Only overview available', $details['overview']);
    }

    public function test_season_episodes_are_normalized(): void
    {
        Http::fake([
            '*/tv/1399/season/1*' => Http::response([
                'episodes' => [
                    ['id' => 10, 'episode_number' => 1, 'name' => 'Winter Is Coming', 'air_date' => '2011-04-17'],
                ],
            ]),
        ]);

        $episodes = (new TmdbClient)->seasonEpisodes(1399, 1);

        $this->assertSame([
            'tmdb_episode_id' => 10,
            'episode_number' => 1,
            'name' => 'Winter Is Coming',
            'air_date' => '2011-04-17',
        ], $episodes[0]);
    }

    public function test_a_failed_response_throws(): void
    {
        Http::fake([
            '*/search/movie*' => Http::response([], 500),
        ]);

        $this->expectException(TmdbRequestException::class);

        (new TmdbClient)->search('anything', MediaType::Movie);
    }
}
