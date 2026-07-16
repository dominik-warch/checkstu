<?php

declare(strict_types=1);

namespace App\Actions\Books;

use App\Models\BookEntry;

class UpdateBookEntryAction
{
    /**
     * @param  array{status: string, read_at?: string|null}  $data
     */
    public function handle(BookEntry $entry, array $data): BookEntry
    {
        $entry->update([
            'status' => $data['status'],
            'read_at' => $data['read_at'] ?? null,
        ]);

        return $entry;
    }
}
