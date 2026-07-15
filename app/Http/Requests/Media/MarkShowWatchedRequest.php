<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\MediaEntry;
use Illuminate\Foundation\Http\FormRequest;

class MarkShowWatchedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('mediaItem');

        return MediaEntry::where('user_id', $this->user()->id)
            ->where('media_item_id', $item->id)
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
