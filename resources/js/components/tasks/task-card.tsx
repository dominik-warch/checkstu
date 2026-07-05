import { Link, router } from '@inertiajs/react';
import { Check, Clock, Lock } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Member, Occurrence } from '@/types/checkstu';

const priorityLabel = ['priority.low', 'priority.normal', 'priority.high', 'priority.urgent'] as const;
const priorityClass = [
    'bg-muted text-muted-foreground',
    'bg-muted text-muted-foreground',
    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
];

function formatDue(due: string | null): string {
    if (!due) return 'Irgendwann';
    const date = new Date(due + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = Math.round((date.getTime() - today.getTime()) / 86_400_000);
    if (diff === 0) return 'Heute';
    if (diff === 1) return 'Morgen';
    if (diff === -1) return 'Gestern';
    return new Intl.DateTimeFormat('de-DE', { day: '2-digit', month: 'short' }).format(date);
}

interface TaskCardProps {
    occurrence: Occurrence;
    members: Member[];
    canCompleteOnBehalf: boolean;
}

export default function TaskCard({ occurrence, members, canCompleteOnBehalf }: TaskCardProps) {
    const [processing, setProcessing] = useState(false);

    const complete = (completedByUserId?: number) => {
        setProcessing(true);
        router.post(
            route('occurrences.complete', occurrence.id),
            completedByUserId ? { completed_by_user_id: completedByUserId } : {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    const overdue = occurrence.status === 'overdue';

    return (
        <div
            className={cn(
                'flex items-center gap-3 rounded-xl border p-3',
                occurrence.is_blocked && 'opacity-60',
            )}
        >
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <Link
                        href={route('tasks.show', occurrence.task_id)}
                        className="truncate font-medium hover:underline"
                    >
                        {occurrence.title}
                    </Link>
                    <Badge variant="secondary" className={cn('shrink-0', priorityClass[occurrence.priority])}>
                        {t(priorityLabel[occurrence.priority])}
                    </Badge>
                </div>

                <div className="text-muted-foreground mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <span className={cn('inline-flex items-center gap-1', overdue && 'text-rose-600 dark:text-rose-400')}>
                        <Clock className="size-3.5" />
                        {formatDue(occurrence.due_date)}
                    </span>
                    {occurrence.assignee && <span>· {occurrence.assignee.name}</span>}
                    {occurrence.categories.map((c) => (
                        <span key={c.id} className="inline-flex items-center gap-1">
                            <span className="size-2 rounded-full" style={{ background: c.color ?? '#999' }} />
                            {c.name}
                        </span>
                    ))}
                </div>

                {occurrence.is_blocked && occurrence.blocking_titles.length > 0 && (
                    <div className="text-muted-foreground mt-1 inline-flex items-center gap-1 text-xs">
                        <Lock className="size-3" />
                        {t('task.waitingOn', { task: occurrence.blocking_titles.join(', ') })}
                    </div>
                )}
            </div>

            {!occurrence.is_blocked &&
                (canCompleteOnBehalf ? (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button size="icon" variant="outline" disabled={processing} aria-label={t('task.markDone')}>
                                <Check className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuLabel>{t('task.whoDidIt')}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {members.map((m) => (
                                <DropdownMenuItem key={m.id} onClick={() => complete(m.id)}>
                                    {m.name}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                ) : (
                    <Button
                        size="icon"
                        variant="outline"
                        disabled={processing}
                        onClick={() => complete()}
                        aria-label={t('task.markDone')}
                    >
                        <Check className="size-4" />
                    </Button>
                ))}
        </div>
    );
}
