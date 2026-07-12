import { Head } from '@inertiajs/react';
import { ReactNode } from 'react';

import ScopeFilter from '@/components/tasks/scope-filter';
import TaskCard from '@/components/tasks/task-card';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { Member, Occurrence, TaskAbilities, TaskTemplateSummary } from '@/types/checkstu';

interface TodayProps {
    occurrences: Occurrence[];
    filters: { scope: 'all' | 'mine' };
    members: Member[];
    templates: TaskTemplateSummary[];
    can: TaskAbilities;
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="mb-6">
            <h2 className="text-muted-foreground mb-2 text-sm font-semibold tracking-wide uppercase">{title}</h2>
            <div className="flex flex-col gap-2">{children}</div>
        </section>
    );
}

export default function Today({ occurrences, filters, members, templates, can }: TodayProps) {
    const blocked = occurrences.filter((o) => o.is_blocked);
    const actionable = occurrences.filter((o) => !o.is_blocked);
    const overdue = actionable.filter((o) => o.status === 'overdue');
    const rest = actionable.filter((o) => o.status !== 'overdue');

    const card = (o: Occurrence) => <TaskCard key={o.id} occurrence={o} members={members} canCompleteOnBehalf={can.completeOnBehalf} />;

    return (
        <CheckstuLayout>
            <Head title={t('nav.today')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('nav.today')}</h1>

            <ScopeFilter routeName="home" scope={filters.scope} />

            {occurrences.length === 0 && (
                <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">Alles erledigt 🎉</div>
            )}

            {overdue.length > 0 && <Section title="Überfällig">{overdue.map(card)}</Section>}
            {rest.length > 0 && <Section title="Zu erledigen">{rest.map(card)}</Section>}
            {blocked.length > 0 && <Section title="Blockiert">{blocked.map(card)}</Section>}

            {can.createTask && <TaskFormDialog members={members} templates={templates} />}
        </CheckstuLayout>
    );
}
