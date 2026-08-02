<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BoardGame;
use App\Models\BoardGamePlay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardGamePlayLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_play_can_be_logged_with_a_mix_of_app_users_and_a_guest(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $game = BoardGame::factory()->create();

        $response = $this->actingAs($user)->post(route('boardgames.plays.store'), [
            'board_game_id' => $game->id,
            'played_at' => '2026-08-01',
            'players' => [
                ['user_id' => $user->id, 'is_winner' => true],
                ['user_id' => $other->id, 'is_winner' => false],
                ['guest_name' => 'Tim', 'is_winner' => false],
            ],
        ]);

        $response->assertRedirect();
        $play = BoardGamePlay::first();
        $this->assertSame($game->id, $play->board_game_id);
        $this->assertSame('2026-08-01', $play->played_at->toDateString());
        $this->assertSame($user->id, $play->logged_by);
        $this->assertCount(3, $play->players);
        $this->assertDatabaseHas('board_game_play_players', [
            'board_game_play_id' => $play->id,
            'user_id' => $user->id,
            'is_winner' => true,
        ]);
        $this->assertDatabaseHas('board_game_play_players', [
            'board_game_play_id' => $play->id,
            'guest_name' => 'Tim',
            'user_id' => null,
        ]);
    }

    public function test_a_player_needs_exactly_one_of_user_id_or_guest_name(): void
    {
        $user = User::factory()->create();
        $game = BoardGame::factory()->create();

        // Neither given.
        $this->actingAs($user)->post(route('boardgames.plays.store'), [
            'board_game_id' => $game->id,
            'played_at' => '2026-08-01',
            'players' => [['is_winner' => false]],
        ])->assertSessionHasErrors('players.0');

        // Both given.
        $this->actingAs($user)->post(route('boardgames.plays.store'), [
            'board_game_id' => $game->id,
            'played_at' => '2026-08-01',
            'players' => [['user_id' => $user->id, 'guest_name' => 'Tim', 'is_winner' => false]],
        ])->assertSessionHasErrors('players.0');
    }

    public function test_the_same_user_cannot_be_listed_twice_in_one_play(): void
    {
        $user = User::factory()->create();
        $game = BoardGame::factory()->create();

        $this->actingAs($user)->post(route('boardgames.plays.store'), [
            'board_game_id' => $game->id,
            'played_at' => '2026-08-01',
            'players' => [
                ['user_id' => $user->id, 'is_winner' => false],
                ['user_id' => $user->id, 'is_winner' => true],
            ],
        ])->assertSessionHasErrors('players.1');
    }

    public function test_at_least_one_player_is_required(): void
    {
        $user = User::factory()->create();
        $game = BoardGame::factory()->create();

        $this->actingAs($user)->post(route('boardgames.plays.store'), [
            'board_game_id' => $game->id,
            'played_at' => '2026-08-01',
            'players' => [],
        ])->assertSessionHasErrors('players');
    }

    public function test_editing_a_play_replaces_the_player_roster(): void
    {
        $user = User::factory()->create();
        $game = BoardGame::factory()->create();
        $play = BoardGamePlay::factory()->for($game, 'boardGame')->create(['logged_by' => $user->id]);
        $play->players()->create(['guest_name' => 'Old Guest', 'is_winner' => false]);

        $this->actingAs($user)->patch(route('boardgames.plays.update', $play), [
            'board_game_id' => $game->id,
            'played_at' => '2026-08-02',
            'players' => [['user_id' => $user->id, 'is_winner' => true]],
        ])->assertRedirect();

        $play->refresh();
        $this->assertCount(1, $play->players);
        $this->assertSame($user->id, $play->players->first()->user_id);
        $this->assertSame('2026-08-02', $play->played_at->toDateString());
    }

    public function test_only_the_logger_or_an_admin_can_edit_or_delete_a_play(): void
    {
        $logger = User::factory()->create();
        $otherMember = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $game = BoardGame::factory()->create();
        $play = BoardGamePlay::factory()->for($game, 'boardGame')->create(['logged_by' => $logger->id]);
        $play->players()->create(['user_id' => $logger->id, 'is_winner' => false]);

        $payload = ['board_game_id' => $game->id, 'played_at' => '2026-08-01', 'players' => [['user_id' => $logger->id, 'is_winner' => false]]];

        $this->actingAs($otherMember)->patch(route('boardgames.plays.update', $play), $payload)->assertForbidden();
        $this->actingAs($otherMember)->delete(route('boardgames.plays.destroy', $play))->assertForbidden();

        $this->actingAs($logger)->patch(route('boardgames.plays.update', $play), $payload)->assertRedirect();
        $this->actingAs($admin)->delete(route('boardgames.plays.destroy', $play))->assertRedirect();
        $this->assertDatabaseMissing('board_game_plays', ['id' => $play->id]);
    }

    public function test_a_guest_can_log_a_play(): void
    {
        $guest = User::factory()->guest()->create();
        $game = BoardGame::factory()->create();

        $this->actingAs($guest)->post(route('boardgames.plays.store'), [
            'board_game_id' => $game->id,
            'played_at' => '2026-08-01',
            'players' => [['user_id' => $guest->id, 'is_winner' => true]],
        ])->assertRedirect();

        $this->assertDatabaseHas('board_game_plays', ['board_game_id' => $game->id, 'logged_by' => $guest->id]);
    }
}
