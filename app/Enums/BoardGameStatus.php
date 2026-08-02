<?php

declare(strict_types=1);

namespace App\Enums;

enum BoardGameStatus: string
{
    case Owned = 'owned';       // "was haben wir"
    case Wishlist = 'wishlist'; // "was wollen wir"
}
