import { Head } from '@inertiajs/react';

import PageHeader, { cardGrid, EmptyState } from '@/components/page-header';
import ScopeFilter from '@/components/tasks/scope-filter';
import TaskCard from '@/components/tasks/task-card';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { Member, Occurrence, TaskAbilities, TaskTemplateSummary } from '@/types/checkstu';

interface TasksIndexProps {
    occurrences: Occurrence[];
    filters: { scope: 'all' | 'mine' };
    members: Member[];
    templates: TaskTemplateSummary[];
    can: TaskAbilities;
}

export default function TasksIndex({ occurrences, filters, members, templates, can }: TasksIndexProps) {
    return (
        <CheckstuLayout>
            <Head title={t('nav.tasks')} />

            <PageHeader title={t('nav.tasks')} className="mb-4">
                <ScopeFilter routeName="tasks.index" scope={filters.scope} className="w-44" />
            </PageHeader>

            {occurrences.length === 0 && <EmptyState>Keine offenen Aufgaben.</EmptyState>}

            <div className={cardGrid}>
                {occurrences.map((o) => (
                    <TaskCard key={o.id} occurrence={o} members={members} canCompleteOnBehalf={can.completeOnBehalf} />
                ))}
            </div>

            {can.createTask && <TaskFormDialog members={members} templates={templates} />}
        </CheckstuLayout>
    );
}
