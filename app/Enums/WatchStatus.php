<?php

declare(strict_types=1);

namespace App\Enums;

enum WatchStatus: string
{
    case Watchlist = 'watchlist';
    case Watching = 'watching';   // TV only — at least one episode watched, not all aired ones yet
    case Completed = 'completed'; // movie: watched; TV: every aired episode watched
}
