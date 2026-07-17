<?php

declare(strict_types=1);

namespace App\Http\Requests\Books;

use App\Enums\WatchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Every family member tracks their own personal reading log — no role restriction.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'open_library_id' => ['required', 'string', 'max:64'],
            'status' => ['required', Rule::in([WatchStatus::Watchlist->value, WatchStatus::Completed->value])],
            'read_at' => ['nullable', 'date'],
        ];
    }
}
