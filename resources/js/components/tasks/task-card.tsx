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
import { contrastTextColor } from '@/lib/color-contrast';
import { useSwipeToComplete } from '@/hooks/use-swipe-to-complete';
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

    const swipe = useSwipeToComplete(() => complete(), !occurrence.is_blocked && !processing);
    const reached = swipe.dx >= 96;
    const overdue = occurrence.status === 'overdue';

    const assigneeColor = occurrence.assignee?.color ?? null;
    const textColor = assigneeColor ? contrastTextColor(assigneeColor) : null;
    // Muted sub-text (due date/assignee/category line, private lock icon) can't use the
    // fixed text-muted-foreground gray on a custom background — it'd have arbitrary,
    // possibly-poor contrast. Dim the same contrast color instead so it always reads.
    const mutedStyle = textColor ? { color: textColor, opacity: 0.75 } : undefined;

    return (
        <div className="relative overflow-hidden rounded-xl">
            {/* Swipe-reveal background */}
            <div
                className={cn(
                    'absolute inset-0 flex items-center gap-2 pl-4 text-white transition-colors',
                    reached ? 'bg-emerald-600' : 'bg-emerald-500/70',
                )}
                aria-hidden
            >
                <Check className="size-5" />
                {reached && <span className="text-sm font-medium">{t('common.done')}</span>}
            </div>

            {/* Foreground card (draggable) */}
            <div
                className={cn(
                    'flex items-center gap-3 border p-3',
                    !assigneeColor && 'bg-background',
                    occurrence.is_blocked && 'opacity-60',
                )}
                style={{
                    transform: `translateX(${swipe.dx}px)`,
                    transition: swipe.animating ? 'transform 200ms ease-out' : undefined,
                    touchAction: 'pan-y',
                    ...(assigneeColor ? { backgroundColor: assigneeColor, color: textColor ?? undefined } : {}),
                }}
                {...swipe.handlers}
            >
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <Link
                            href={route('tasks.show', occurrence.task_id)}
                            className="truncate font-medium hover:underline"
                            onClick={(e) => {
                                if (swipe.swiped.current) e.preventDefault();
                            }}
                        >
                            {occurrence.title}
                        </Link>
                        <Badge variant="secondary" className={cn('shrink-0', priorityClass[occurrence.priority])}>
                            {t(priorityLabel[occurrence.priority])}
                        </Badge>
                        {occurrence.is_private && (
                            <Lock
                                className={cn('size-3.5 shrink-0', !assigneeColor && 'text-muted-foreground')}
                                style={mutedStyle}
                                aria-label={t('task.private')}
                            />
                        )}
                    </div>

                    <div
                        className={cn('mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm', !assigneeColor && 'text-muted-foreground')}
                        style={mutedStyle}
                    >
                        <span
                            className={cn(
                                'inline-flex items-center gap-1',
                                overdue && !assigneeColor && 'text-rose-600 dark:text-rose-400',
                            )}
                        >
                            <Clock className="size-3.5" />
                            {formatDue(occurrence.due_date)}
                        </span>
                        <span>· {occurrence.assignee ? occurrence.assignee.name : t('task.unassigned')}</span>
                        {occurrence.categories.map((c) => (
                            <span key={c.id} className="inline-flex items-center gap-1">
                                <span className="size-2 rounded-full" style={{ background: c.color ?? '#999' }} />
                                {c.name}
                            </span>
                        ))}
                    </div>

                    {occurrence.is_blocked && occurrence.blocking_titles.length > 0 && (
                        <div
                            className={cn('mt-1 inline-flex items-center gap-1 text-xs', !assigneeColor && 'text-muted-foreground')}
                            style={mutedStyle}
                        >
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
        </div>
    );
}
