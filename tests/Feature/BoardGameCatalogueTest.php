<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BoardGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BoardGameCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private const THING_XML = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <items termsofuse="https://boardgamegeek.com/xmlapi/termsofuse">
        <item type="boardgame" id="13">
        <thumbnail>https://cf.geekdo-images.com/thumb.jpg</thumbnail>
        <name type="primary" sortindex="1" value="Catan" />
        <yearpublished value="1995" />
        <minplayers value="3" />
        <maxplayers value="4" />
        <playingtime value="120" />
        </item>
        </items>
        XML;

    public function test_adding_a_game_from_bgg_search_fetches_and_stores_details(): void
    {
        Http::fake(['*/thing*' => Http::response(self::THING_XML, 200, ['Content-Type' => 'application/xml'])]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('boardgames.store'), ['bgg_id' => 13, 'status' => 'owned'])
            ->assertRedirect();

        $this->assertDatabaseHas('board_games', [
            'bgg_id' => 13,
            'name' => 'Catan',
            'min_players' => 3,
            'max_players' => 4,
            'status' => 'owned',
            'added_by' => $user->id,
        ]);
    }

    public function test_re_adding_a_known_bgg_game_refreshes_it_instead_of_duplicating(): void
    {
        Http::fake(['*/thing*' => Http::response(self::THING_XML, 200, ['Content-Type' => 'application/xml'])]);
        $user = User::factory()->create();
        BoardGame::factory()->create(['bgg_id' => 13, 'status' => 'wishlist']);

        $this->actingAs($user)->post(route('boardgames.store'), ['bgg_id' => 13, 'status' => 'owned']);

        $this->assertSame(1, BoardGame::where('bgg_id', 13)->count());
        $this->assertDatabaseHas('board_games', ['bgg_id' => 13, 'status' => 'owned']);
    }

    public function test_a_game_can_be_added_manually_without_a_bgg_lookup(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('boardgames.store'), ['name' => 'Hausregel-Spiel', 'status' => 'wishlist'])
            ->assertRedirect();

        Http::assertNothingSent();
        $this->assertDatabaseHas('board_games', ['bgg_id' => null, 'name' => 'Hausregel-Spiel', 'status' => 'wishlist']);
    }

    public function test_manual_entry_without_a_name_or_bgg_id_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('boardgames.store'), ['status' => 'wishlist'])
            ->assertSessionHasErrors('name');
    }

    public function test_status_can_be_toggled_between_owned_and_wishlist(): void
    {
        $user = User::factory()->create();
        $game = BoardGame::factory()->create(['status' => 'wishlist']);

        $this->actingAs($user)->patch(route('boardgames.update', $game), ['status' => 'owned'])
            ->assertRedirect();

        $this->assertDatabaseHas('board_games', ['id' => $game->id, 'status' => 'owned']);
    }

    public function test_catalogue_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        BoardGame::factory()->create(['name' => 'Owned Game', 'status' => 'owned']);
        BoardGame::factory()->wishlist()->create(['name' => 'Wishlist Game']);

        $response = $this->actingAs($user)->get(route('boardgames.catalogue', ['status' => 'owned']));

        $response->assertInertia(fn ($page) => $page
            ->component('board-games/catalogue')
            ->has('games', 1)
            ->where('games.0.name', 'Owned Game'));
    }

    public function test_search_reports_unavailable_instead_of_an_empty_result_when_bgg_is_unreachable(): void
    {
        Http::fake(['*/search*' => Http::response('Unauthorized.', 401)]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('boardgames.search', ['query' => 'Catan']));

        $response->assertStatus(503);
        $response->assertJson(['results' => [], 'unavailable' => true]);
    }

    public function test_only_an_admin_can_delete_a_game(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $game = BoardGame::factory()->create();

        $this->actingAs($member)->delete(route('boardgames.destroy', $game))->assertForbidden();
        $this->assertDatabaseHas('board_games', ['id' => $game->id]);

        $this->actingAs($admin)->delete(route('boardgames.destroy', $game))->assertRedirect();
        $this->assertDatabaseMissing('board_games', ['id' => $game->id]);
    }

    public function test_a_guest_can_view_the_catalogue(): void
    {
        $guest = User::factory()->guest()->create();
        BoardGame::factory()->create();

        $this->actingAs($guest)->get(route('boardgames.catalogue'))->assertOk();
    }
}
