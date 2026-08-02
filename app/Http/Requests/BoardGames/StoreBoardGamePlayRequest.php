<?php

declare(strict_types=1);

namespace App\Http\Requests\BoardGames;

use App\Http\Requests\BoardGames\Concerns\ValidatesBoardGamePlayers;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreBoardGamePlayRequest extends FormRequest
{
    use ValidatesBoardGamePlayers;

    public function authorize(): bool
    {
        // Logging a play is open to every family member — no privacy dimension.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'board_game_id' => ['required', 'integer', 'exists:board_games,id'],
            'played_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            ...$this->playerRules(),
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(fn (ValidatorContract $v) => $this->afterValidatePlayers($v));
    }
}
