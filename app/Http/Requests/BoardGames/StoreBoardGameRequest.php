<?php

declare(strict_types=1);

namespace App\Http\Requests\BoardGames;

use App\Enums\BoardGameStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBoardGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Adding to the shared catalogue is open to every family member — no privacy dimension.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bgg_id' => ['nullable', 'integer'],
            'name' => ['required_without:bgg_id', 'nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(BoardGameStatus::class)],
        ];
    }
}
