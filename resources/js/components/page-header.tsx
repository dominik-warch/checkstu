import { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/** Page title with an optional control on the right (scope filter, action). */
export default function PageHeader({ title, children, className }: { title: ReactNode; children?: ReactNode; className?: string }) {
    return (
        <div className={cn('mb-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-3', className)}>
            <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
            {children}
        </div>
    );
}

export function EmptyState({ children, className }: { children: ReactNode; className?: string }) {
    return (
        <p className={cn('text-muted-foreground mt-4 rounded-2xl border-[1.5px] border-dashed p-6 text-center text-base', className)}>{children}</p>
    );
}

/** Card list: one column on phones, two from tablet up. */
export const cardGrid = 'grid grid-cols-1 gap-2 md:grid-cols-2 lg:gap-3';
