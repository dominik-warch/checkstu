import { Head } from '@inertiajs/react';

import PageHeader, { cardGrid, EmptyState } from '@/components/page-header';
import SectionHead from '@/components/section-head';
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

function dayLabel(due: string): string {
    const date = new Date(due + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = Math.round((date.getTime() - today.getTime()) / 86_400_000);
    if (diff === 0) return 'Heute';
    if (diff === 1) return 'Morgen';
    return new Intl.DateTimeFormat('de-DE', { weekday: 'long', day: 'numeric', month: 'long' }).format(date);
}

/** Everything still open, by day: overdue first, then each day ahead, then "Irgendwann" for tasks without a date. */
export default function TasksIndex({ occurrences, filters, members, templates, can }: TasksIndexProps) {
    // Already sorted by due date server-side (undated last), so insertion order is the display order.
    const today = new Date(new Date().setHours(0, 0, 0, 0));
    const groups = new Map<string, Occurrence[]>();
    for (const o of occurrences) {
        const key = !o.due_date ? 'someday' : new Date(o.due_date + 'T00:00:00') < today ? 'overdue' : o.due_date;
        groups.set(key, [...(groups.get(key) ?? []), o]);
    }

    return (
        <CheckstuLayout>
            <Head title={t('nav.tasks')} />

            <PageHeader title={t('nav.tasks')}>
                <ScopeFilter routeName="tasks.index" scope={filters.scope} className="w-44" />
            </PageHeader>

            {occurrences.length === 0 && <EmptyState>Keine offenen Aufgaben.</EmptyState>}

            {[...groups.entries()].map(([key, list]) => (
                <section key={key} className="mb-2">
                    <SectionHead count={list.length}>
                        {key === 'overdue' ? <span className="text-urgent">Überfällig</span> : key === 'someday' ? 'Irgendwann' : dayLabel(key)}
                    </SectionHead>
                    <div className={cardGrid}>
                        {list.map((o) => (
                            // Under a day's head the due label would only repeat it; overdue cards keep their date.
                            <TaskCard
                                key={o.id}
                                occurrence={o}
                                members={members}
                                canCompleteOnBehalf={can.completeOnBehalf}
                                showDue={key === 'overdue'}
                            />
                        ))}
                    </div>
                </section>
            ))}

            {can.createTask && <TaskFormDialog members={members} templates={templates} />}
        </CheckstuLayout>
    );
}
