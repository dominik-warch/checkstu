import { Link, router, usePage } from '@inertiajs/react';
import { Check, ChevronDown, Lock, Repeat, SkipForward, Trash2 } from 'lucide-react';
import { useState, type ReactNode } from 'react';

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
import { contrastTextColor, memberFill } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
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
export function deleteTask(taskId: number, title: string, onFailed?: () => void, returnTo?: string) {
    let succeeded = false;
    router.delete(route('tasks.destroy', taskId), {
        // From the task's own page, the server sends you back to returnTo (the list you came from).
        data: returnTo ? { return_to: returnTo } : {},
        preserveScroll: true,
        onSuccess: () => {
            succeeded = true;
            showUndo({ title, note: 'Gelöscht', undo: () => router.post(route('tasks.restore', taskId), {}, { preserveScroll: true }) });
        },
        onFinish: () => {
            if (!succeeded) onFailed?.();
        },
    });
}

/** Only a parent finishing someone else's task is asked who did it; your own and "Für alle" tasks complete in one tap. */
export function asksWhoDidIt(canCompleteOnBehalf: boolean, occurrence: Occurrence, meId: number): boolean {
    return canCompleteOnBehalf && occurrence.assignee !== null && occurrence.assignee.id !== meId;
}

/** "Wer hat es erledigt?" with the assignee first and marked, so the likely answer is the first tap. */
export function WhoDidItMenu({
    trigger,
    members,
    assigneeId,
    onPick,
    align = 'end',
    className,
    open,
    onOpenChange,
}: {
    trigger: ReactNode;
    members: Member[];
    assigneeId: number | null;
    onPick: (member: Member) => void;
    align?: 'start' | 'end';
    className?: string;
    /** Controlled open state, so a swipe can open the menu too. */
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}) {
    const sorted = [...members].sort((a, b) => Number(b.id === assigneeId) - Number(a.id === assigneeId));
    return (
        <DropdownMenu open={open} onOpenChange={onOpenChange}>
            <DropdownMenuTrigger asChild>{trigger}</DropdownMenuTrigger>
            <DropdownMenuContent align={align} className={className}>
                <DropdownMenuLabel>{t('task.whoDidIt')}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {sorted.map((m) => (
                    <DropdownMenuItem key={m.id} onClick={() => onPick(m)}>
                        <span className="size-3 rounded-full" style={{ background: m.color ?? 'var(--muted-foreground)' }} />
                        {m.name}
                        {m.id === assigneeId && <span className="text-muted-foreground ml-auto pl-3 text-sm">zuständig</span>}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/** Lets one occurrence go (the series carries on), with the undo toast. Parents only, like deleting. */
export function skipOccurrence(occurrenceId: number, title: string, onFailed?: () => void, returnTo?: string) {
    let succeeded = false;
    router.post(route('occurrences.skip', occurrenceId), returnTo ? { return_to: returnTo } : {}, {
        preserveScroll: true,
        onSuccess: () => {
            succeeded = true;
            showUndo({
                title,
                note: 'Übersprungen',
                undo: () => router.delete(route('occurrences.restore', occurrenceId), { preserveScroll: true }),
            });
        },
        onFinish: () => {
            if (!succeeded) onFailed?.();
        },
    });
}

/** The round "done" target used on cards and for media episodes: one idiom wherever something gets ticked. */
export const completeRingClass = (onColour: boolean) =>
    cn(
        'group/done grid size-11 shrink-0 place-items-center rounded-full border-2 border-current transition-transform duration-150 active:scale-90 disabled:opacity-50',
        !onColour && 'border-muted-foreground hover:border-foreground',
    );

/** A faint check at rest says what the ring does on touch (no hover there); it firms up on hover, focus and press. */
export function RingCheck({ onColour = false }: { onColour?: boolean }) {
    return (
        <Check
            className={cn(
                'size-5 transition-opacity group-hover/done:opacity-60 group-focus-visible/done:opacity-60 group-active/done:opacity-100',
                // On a member colour a quarter-strength check reads as decoration; it needs more to say "tap to finish".
                onColour ? 'opacity-45' : 'opacity-25',
            )}
            strokeWidth={3}
            aria-hidden
        />
    );
}

/**
 * Parents hand a task out right from the card: the name in the meta line opens "Wer ist dran?"
 * ("Für alle" or a member, the current one ticked), with the undo toast. Not for private tasks.
 */
function AssigneeMenu({ occurrence, members }: { occurrence: Occurrence; members: Member[] }) {
    const current = occurrence.assignee?.id ?? null;
    const label = occurrence.assignee ? occurrence.assignee.name : t('task.unassigned');

    const assign = (id: number | null, name: string) => {
        if (id === current) return;
        router.patch(
            route('tasks.assign', occurrence.task_id),
            { default_assignee_id: id },
            {
                preserveScroll: true,
                onSuccess: () =>
                    showUndo({
                        title: occurrence.title,
                        note: id === null ? 'Jetzt für alle' : `Jetzt bei ${name}`,
                        undo: () =>
                            router.patch(route('tasks.assign', occurrence.task_id), { default_assignee_id: current }, { preserveScroll: true }),
                    }),
            },
        );
    };

    return (
        <DropdownMenu>
            {/* The ::after stretches the tap area to 44px without moving the meta line. */}
            <DropdownMenuTrigger
                aria-label={`${t('task.assignee')}: ${label}, ändern`}
                className="relative inline-flex items-center gap-0.5 rounded-sm font-medium underline-offset-4 after:absolute after:-inset-x-2 after:-inset-y-3 hover:underline"
            >
                {label}
                <ChevronDown className="size-3.5" aria-hidden />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                <DropdownMenuLabel>Wer ist dran?</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => assign(null, '')}>
                    <span className="border-muted-foreground size-3 rounded-full border border-dashed" />
                    {t('task.unassigned')}
                    {current === null && <Check className="ml-auto size-4" />}
                </DropdownMenuItem>
                {members.map((m) => (
                    <DropdownMenuItem key={m.id} onClick={() => assign(m.id, m.name)}>
                        <span className="size-3 rounded-full" style={{ background: m.color ?? 'var(--muted-foreground)' }} />
                        {m.name}
                        {current === m.id && <Check className="ml-auto size-4" />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

interface TaskCardProps {
    occurrence: Occurrence;
    members: Member[];
    canCompleteOnBehalf: boolean;
    showDue?: boolean;
}

export default function TaskCard({ occurrence, members, canCompleteOnBehalf, showDue = true }: TaskCardProps) {
    const { processing, complete } = useCompleteOccurrence(occurrence, occurrence.assignee?.color);
    const asksWho = asksWhoDidIt(canCompleteOnBehalf, occurrence, usePage<SharedData>().props.auth.user.id);

    const [whoOpen, setWhoOpen] = useState(false);

    // Swipe right completes, or for a parent on someone else's task opens "Wer hat es erledigt?" like the ring does.
    // Swipe left on a recurring task skips this time (the series stays); on a one-off it deletes. Either way the
    // card springs back if the request fails, so the list never shows a change the server didn't get.
    const swipe = useSwipeToComplete(
        () => {
            if (asksWho) {
                setWhoOpen(true);
                return false;
            }
            complete(undefined, () => swipe.reset());
        },
        !occurrence.is_blocked && !processing,
        occurrence.can_delete
            ? () =>
                  occurrence.is_recurring
                      ? skipOccurrence(occurrence.id, occurrence.title, () => swipe.reset())
                      : deleteTask(occurrence.task_id, occurrence.title, () => swipe.reset())
            : undefined,
    );
    const reached = swipe.dx >= 96;
    const reachedDelete = swipe.dx <= -96;
    const leftIsSkip = occurrence.is_recurring;
    const overdue = occurrence.status === 'overdue';
    const priority = priorityClass[occurrence.priority];

    const assigneeColor = occurrence.assignee?.color ?? null;
    const ink = assigneeColor ? contrastTextColor(assigneeColor) : null;

    const completeButton = (
        <button
            type="button"
            disabled={processing}
            aria-label={`${occurrence.title}: ${t('task.markDone')}`}
            onClick={asksWho ? undefined : () => complete()}
            className={completeRingClass(Boolean(ink))}
        >
            <RingCheck onColour={Boolean(ink)} />
        </button>
    );

    return (
        <div className="relative overflow-hidden rounded-2xl">
            {/* Swipe-reveal background */}
            <div
                className={cn(
                    'absolute inset-0 flex items-center text-white transition-colors',
                    swipe.dx < 0
                        ? cn(
                              'justify-end gap-2 pr-5',
                              leftIsSkip ? 'bg-plate text-plate-foreground' : reachedDelete ? 'bg-swipe-delete-deep' : 'bg-swipe-delete',
                          )
                        : cn('gap-2 pl-5', reached ? 'bg-swipe-done-deep' : 'bg-swipe-done'),
                )}
                aria-hidden
            >
                {swipe.dx < 0 ? (
                    <>
                        {reachedDelete && <span className="text-sm font-bold">{leftIsSkip ? 'Diesmal überspringen' : t('common.delete')}</span>}
                        {leftIsSkip ? <SkipForward className="size-5" /> : <Trash2 className="size-5" />}
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
                className={cn('relative flex min-h-16 items-center gap-3 rounded-2xl py-2.5 pr-2.5 pl-4', !assigneeColor && 'bg-card border-[1.5px]')}
                style={{
                    transform: `translateX(${swipe.dx}px)`,
                    transition: swipe.animating ? 'transform 260ms var(--ease-pop)' : undefined,
                    touchAction: 'pan-y',
                    ...(assigneeColor ? memberFill(assigneeColor) : {}),
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
                        {occurrence.can_delete && !occurrence.is_private ? (
                            <AssigneeMenu occurrence={occurrence} members={members} />
                        ) : (
                            <span>{occurrence.assignee ? occurrence.assignee.name : t('task.unassigned')}</span>
                        )}
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
                    (asksWho ? (
                        <WhoDidItMenu
                            trigger={completeButton}
                            members={members}
                            assigneeId={occurrence.assignee?.id ?? null}
                            onPick={(m) => complete(m)}
                            open={whoOpen}
                            onOpenChange={setWhoOpen}
                        />
                    ) : (
                        completeButton
                    ))}
                {/* Blocked: a dashed ring with a lock where the complete ring would be, instead of dimming the card (and its text contrast). */}
                {occurrence.is_blocked && (
                    <span
                        className={cn(
                            'grid size-11 shrink-0 place-items-center rounded-full border-2 border-dashed border-current',
                            !ink && 'border-muted-foreground text-muted-foreground',
                        )}
                    >
                        <Lock className="size-5" aria-label={t('task.blocked')} />
                    </span>
                )}
            </div>
        </div>
    );
}
