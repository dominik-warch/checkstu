import { Head, router } from '@inertiajs/react';

import TaskFormDialog from '@/components/tasks/task-form-dialog';
import TaskCard from '@/components/tasks/task-card';
import { Button } from '@/components/ui/button';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Member, Occurrence, TaskAbilities } from '@/types/checkstu';

interface TasksIndexProps {
    occurrences: Occurrence[];
    filters: { scope: 'all' | 'mine' };
    members: Member[];
    can: TaskAbilities;
}

export default function TasksIndex({ occurrences, filters, members, can }: TasksIndexProps) {
    const setScope = (scope: 'all' | 'mine') => {
        router.get(route('tasks.index'), { scope }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const ScopeButton = ({ value, label }: { value: 'all' | 'mine'; label: string }) => (
        <Button
            type="button"
            variant={filters.scope === value ? 'default' : 'outline'}
            size="sm"
            className={cn('flex-1')}
            onClick={() => setScope(value)}
        >
            {label}
        </Button>
    );

    return (
        <CheckstuLayout>
            <Head title={t('nav.tasks')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('nav.tasks')}</h1>

            <div className="mb-4 flex gap-2">
                <ScopeButton value="all" label="Alle" />
                <ScopeButton value="mine" label="Meine" />
            </div>

            <div className="flex flex-col gap-2">
                {occurrences.length === 0 && (
                    <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">
                        Keine offenen Aufgaben.
                    </div>
                )}
                {occurrences.map((o) => (
                    <TaskCard key={o.id} occurrence={o} members={members} canCompleteOnBehalf={can.completeOnBehalf} />
                ))}
            </div>

            {can.createTask && <TaskFormDialog members={members} />}
        </CheckstuLayout>
    );
}
