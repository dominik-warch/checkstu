import { Head, router } from '@inertiajs/react';

import ArchiveCard from '@/components/archive/archive-card';
import PageHeader, { cardGrid, EmptyState } from '@/components/page-header';
import SectionHead from '@/components/section-head';
import ScopeFilter from '@/components/tasks/scope-filter';
import { Button } from '@/components/ui/button';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { Occurrence } from '@/types/checkstu';

interface ArchiveIndexProps {
    occurrences: Occurrence[];
    filters: { scope: 'all' | 'mine' };
    deleted: { id: number; title: string }[];
}

export default function ArchiveIndex({ occurrences, filters, deleted }: ArchiveIndexProps) {
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

            {deleted.length > 0 && (
                <section className="mt-6">
                    <SectionHead count={deleted.length}>Gelöscht</SectionHead>
                    <div className={cardGrid}>
                        {deleted.map((task) => (
                            <div
                                key={task.id}
                                className="bg-card flex min-h-16 items-center gap-3 rounded-2xl border-[1.5px] border-dashed py-2.5 pr-2.5 pl-4"
                            >
                                <span className="text-muted-foreground min-w-0 flex-1 truncate text-base font-medium">{task.title}</span>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => router.post(route('tasks.restore', task.id), {}, { preserveScroll: true })}
                                >
                                    {t('archive.restore')}
                                </Button>
                            </div>
                        ))}
                    </div>
                </section>
            )}
        </CheckstuLayout>
    );
}
