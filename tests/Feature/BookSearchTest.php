<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BookEntry;
use App\Models\BookItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSearch(): void
    {
        Http::fake([
            '*/search.json*' => Http::response([
                'docs' => [
                    [
                        'key' => '/works/OL1W',
                        'title' => 'Fänger im Roggen',
                        'author_name' => ['J.D. Salinger'],
                        'editions' => ['docs' => [['key' => '/books/OL1M', 'title' => 'Fänger im Roggen']]],
                    ],
                ],
            ]),
        ]);
    }

    public function test_searching_returns_normalized_open_library_results(): void
    {
        $this->fakeSearch();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => 'Fänger im Roggen']));

        $response->assertOk();
        $response->assertJsonPath('results.0.open_library_id', '/books/OL1M');
        $response->assertJsonPath('results.0.title', 'Fänger im Roggen');
        $response->assertJsonPath('results.0.in_library', false);
    }

    public function test_a_result_already_in_the_users_library_is_flagged(): void
    {
        $this->fakeSearch();
        $user = User::factory()->create();
        $item = BookItem::factory()->create(['open_library_id' => '/books/OL1M']);
        BookEntry::factory()->for($user)->for($item, 'bookItem')->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => 'Fänger im Roggen']));

        $response->assertJsonPath('results.0.in_library', true);
    }

    public function test_another_users_library_does_not_flag_a_result_as_owned(): void
    {
        $this->fakeSearch();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $item = BookItem::factory()->create(['open_library_id' => '/books/OL1M']);
        BookEntry::factory()->for($otherUser)->for($item, 'bookItem')->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => 'Fänger im Roggen']));

        $response->assertJsonPath('results.0.in_library', false);
    }

    public function test_an_empty_query_returns_no_results_without_calling_open_library(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => '']));

        $response->assertOk();
        $response->assertJson(['results' => []]);
        Http::assertNothingSent();
    }

    public function test_repeated_identical_searches_hit_open_library_only_once(): void
    {
        Http::fake(['*/search.json*' => Http::response(['docs' => []])]);
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('books.search', ['query' => 'Dune']));
        $this->actingAs($user)->getJson(route('books.search', ['query' => 'Dune']));

        Http::assertSentCount(1);
    }

    public function test_a_persistent_upstream_failure_is_flagged_as_unavailable_not_a_plain_error(): void
    {
        Http::fake(['*/search.json*' => Http::response([], 503)]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('books.search', ['query' => 'Kinder des Nebels']));

        $response->assertStatus(503);
        $response->assertJson(['results' => [], 'unavailable' => true]);
    }
}
