<?php

declare(strict_types=1);

namespace App\Http\Requests\BoardGames\Concerns;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;

/**
 * Shared by Store/UpdateBoardGamePlayRequest: a play's "players" array must
 * have, per entry, exactly one of user_id/guest_name, and no user appearing
 * twice in the same play. App-level guard (not a DB constraint), same style
 * as the task_dependencies cycle check.
 */
trait ValidatesBoardGamePlayers
{
    /**
     * @return array<string, mixed>
     */
    protected function playerRules(): array
    {
        return [
            'players' => ['required', 'array', 'min:1'],
            'players.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'players.*.guest_name' => ['nullable', 'string', 'max:255'],
            'players.*.is_winner' => ['boolean'],
        ];
    }

    protected function afterValidatePlayers(ValidatorContract $validator): void
    {
        $players = $this->input('players', []);
        $seenUserIds = [];

        foreach ($players as $index => $player) {
            $userId = $player['user_id'] ?? null;
            $guestName = trim((string) ($player['guest_name'] ?? ''));

            if (($userId === null) === ($guestName === '')) {
                $validator->errors()->add(
                    "players.{$index}",
                    'Jede Person ist entweder ein Familienmitglied oder ein Gastname – nicht beides oder keins.',
                );

                continue;
            }

            if ($userId !== null) {
                if (in_array($userId, $seenUserIds, true)) {
                    $validator->errors()->add("players.{$index}", 'Diese Person ist bereits als Mitspieler eingetragen.');
                }
                $seenUserIds[] = $userId;
            }
        }
    }
}
