import { router, usePage } from '@inertiajs/react';
import { Lock, Repeat, RotateCcw } from 'lucide-react';
import { useState } from 'react';

import { priorityClass, priorityLabel } from '@/components/tasks/task-card';
import { showUndo } from '@/components/undo-toast';
import { useSwipeToComplete } from '@/hooks/use-swipe-to-complete';
import { contrastTextColor, memberFill } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { Occurrence } from '@/types/checkstu';

function formatCompletedAt(iso: string | null): string {
    if (!iso) return '';
    const date = new Date(iso);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dateOnly = new Date(date);
    dateOnly.setHours(0, 0, 0, 0);
    const diff = Math.round((dateOnly.getTime() - today.getTime()) / 86_400_000);
    const time = new Intl.DateTimeFormat('de-DE', { hour: '2-digit', minute: '2-digit' }).format(date);
    if (diff === 0) return `Heute, ${time}`;
    if (diff === -1) return `Gestern, ${time}`;
    return new Intl.DateTimeFormat('de-DE', { day: 'numeric', month: 'short' }).format(date);
}

interface ArchiveCardProps {
    occurrence: Occurrence;
}

export default function ArchiveCard({ occurrence }: ArchiveCardProps) {
    const [processing, setProcessing] = useState(false);

    const meId = usePage<SharedData>().props.auth.user.id;
    const restore = () => {
        setProcessing(true);
        router.delete(route('occurrences.restore', occurrence.id), {
            preserveScroll: true,
            // Undo marks it done again. Credit goes back to whoever had it, when that's you; otherwise the
            // server's default (the assignee) applies, since only parents may credit someone else.
            onSuccess: () =>
                showUndo({
                    title: occurrence.title,
                    note: 'Wieder offen',
                    undo: () =>
                        router.post(
                            route('occurrences.complete', occurrence.id),
                            occurrence.completed_by?.id === meId ? { completed_by_user_id: meId } : {},
                            { preserveScroll: true },
                        ),
                }),
            onFinish: () => setProcessing(false),
        });
    };

    const swipe = useSwipeToComplete(restore, !processing);
    const reached = swipe.dx >= 96;

    const color = occurrence.completed_by ? (occurrence.assignee?.color ?? null) : null;
    const ink = color ? contrastTextColor(color) : null;
    const priority = priorityClass[occurrence.priority];

    return (
        <div className="relative overflow-hidden rounded-2xl">
            <div
                className={cn(
                    'absolute inset-0 flex items-center gap-2 pl-5 text-white transition-colors',
                    reached ? 'bg-swipe-restore-deep' : 'bg-swipe-restore',
                )}
                aria-hidden
            >
                <RotateCcw className="size-5" />
                {reached && <span className="text-sm font-bold">{t('archive.restore')}</span>}
            </div>

            <div
                className={cn('relative flex min-h-16 items-center gap-3 rounded-2xl py-2.5 pr-2.5 pl-4', !color && 'bg-card border-[1.5px]')}
                style={{
                    transform: `translateX(${swipe.dx}px)`,
                    transition: swipe.animating ? 'transform 260ms var(--ease-pop)' : undefined,
                    touchAction: 'pan-y',
                    ...(color ? memberFill(color) : {}),
                }}
                {...swipe.handlers}
            >
                <div className="min-w-0 flex-1">
                    <span className="line-clamp-2 text-base leading-snug font-medium [overflow-wrap:break-word] hyphens-auto line-through decoration-2">
                        {occurrence.title}
                    </span>
                    <div className={cn('mt-0.5 flex flex-wrap items-center gap-x-2.5 text-sm', !ink && 'text-muted-foreground')}>
                        {priority && (
                            <span className={cn('rounded-full px-2 text-xs leading-5 font-bold', priority)}>
                                {t(priorityLabel[occurrence.priority])}
                            </span>
                        )}
                        {occurrence.completed_by && <span>{t('archive.completedBy', { name: occurrence.completed_by.name })}</span>}
                        <time className="tabular-nums">{formatCompletedAt(occurrence.completed_at)}</time>
                        {occurrence.is_private && <Lock className="size-3.5" aria-label={t('task.private')} />}
                        {occurrence.is_recurring && <Repeat className="size-3.5" aria-label={t('task.recurring')} />}
                    </div>
                </div>

                {/* Tap equivalent of the swipe. */}
                <button
                    type="button"
                    onClick={restore}
                    disabled={processing}
                    aria-label={`${occurrence.title}: ${t('archive.restore')}`}
                    className={cn(
                        'grid size-11 shrink-0 place-items-center rounded-full transition-transform active:scale-90 disabled:opacity-50',
                        ink ? 'hover:ring-[1.5px] hover:ring-current hover:ring-inset' : 'hover:bg-muted text-muted-foreground',
                    )}
                >
                    <RotateCcw className="size-5" />
                </button>
            </div>
        </div>
    );
}
