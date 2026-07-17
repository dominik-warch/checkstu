<?php

declare(strict_types=1);

namespace App\Actions\Books;

use App\Models\BookEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBookEntryAction
{
    public function __construct(
        private readonly AddBookItemAction $addItem,
    ) {}

    /**
     * @param  array{open_library_id: string, status: string, read_at?: string|null}  $data
     */
    public function handle(array $data, User $user): BookEntry
    {
        return DB::transaction(function () use ($data, $user): BookEntry {
            $item = $this->addItem->handle($data['open_library_id']);

            return BookEntry::updateOrCreate(
                ['user_id' => $user->id, 'book_item_id' => $item->id],
                ['status' => $data['status'], 'read_at' => $data['read_at'] ?? null],
            );
        });
    }
}
