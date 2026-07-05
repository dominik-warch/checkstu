<?php

declare(strict_types=1);

namespace App\Enums;

enum CompletionAction: string
{
    case Completed = 'completed';
    case Skipped = 'skipped';
    case Reopened = 'reopened';
}
