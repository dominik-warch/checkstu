import { Head } from '@inertiajs/react';

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

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('nav.tasks')}</h1>

            <ScopeFilter routeName="tasks.index" scope={filters.scope} />

            <div className="flex flex-col gap-2">
                {occurrences.length === 0 && (
                    <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">Keine offenen Aufgaben.</div>
                )}
                {occurrences.map((o) => (
                    <TaskCard key={o.id} occurrence={o} members={members} canCompleteOnBehalf={can.completeOnBehalf} />
                ))}
            </div>

            {can.createTask && <TaskFormDialog members={members} templates={templates} />}
        </CheckstuLayout>
    );
}
