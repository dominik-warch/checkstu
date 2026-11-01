import { Head } from '@inertiajs/react';

import ArchiveCard from '@/components/archive/archive-card';
import PageHeader, { cardGrid, EmptyState } from '@/components/page-header';
import ScopeFilter from '@/components/tasks/scope-filter';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { Occurrence } from '@/types/checkstu';

interface ArchiveIndexProps {
    occurrences: Occurrence[];
    filters: { scope: 'all' | 'mine' };
}

export default function ArchiveIndex({ occurrences, filters }: ArchiveIndexProps) {
    return (
        <CheckstuLayout>
            <Head title={t('archive.title')} />

            <PageHeader title={t('archive.title')} className="mb-4">
                <ScopeFilter routeName="archive" scope={filters.scope} className="w-44" />
            </PageHeader>

            {occurrences.length === 0 && <EmptyState>{t('archive.empty')}</EmptyState>}

            <div className={cardGrid}>
                {occurrences.map((o) => (
                    <ArchiveCard key={o.id} occurrence={o} />
                ))}
            </div>
        </CheckstuLayout>
    );
}
