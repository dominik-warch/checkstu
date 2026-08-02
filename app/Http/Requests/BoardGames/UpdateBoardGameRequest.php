<?php

declare(strict_types=1);

namespace App\Http\Requests\BoardGames;

use App\Enums\BoardGameStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBoardGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('boardGame'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BoardGameStatus::class)],
        ];
    }
}
