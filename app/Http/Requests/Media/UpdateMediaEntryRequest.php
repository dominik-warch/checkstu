<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Enums\MediaType;
use App\Enums\WatchStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaEntryRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in([WatchStatus::Watchlist->value, WatchStatus::Completed->value]),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    // TV status is fully episode-driven (see RecomputeMediaEntryStatusAction) —
                    // this endpoint is movie-only.
                    if ($this->route('entry')->mediaItem->type === MediaType::Tv) {
                        $fail('Der Status einer Serie wird über einzelne Folgen gesteuert.');
                    }
                },
            ],
            'watched_at' => ['nullable', 'date'],
        ];
    }
}
