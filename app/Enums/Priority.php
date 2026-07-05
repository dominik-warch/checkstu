<?php

declare(strict_types=1);

namespace App\Enums;

enum Priority: int
{
    case Low = 0;
    case Normal = 1;
    case High = 2;
    case Urgent = 3;
}
