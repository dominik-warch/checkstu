<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_searching_returns_normalized_google_books_results(): void
    {
        Http::fake([
            '*/volumes?*' => Http::response([
                'items' => [
                    ['id' => 'zyTCAlFPjgYC', 'volumeInfo' => ['title' => 'Fänger im Roggen', 'authors' => ['J.D. Salinger']]],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => 'Fänger im Roggen']));

        $response->assertOk();
        $response->assertJsonPath('results.0.google_books_id', 'zyTCAlFPjgYC');
        $response->assertJsonPath('results.0.title', 'Fänger im Roggen');
    }

    public function test_an_empty_query_returns_no_results_without_calling_google_books(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => '']));

        $response->assertOk();
        $response->assertJson(['results' => []]);
        Http::assertNothingSent();
    }

    public function test_repeated_identical_searches_hit_google_books_only_once(): void
    {
        Http::fake(['*/volumes?*' => Http::response(['items' => []])]);
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('books.search', ['query' => 'Dune']));
        $this->actingAs($user)->getJson(route('books.search', ['query' => 'Dune']));

        Http::assertSentCount(1);
    }

    public function test_a_persistent_upstream_failure_is_flagged_as_unavailable_not_a_plain_error(): void
    {
        Http::fake(['*/volumes?*' => Http::response([], 503)]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => 'Kinder des Nebels']));

        $response->assertStatus(503);
        $response->assertJson(['results' => [], 'unavailable' => true]);
    }
}
