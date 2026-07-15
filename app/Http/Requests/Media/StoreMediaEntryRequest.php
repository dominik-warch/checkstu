<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Enums\MediaType;
use App\Enums\WatchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Every family member tracks their own personal media log — no role restriction.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tmdb_id' => ['required', 'integer'],
            'type' => ['required', Rule::enum(MediaType::class)],
            'status' => [
                'required',
                Rule::enum(WatchStatus::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->input('type') === MediaType::Tv->value && $value !== WatchStatus::Watchlist->value) {
                        $fail('Eine Serie kann nur zur Merkliste hinzugefügt werden — einzelne Folgen werden separat abgehakt.');
                    }
                },
            ],
            'watched_at' => ['nullable', 'date'],
        ];
    }
}
