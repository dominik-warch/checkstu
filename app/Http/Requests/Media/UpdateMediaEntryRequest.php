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
                Rule::in([WatchStatus::Watchlist->value, WatchStatus::Completed->value, WatchStatus::Dropped->value]),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $isTv = $this->route('entry')->mediaItem->type === MediaType::Tv;
                    $manualOverride = in_array($value, [WatchStatus::Completed->value, WatchStatus::Dropped->value], true);

                    // TV status is normally fully episode-driven (see RecomputeMediaEntryStatusAction);
                    // the one exception is manually closing out a show (finished ignoring filler
                    // episodes, or dropped outright) via this endpoint. Watchlist/Watching for TV
                    // stay episode-derived only — the next episode-watch recompute takes back over.
                    if ($isTv && ! $manualOverride) {
                        $fail('Der Status einer Serie wird über einzelne Folgen gesteuert.');
                    }

                    if (! $isTv && $value === WatchStatus::Dropped->value) {
                        $fail('„Abgebrochen" ist nur für Serien verfügbar.');
                    }
                },
            ],
        ];
    }
}
