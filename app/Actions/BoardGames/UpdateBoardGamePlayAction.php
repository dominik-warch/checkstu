<?php

declare(strict_types=1);

namespace App\Actions\BoardGames;

use App\Models\BoardGamePlay;
use Illuminate\Support\Facades\DB;

class UpdateBoardGamePlayAction
{
    /**
     * @param  array{board_game_id: int, played_at: string, notes?: string|null, players: list<array{user_id?: int|null, guest_name?: string|null, is_winner?: bool}>}  $data
     */
    public function handle(BoardGamePlay $play, array $data): BoardGamePlay
    {
        return DB::transaction(function () use ($play, $data): BoardGamePlay {
            $play->update([
                'board_game_id' => $data['board_game_id'],
                'played_at' => $data['played_at'],
                'notes' => filled($data['notes'] ?? null) ? $data['notes'] : null,
            ]);

            // Simplest correct approach: replace the player roster wholesale
            // rather than diffing — a play's participant list is small and
            // edited as a whole from the same dialog it was created in.
            $play->players()->delete();

            foreach ($data['players'] as $player) {
                $play->players()->create([
                    'user_id' => $player['user_id'] ?? null,
                    'guest_name' => filled($player['guest_name'] ?? null) ? $player['guest_name'] : null,
                    'is_winner' => $player['is_winner'] ?? false,
                ]);
            }

            return $play->fresh('players');
        });
    }
}
