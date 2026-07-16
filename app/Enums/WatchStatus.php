<?php

declare(strict_types=1);

namespace App\Enums;

/** Shared by media_entries (movies/TV) and book_entries. */
enum WatchStatus: string
{
    case Watchlist = 'watchlist';
    case Watching = 'watching';   // TV only — at least one episode watched, not all aired ones yet
    case Completed = 'completed'; // movie/book: watched/read; TV: every aired episode watched
}
