import { Link, router } from '@inertiajs/react';
import { Check, Lock, Repeat, Trash2 } from 'lucide-react';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { showUndo } from '@/components/undo-toast';
import { useCompleteOccurrence } from '@/hooks/use-complete-occurrence';
import { useSwipeToComplete } from '@/hooks/use-swipe-to-complete';
import { contrastTextColor } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Member, Occurrence } from '@/types/checkstu';

export const priorityLabel = ['priority.low', 'priority.normal', 'priority.high', 'priority.urgent'] as const;
// Low and normal carry no badge: only priorities that ask for attention get one.
export const priorityClass = ['', '', 'bg-warn-soft text-warn-ink', 'bg-urgent-soft text-urgent-ink'];

export function formatDue(due: string | null): string {
    if (!due) return 'Irgendwann';
    const date = new Date(due + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = Math.round((date.getTime() - today.getTime()) / 86_400_000);
    if (diff === 0) return 'Heute';
    if (diff === 1) return 'Morgen';
    if (diff === -1) return 'Gestern';
    return new Intl.DateTimeFormat('de-DE', { day: 'numeric', month: 'short' }).format(date);
}

/** Deletes at once (tasks are only soft-deleted) and offers the undo toast; "Gelöscht" in the archive is the way back after that. */
export function deleteTask(taskId: number, title: string) {
    router.delete(route('tasks.destroy', taskId), {
        preserveScroll: true,
        onSuccess: () => showUndo({ title, note: 'Gelöscht', undo: () => router.post(route('tasks.restore', taskId), {}, { preserveScroll: true }) }),
    });
}

interface TaskCardProps {
    occurrence: Occurrence;
    members: Member[];
    canCompleteOnBehalf: boolean;
    showDue?: boolean;
}

export default function TaskCard({ occurrence, members, canCompleteOnBehalf, showDue = true }: TaskCardProps) {
    const { processing, complete } = useCompleteOccurrence(occurrence, occurrence.assignee?.color);

    const swipe = useSwipeToComplete(
        () => complete(),
        !occurrence.is_blocked && !processing,
        occurrence.can_delete ? () => deleteTask(occurrence.task_id, occurrence.title) : undefined,
    );
    const reached = swipe.dx >= 96;
    const reachedDelete = swipe.dx <= -96;
    const overdue = occurrence.status === 'overdue';
    const priority = priorityClass[occurrence.priority];

    const assigneeColor = occurrence.assignee?.color ?? null;
    const ink = assigneeColor ? contrastTextColor(assigneeColor) : null;

    const completeButton = (
        <button
            type="button"
            disabled={processing}
            aria-label={`${occurrence.title}: ${t('task.markDone')}`}
            onClick={canCompleteOnBehalf ? undefined : () => complete()}
            className={cn(
                'group/done grid size-11 shrink-0 place-items-center rounded-full border-2 border-current transition-transform duration-150 active:scale-90 disabled:opacity-50',
                !ink && 'border-muted-foreground hover:border-foreground',
            )}
        >
            <Check className="size-5 opacity-0 transition-opacity group-hover/done:opacity-60 group-focus-visible/done:opacity-60" strokeWidth={3} />
        </button>
    );

    return (
        <div className="relative overflow-hidden rounded-2xl">
            {/* Swipe-reveal background */}
            <div
                className={cn(
                    'absolute inset-0 flex items-center text-white transition-colors',
                    swipe.dx < 0
                        ? cn('justify-end gap-2 pr-5', reachedDelete ? 'bg-[#be123c]' : 'bg-[#e11d48]')
                        : cn('gap-2 pl-5', reached ? 'bg-[#047857]' : 'bg-[#059669]'),
                )}
                aria-hidden
            >
                {swipe.dx < 0 ? (
                    <>
                        {reachedDelete && <span className="text-sm font-bold">{t('common.delete')}</span>}
                        <Trash2 className="size-5" />
                    </>
                ) : (
                    <>
                        <Check className="size-6" strokeWidth={3} />
                        {reached && <span className="text-sm font-bold">{t('common.done')}</span>}
                    </>
                )}
            </div>

            {/* Foreground card (draggable) */}
            <div
                className={cn(
                    'relative flex min-h-16 items-center gap-3 rounded-2xl py-2.5 pr-2.5 pl-4',
                    !assigneeColor && 'bg-card border-[1.5px]',
                    occurrence.is_blocked && 'opacity-55',
                )}
                style={{
                    transform: `translateX(${swipe.dx}px)`,
                    transition: swipe.animating ? 'transform 260ms var(--ease-pop)' : undefined,
                    touchAction: 'pan-y',
                    ...(assigneeColor ? { backgroundColor: assigneeColor, color: ink ?? undefined } : {}),
                }}
                {...swipe.handlers}
            >
                <div className="min-w-0 flex-1">
                    <Link
                        href={route('tasks.show', occurrence.task_id)}
                        className="line-clamp-2 text-base leading-snug font-medium [overflow-wrap:break-word] hyphens-auto underline-offset-4 hover:underline"
                        onClick={(e) => {
                            if (swipe.swiped.current) e.preventDefault();
                        }}
                    >
                        {occurrence.title}
                    </Link>

                    <div
                        className={cn(
                            'mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-0.5 text-sm',
                            ink ? 'text-current' : 'text-muted-foreground',
                        )}
                    >
                        {priority && (
                            <span className={cn('rounded-full px-2 text-xs leading-5 font-bold opacity-100', priority)}>
                                {t(priorityLabel[occurrence.priority])}
                            </span>
                        )}
                        <span>{occurrence.assignee ? occurrence.assignee.name : t('task.unassigned')}</span>
                        {occurrence.categories.map((c) => (
                            <span key={c.id} className="inline-flex items-center gap-1">
                                <span className="size-2 rounded-full ring-1 ring-current/30" style={{ background: c.color ?? '#999' }} />
                                {c.name}
                            </span>
                        ))}
                        {occurrence.is_private && <Lock className="size-3.5" aria-label={t('task.private')} />}
                        {occurrence.is_recurring && <Repeat className="size-3.5" aria-label={t('task.recurring')} />}
                    </div>

                    {occurrence.is_blocked && occurrence.blocking_titles.length > 0 && (
                        <div className={cn('mt-0.5 inline-flex items-center gap-1 text-sm', ink ? 'text-current' : 'text-muted-foreground')}>
                            <Lock className="size-3.5" />
                            {t('task.waitingOn', { task: occurrence.blocking_titles.join(', ') })}
                        </div>
                    )}
                </div>

                {/* Due dates share one right-aligned column, so the eye can run down them. */}
                {showDue && (
                    <time
                        dateTime={occurrence.due_date ?? undefined}
                        className={cn(
                            'w-16 shrink-0 text-right text-sm tabular-nums',
                            overdue ? 'font-bold' : ink ? 'text-current' : 'text-muted-foreground',
                            overdue && !ink && 'text-urgent',
                        )}
                    >
                        {formatDue(occurrence.due_date)}
                    </time>
                )}

                {!occurrence.is_blocked &&
                    (canCompleteOnBehalf ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>{completeButton}</DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuLabel>{t('task.whoDidIt')}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                {members.map((m) => (
                                    <DropdownMenuItem key={m.id} onClick={() => complete(m)}>
                                        <span className="size-3 rounded-full" style={{ background: m.color ?? 'var(--muted-foreground)' }} />
                                        {m.name}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        completeButton
                    ))}
                {occurrence.is_blocked && <span className="size-11 shrink-0" aria-hidden />}
            </div>
        </div>
    );
}
