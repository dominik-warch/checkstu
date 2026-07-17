<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\OpenLibrary\OpenLibraryClient;
use App\Support\OpenLibrary\OpenLibraryRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenLibraryClientTest extends TestCase
{
    public function test_search_prefers_the_matched_editions_title_over_the_works_title(): void
    {
        Http::fake([
            '*/search.json*' => Http::response([
                'docs' => [
                    [
                        'key' => '/works/OL5738148W',
                        'title' => 'The Final Empire',
                        'author_name' => ['Brandon Sanderson'],
                        'cover_i' => 14658160,
                        'editions' => [
                            'docs' => [
                                ['key' => '/books/OL57502463M', 'title' => 'Kinder des Nebels', 'cover_i' => 14838260],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $results = (new OpenLibraryClient)->search('Kinder des Nebels');

        $this->assertSame([
            'open_library_id' => '/books/OL57502463M',
            'title' => 'Kinder des Nebels',
            'authors' => 'Brandon Sanderson',
            'overview' => '',
            'thumbnail_url' => 'https://covers.openlibrary.org/b/id/14838260-M.jpg',
            'published_date' => null,
        ], $results[0]);
    }

    public function test_search_skips_a_work_with_no_matched_edition(): void
    {
        Http::fake([
            '*/search.json*' => Http::response([
                'docs' => [
                    ['key' => '/works/OL1W', 'title' => 'No Edition Matched', 'editions' => ['docs' => []]],
                    ['key' => '/works/OL2W', 'title' => 'Has An Edition', 'editions' => ['docs' => [['key' => '/books/OL2M', 'title' => 'Has An Edition']]]],
                ],
            ]),
        ]);

        $results = (new OpenLibraryClient)->search('anything');

        $this->assertCount(1, $results);
        $this->assertSame('/books/OL2M', $results[0]['open_library_id']);
    }

    public function test_details_chains_edition_work_and_author_into_one_record(): void
    {
        Http::fake([
            '*/books/OL57502463M.json' => Http::response([
                'key' => '/books/OL57502463M',
                'title' => 'Kinder des Nebels',
                'publish_date' => '01.03.2018',
                'covers' => [14838260],
                'works' => [['key' => '/works/OL5738148W']],
            ]),
            '*/works/OL5738148W.json' => Http::response([
                'title' => 'The Final Empire',
                'description' => 'For a thousand years ashes have fallen from the sky.',
                'authors' => [['author' => ['key' => '/authors/OL1394865A']]],
            ]),
            '*/authors/OL1394865A.json' => Http::response(['name' => 'Brandon Sanderson']),
        ]);

        $details = (new OpenLibraryClient)->details('/books/OL57502463M');

        $this->assertSame([
            'open_library_id' => '/books/OL57502463M',
            'title' => 'Kinder des Nebels',
            'authors' => 'Brandon Sanderson',
            'overview' => 'For a thousand years ashes have fallen from the sky.',
            'thumbnail_url' => 'https://covers.openlibrary.org/b/id/14838260-M.jpg',
            'published_date' => '2018-01-01',
        ], $details);
    }

    public function test_a_description_given_as_an_object_is_unwrapped(): void
    {
        Http::fake([
            '*/books/OL1M.json' => Http::response(['key' => '/books/OL1M', 'title' => 'X', 'works' => [['key' => '/works/OL1W']]]),
            '*/works/OL1W.json' => Http::response(['description' => ['type' => '/type/text', 'value' => 'An object-shaped description.']]),
        ]);

        $details = (new OpenLibraryClient)->details('/books/OL1M');

        $this->assertSame('An object-shaped description.', $details['overview']);
    }

    public function test_a_failed_response_throws(): void
    {
        Http::fake(['*/search.json*' => Http::response([], 500)]);

        $this->expectException(OpenLibraryRequestException::class);

        (new OpenLibraryClient)->search('anything');
    }

    public function test_a_transient_server_error_is_retried_and_can_succeed(): void
    {
        Http::fake([
            '*/search.json*' => Http::sequence()
                ->push([], 503)
                ->push(['docs' => [['key' => '/works/OL1W', 'title' => 'Retried', 'editions' => ['docs' => [['key' => '/books/OL1M', 'title' => 'Retried']]]]]], 200),
        ]);

        $results = (new OpenLibraryClient)->search('anything');

        $this->assertSame('Retried', $results[0]['title']);
        Http::assertSentCount(2);
    }

    public function test_a_client_error_is_not_retried(): void
    {
        Http::fake(['*/search.json*' => Http::response([], 400)]);

        try {
            (new OpenLibraryClient)->search('anything');
        } catch (OpenLibraryRequestException) {
            // expected
        }

        Http::assertSentCount(1);
    }
}
