<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurrenceType: string
{
    case OneOff = 'one_off';               // ad-hoc single task
    case Rrule = 'rrule';                  // calendar-fixed, regular (via simshaun/recurr)
    case ExplicitDates = 'explicit_dates'; // calendar-fixed, irregular (e.g. garbage pickup)
    case Relative = 'relative';            // completion-anchored: N days after last done

    /** Whether completing an occurrence should spawn the next one at complete-time. */
    public function isCompletionAnchored(): bool
    {
        return $this === self::Relative;
    }
}
