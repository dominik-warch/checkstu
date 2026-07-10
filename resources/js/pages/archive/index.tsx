import { Head } from '@inertiajs/react';

import ArchiveCard from '@/components/archive/archive-card';
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

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('archive.title')}</h1>

            <ScopeFilter routeName="archive" scope={filters.scope} />

            <div className="flex flex-col gap-2">
                {occurrences.length === 0 && (
                    <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">{t('archive.empty')}</div>
                )}
                {occurrences.map((o) => (
                    <ArchiveCard key={o.id} occurrence={o} />
                ))}
            </div>
        </CheckstuLayout>
    );
}
