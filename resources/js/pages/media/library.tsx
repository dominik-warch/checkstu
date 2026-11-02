import { Head } from '@inertiajs/react';

import BookEntryCard from '@/components/media/book-entry-card';
import LibraryFilters from '@/components/media/library-filters';
import MediaEntryCard from '@/components/media/media-entry-card';
import MediaSearchDialog from '@/components/media/media-search-dialog';
import { cardGrid, EmptyState } from '@/components/page-header';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { LibraryFilters as Filters, LibraryEntry } from '@/types/media';

interface LibraryProps {
    entries: LibraryEntry[];
    filters: Filters;
}

export default function Library({ entries, filters }: LibraryProps) {
    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={t('media.library')} />

            <h1 className="mb-3 text-2xl font-bold tracking-tight">{t('media.library')}</h1>

            <LibraryFilters filters={filters} />

            {entries.length === 0 && (
                <EmptyState>
                    {filters.status === 'all' && filters.type === 'all' ? t('media.emptyLibrary') : 'Nichts passt zu diesen Filtern.'}
                </EmptyState>
            )}

            <div className={cardGrid}>
                {entries.map((entry) =>
                    entry.kind === 'book' ? (
                        <BookEntryCard key={`book-${entry.id}`} entry={entry} />
                    ) : (
                        <MediaEntryCard key={`media-${entry.id}`} entry={entry} />
                    ),
                )}
            </div>

            <MediaSearchDialog />
        </CheckstuLayout>
    );
}
