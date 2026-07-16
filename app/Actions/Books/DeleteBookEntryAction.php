<?php

declare(strict_types=1);

namespace App\Actions\Books;

use App\Models\BookEntry;

class DeleteBookEntryAction
{
    public function handle(BookEntry $entry): void
    {
        $entry->delete();
    }
}
