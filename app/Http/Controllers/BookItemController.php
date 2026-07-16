<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BookEntry;
use App\Models\BookItem;
use App\Support\BookEntryPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookItemController extends Controller
{
    public function show(Request $request, BookItem $bookItem): Response
    {
        $entry = BookEntry::where('user_id', $request->user()->id)
            ->where('book_item_id', $bookItem->id)
            ->first();

        return Inertia::render('media/book-show', [
            'item' => BookEntryPresenter::detail($bookItem, $entry),
        ]);
    }
}
