<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\MediaEntry;
use Illuminate\Foundation\Http\FormRequest;

class MarkSeasonWatchedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $season = $this->route('season');

        return MediaEntry::where('user_id', $this->user()->id)
            ->where('media_item_id', $season->media_item_id)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
