import { ChevronDown } from 'lucide-react';
import type { ReactNode } from 'react';

import type { Occurrence } from '@/types/checkstu';

const LEFT_BEHIND_AFTER_DAYS = 3;

/** Overdue for more than three days: not urgent anymore, just left behind. */
export function isLeftBehind(o: Occurrence): boolean {
    if (o.status !== 'overdue' || !o.due_date) return false;
    const cutoff = new Date();
    cutoff.setHours(0, 0, 0, 0);
    cutoff.setDate(cutoff.getDate() - LEFT_BEHIND_AFTER_DAYS);
    return new Date(o.due_date + 'T00:00:00') < cutoff;
}

/**
 * One quiet folded row instead of a red wall of old overdue cards, so today's work stays on top.
 * Open it to finish them, or open one to skip it ("Überspringen") or delete it.
 */
export default function LeftBehind({ count, children }: { count: number; children: ReactNode }) {
    if (count === 0) return null;

    return (
        <details className="group/left mt-4">
            <summary className="text-muted-foreground hover:border-foreground/40 flex min-h-12 cursor-pointer list-none items-center gap-2 rounded-2xl border-[1.5px] border-dashed px-4 text-sm font-bold [&::-webkit-details-marker]:hidden">
                Liegen geblieben
                <span className="font-medium tabular-nums">{count}</span>
                <span className="ml-auto font-medium">älter als {LEFT_BEHIND_AFTER_DAYS} Tage</span>
                <ChevronDown className="size-4 transition-transform duration-200 group-open/left:rotate-180" />
            </summary>
            <div className="mt-2">{children}</div>
        </details>
    );
}
