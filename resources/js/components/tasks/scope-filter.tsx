import { router } from '@inertiajs/react';

import { cn } from '@/lib/utils';

type Scope = 'all' | 'mine';

const options: { value: Scope; label: string }[] = [
    { value: 'mine', label: 'Meine' },
    { value: 'all', label: 'Alle' },
];

export default function ScopeFilter({ routeName, scope, className }: { routeName: string; scope: Scope; className?: string }) {
    const setScope = (value: Scope) => {
        router.get(route(routeName), { scope: value }, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <div role="radiogroup" aria-label="Aufgaben anzeigen" className={cn('bg-muted flex rounded-full p-1', className)}>
            {options.map((o) => (
                <button
                    key={o.value}
                    type="button"
                    role="radio"
                    aria-checked={scope === o.value}
                    onClick={() => setScope(o.value)}
                    className={cn(
                        'h-9 flex-1 rounded-full px-4 text-sm font-medium transition-colors duration-200',
                        scope === o.value ? 'bg-foreground text-background font-bold' : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {o.label}
                </button>
            ))}
        </div>
    );
}
