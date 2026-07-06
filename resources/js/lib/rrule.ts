/**
 * Minimal client-side RFC5545 RRULE builder for the simplified recurrence
 * picker (daily/weekly/monthly + interval + weekdays). Kept intentionally
 * narrow — checkstu never exposes raw RRULE syntax to users.
 */

export type RruleFreq = 'DAILY' | 'WEEKLY' | 'MONTHLY';

export const WEEKDAYS: { value: string; label: string }[] = [
    { value: 'MO', label: 'Mo' },
    { value: 'TU', label: 'Di' },
    { value: 'WE', label: 'Mi' },
    { value: 'TH', label: 'Do' },
    { value: 'FR', label: 'Fr' },
    { value: 'SA', label: 'Sa' },
    { value: 'SU', label: 'So' },
];

export function buildRrule(freq: RruleFreq, interval: number, byday: string[]): string {
    const parts = [`FREQ=${freq}`];
    if (interval > 1) {
        parts.push(`INTERVAL=${interval}`);
    }
    if (freq === 'WEEKLY' && byday.length > 0) {
        parts.push(`BYDAY=${byday.join(',')}`);
    }
    return parts.join(';');
}
