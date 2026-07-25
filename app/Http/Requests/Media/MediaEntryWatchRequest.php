<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Enums\WatchStatus;
use App\Models\MediaEntry;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by store (route-bound {entry}) and update/destroy (route-bound
 * {watch}) — a watch only ever makes sense once the movie itself is
 * completed (see UpdateMediaEntryAction, which wipes watches when a movie
 * leaves that status).
 */
class MediaEntryWatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('entry');
        if ($entry instanceof MediaEntry) {
            return $this->user()->can('update', $entry) && $entry->status === WatchStatus::Completed;
        }

        return $this->route('watch')->entry->user_id === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'watched_at' => ['nullable', 'date'],
        ];
    }
}
