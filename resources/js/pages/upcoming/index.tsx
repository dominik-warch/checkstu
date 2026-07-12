import { Head } from '@inertiajs/react';

import ScopeFilter from '@/components/tasks/scope-filter';
import TaskCard from '@/components/tasks/task-card';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { Member, Occurrence, TaskAbilities, TaskTemplateSummary } from '@/types/checkstu';

interface UpcomingProps {
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
    if (diff < 0) return 'Überfällig';
    if (diff === 0) return 'Heute';
    if (diff === 1) return 'Morgen';
    return new Intl.DateTimeFormat('de-DE', { weekday: 'long', day: '2-digit', month: 'long' }).format(date);
}

export default function Upcoming({ occurrences, filters, members, templates, can }: UpcomingProps) {
    // Group by due date (already sorted ascending server-side); overdue collapses under one label.
    const groups = new Map<string, Occurrence[]>();
    for (const o of occurrences) {
        if (!o.due_date) continue;
        const key = new Date(o.due_date + 'T00:00:00') < new Date(new Date().setHours(0, 0, 0, 0)) ? 'overdue' : o.due_date;
        const list = groups.get(key) ?? [];
        list.push(o);
        groups.set(key, list);
    }

    return (
        <CheckstuLayout>
            <Head title={t('upcoming.title')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('upcoming.title')}</h1>

            <ScopeFilter routeName="upcoming" scope={filters.scope} />

            {occurrences.length === 0 && (
                <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">{t('upcoming.empty')}</div>
            )}

            {[...groups.entries()].map(([key, list]) => (
                <section key={key} className="mb-6">
                    <h2 className="text-muted-foreground mb-2 text-sm font-semibold tracking-wide uppercase">
                        {key === 'overdue' ? 'Überfällig' : dayLabel(list[0].due_date as string)}
                    </h2>
                    <div className="flex flex-col gap-2">
                        {list.map((o) => (
                            <TaskCard key={o.id} occurrence={o} members={members} canCompleteOnBehalf={can.completeOnBehalf} />
                        ))}
                    </div>
                </section>
            ))}

            {can.createTask && <TaskFormDialog members={members} templates={templates} />}
        </CheckstuLayout>
    );
}
