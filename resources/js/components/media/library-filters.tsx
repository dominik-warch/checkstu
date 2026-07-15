import { router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { LibraryFilters as Filters } from '@/types/media';

interface LibraryFiltersProps {
    filters: Filters;
}

export default function LibraryFilters({ filters }: LibraryFiltersProps) {
    function update(next: Partial<Filters>) {
        router.get(route('media.library'), { ...filters, ...next }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const statusOptions: { value: Filters['status']; label: string }[] = [
        { value: 'all', label: t('common.all') },
        { value: 'watchlist', label: t('media.watchlist') },
        { value: 'watching', label: t('media.watching') },
        { value: 'completed', label: t('media.completed') },
    ];

    const typeOptions: { value: Filters['type']; label: string }[] = [
        { value: 'all', label: t('common.all') },
        { value: 'movie', label: t('media.typeMovie') },
        { value: 'tv', label: t('media.typeTv') },
    ];

    return (
        <div className="mb-4 flex flex-col gap-2">
            <div className="flex flex-wrap gap-2">
                {statusOptions.map((opt) => (
                    <Button
                        key={opt.value}
                        type="button"
                        size="sm"
                        variant={filters.status === opt.value ? 'default' : 'outline'}
                        className={cn('flex-1')}
                        onClick={() => update({ status: opt.value })}
                    >
                        {opt.label}
                    </Button>
                ))}
            </div>
            <div className="flex flex-wrap gap-2">
                {typeOptions.map((opt) => (
                    <Button
                        key={opt.value}
                        type="button"
                        size="sm"
                        variant={filters.type === opt.value ? 'default' : 'outline'}
                        className={cn('flex-1')}
                        onClick={() => update({ type: opt.value })}
                    >
                        {opt.label}
                    </Button>
                ))}
            </div>
        </div>
    );
}
