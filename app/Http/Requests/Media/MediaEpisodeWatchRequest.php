<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\MediaEntry;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by both mark and unmark (store/destroy) — nothing stops a user
 * POSTing an arbitrary episode id otherwise, since the mutation itself acts
 * implicitly on the authenticated user with no user-owned resource in the URL.
 */
class MediaEpisodeWatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $episode = $this->route('episode');

        return MediaEntry::where('user_id', $this->user()->id)
            ->where('media_item_id', $episode->season->media_item_id)
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
