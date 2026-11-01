import { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/** Sticky group heading: always names where you are in the list (Überfällig, Heute, …). */
export default function SectionHead({ children, count, className }: { children: ReactNode; count?: number; className?: string }) {
    return (
        <h2
            className={cn(
                'bg-background sticky top-[calc(env(safe-area-inset-top)+3.5rem)] z-10 -mx-1 flex items-baseline gap-2 px-1 pt-4 pb-2 text-sm font-bold lg:top-0',
                className,
            )}
        >
            {children}
            {count !== undefined && <span className="text-muted-foreground font-medium tabular-nums">{count}</span>}
        </h2>
    );
}
