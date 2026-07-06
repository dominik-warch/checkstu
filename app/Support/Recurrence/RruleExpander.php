<?php

declare(strict_types=1);

namespace App\Support\Recurrence;

use Illuminate\Support\Carbon;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\Constraint\BetweenConstraint;

/**
 * Thin wrapper around simshaun/recurr for expanding an RFC5545 RRULE string.
 *
 * The rule's own start date (anchor) determines phase/interval counting (e.g.
 * which week an INTERVAL=2 lands on) — the window we pass in only filters
 * which of those computed dates we actually want back, so narrowing the
 * window to "from today" never shifts a biweekly/monthly cadence.
 */
class RruleExpander
{
    /** Whether $rrule is a syntactically valid RFC5545 recurrence rule. */
    public function isValid(string $rrule): bool
    {
        try {
            new Rule($rrule, Carbon::today(), null, config('app.timezone', 'UTC'));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<Carbon> distinct calendar dates, ascending, within [$windowStart, $windowEnd]
     */
    public function datesBetween(string $rrule, Carbon $anchor, Carbon $windowStart, Carbon $windowEnd): array
    {
        $timezone = config('app.timezone', 'UTC');

        $rule = new Rule($rrule, $anchor->copy()->startOfDay()->setTimezone($timezone), null, $timezone);

        $constraint = new BetweenConstraint(
            $windowStart->copy()->startOfDay(),
            $windowEnd->copy()->endOfDay(),
            true,
        );

        $transformer = new ArrayTransformer();
        $recurrences = $transformer->transform($rule, $constraint);

        $dates = [];
        foreach ($recurrences as $recurrence) {
            $dates[] = Carbon::instance($recurrence->getStart())->startOfDay();
        }

        return $dates;
    }
}
