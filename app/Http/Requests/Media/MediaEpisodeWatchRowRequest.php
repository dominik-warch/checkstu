<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

/** Shared by update/destroy on a single watch row, addressed directly by id. */
class MediaEpisodeWatchRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('watch')->user_id === $this->user()->id;
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
