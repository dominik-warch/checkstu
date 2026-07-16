<?php

declare(strict_types=1);

namespace App\Http\Requests\Books;

use App\Enums\WatchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('entry'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([WatchStatus::Watchlist->value, WatchStatus::Completed->value])],
            'read_at' => ['nullable', 'date'],
        ];
    }
}
