<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\MediaEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateMediaEntryAction
{
    public function __construct(
        private readonly AddMediaItemAction $addItem,
    ) {}

    /**
     * @param  array{tmdb_id: int, type: string, status: string, watched_at?: string|null}  $data
     */
    public function handle(array $data, User $user): MediaEntry
    {
        return DB::transaction(function () use ($data, $user): MediaEntry {
            $item = $this->addItem->handle($data['tmdb_id'], MediaType::from($data['type']));

            // updateOrCreate so re-adding a previously-removed item (or
            // re-submitting from search) just refreshes the status, not a duplicate row.
            return MediaEntry::updateOrCreate(
                ['user_id' => $user->id, 'media_item_id' => $item->id],
                ['status' => $data['status'], 'watched_at' => $data['watched_at'] ?? null],
            );
        });
    }
}
