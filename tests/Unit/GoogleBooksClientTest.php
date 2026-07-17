<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GoogleBooks\GoogleBooksClient;
use App\Support\GoogleBooks\GoogleBooksRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksClientTest extends TestCase
{
    public function test_search_normalizes_results(): void
    {
        Http::fake([
            '*/volumes?*' => Http::response([
                'items' => [
                    [
                        'id' => 'zyTCAlFPjgYC',
                        'volumeInfo' => [
                            'title' => 'The Catcher in the Rye',
                            'authors' => ['J.D. Salinger'],
                            'description' => 'A classic novel.',
                            'publishedDate' => '1991-05-01',
                            'imageLinks' => ['thumbnail' => 'http://books.google.com/thumb.jpg'],
                        ],
                    ],
                ],
            ]),
        ]);

        $results = (new GoogleBooksClient)->search('Catcher in the Rye');

        $this->assertSame([
            'google_books_id' => 'zyTCAlFPjgYC',
            'title' => 'The Catcher in the Rye',
            'authors' => 'J.D. Salinger',
            'overview' => 'A classic novel.',
            'thumbnail_url' => 'https://books.google.com/thumb.jpg',
            'published_date' => '1991-05-01',
        ], $results[0]);
    }

    public function test_search_handles_missing_optional_fields(): void
    {
        Http::fake([
            '*/volumes?*' => Http::response([
                'items' => [
                    ['id' => 'abc', 'volumeInfo' => ['title' => 'Untitled Draft']],
                ],
            ]),
        ]);

        $results = (new GoogleBooksClient)->search('anything');

        $this->assertSame('', $results[0]['authors']);
        $this->assertSame('', $results[0]['overview']);
        $this->assertNull($results[0]['thumbnail_url']);
        $this->assertNull($results[0]['published_date']);
    }

    public function test_a_year_only_published_date_is_normalized(): void
    {
        Http::fake([
            '*/volumes?*' => Http::response([
                'items' => [['id' => 'abc', 'volumeInfo' => ['title' => 'Old Book', 'publishedDate' => '1955']]],
            ]),
        ]);

        $results = (new GoogleBooksClient)->search('anything');

        $this->assertSame('1955-01-01', $results[0]['published_date']);
    }

    public function test_details_normalizes_a_single_volume(): void
    {
        Http::fake([
            '*/volumes/zyTCAlFPjgYC*' => Http::response([
                'id' => 'zyTCAlFPjgYC',
                'volumeInfo' => ['title' => 'The Catcher in the Rye', 'authors' => ['J.D. Salinger']],
            ]),
        ]);

        $details = (new GoogleBooksClient)->details('zyTCAlFPjgYC');

        $this->assertSame('zyTCAlFPjgYC', $details['google_books_id']);
        $this->assertSame('The Catcher in the Rye', $details['title']);
    }

    public function test_a_failed_response_throws(): void
    {
        Http::fake([
            '*/volumes?*' => Http::response([], 500),
        ]);

        $this->expectException(GoogleBooksRequestException::class);

        (new GoogleBooksClient)->search('anything');
    }

    public function test_a_transient_server_error_is_retried_and_can_succeed(): void
    {
        Http::fake([
            '*/volumes?*' => Http::sequence()
                ->push([], 503)
                ->push(['items' => [['id' => 'abc', 'volumeInfo' => ['title' => 'Retried Book']]]], 200),
        ]);

        $results = (new GoogleBooksClient)->search('anything');

        $this->assertSame('Retried Book', $results[0]['title']);
        Http::assertSentCount(2);
    }

    public function test_a_client_error_is_not_retried(): void
    {
        Http::fake([
            '*/volumes?*' => Http::response([], 400),
        ]);

        try {
            (new GoogleBooksClient)->search('anything');
        } catch (GoogleBooksRequestException) {
            // expected
        }

        Http::assertSentCount(1);
    }
}
