import { router } from '@inertiajs/react';

import PillSelect from '@/components/pill-select';
import { t } from '@/lib/i18n';
import type { LibraryFilters as Filters } from '@/types/media';

interface LibraryFiltersProps {
    filters: Filters;
}

export default function LibraryFilters({ filters }: LibraryFiltersProps) {
    function update(next: Partial<Filters>) {
        router.get(route('media.library'), { ...filters, ...next }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const statusOptions: { value: Filters['status']; label: string }[] = [
        { value: 'all', label: 'Jeder Status' },
        { value: 'watchlist', label: t('media.watchlist') },
        { value: 'watching', label: t('media.watching') },
        { value: 'completed', label: t('media.completed') },
        { value: 'dropped', label: t('media.dropped') },
    ];

    const typeOptions: { value: Filters['type']; label: string }[] = [
        { value: 'all', label: 'Alle Typen' },
        { value: 'movie', label: t('media.typeMovie') },
        { value: 'tv', label: t('media.typeTv') },
        { value: 'book', label: t('media.typeBook') },
    ];

    const sortOptions: { value: Filters['sort']; label: string }[] = [
        { value: 'alphabetical', label: t('media.sortAlphabetical') },
        { value: 'recent', label: t('media.sortRecent') },
        { value: 'release', label: t('media.sortRelease') },
    ];

    return (
        <div className="mb-3 flex gap-1.5">
            <PillSelect label="Status" options={statusOptions} value={filters.status} neutral="all" onChange={(status) => update({ status })} />
            <PillSelect label="Typ" options={typeOptions} value={filters.type} neutral="all" onChange={(type) => update({ type })} />
            <PillSelect label="Sortierung" options={sortOptions} value={filters.sort} onChange={(sort) => update({ sort })} />
        </div>
    );
}
