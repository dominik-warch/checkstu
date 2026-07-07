import { router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Scope = 'all' | 'mine';

export default function ScopeFilter({ routeName, scope }: { routeName: string; scope: Scope }) {
    const setScope = (value: Scope) => {
        router.get(route(routeName), { scope: value }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const ScopeButton = ({ value, label }: { value: Scope; label: string }) => (
        <Button
            type="button"
            variant={scope === value ? 'default' : 'outline'}
            size="sm"
            className={cn('flex-1')}
            onClick={() => setScope(value)}
        >
            {label}
        </Button>
    );

    return (
        <div className="mb-4 flex gap-2">
            <ScopeButton value="all" label="Alle" />
            <ScopeButton value="mine" label="Meine" />
        </div>
    );
}
