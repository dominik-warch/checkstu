import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Check, Circle, Lock, Pencil, Repeat, SkipForward, Trash2 } from 'lucide-react';

import { asksWhoDidIt, deleteTask, priorityClass, priorityLabel, skipOccurrence, WhoDidItMenu } from '@/components/tasks/task-card';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import { Button } from '@/components/ui/button';
import { useCompleteOccurrence } from '@/hooks/use-complete-occurrence';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { contrastTextColor, memberFill } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { previousPage } from '@/lib/previous-page';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { CategoryTag, Member, Occurrence } from '@/types/checkstu';

interface TaskDetail {
    id: number;
    title: string;
    description: string | null;
    priority: number;
    is_private: boolean;
    is_recurring: boolean;
    due_date: string | null;
    assignee_id: number | null;
    assignee: { id: number; name: string; color?: string | null } | null;
    category_ids: number[];
    categories: CategoryTag[];
    blocked_by: { id: number; title: string; done: boolean }[];
    blocks: { id: number; title: string }[];
}

interface ShowProps {
    task: TaskDetail;
    occurrence: Occurrence | null;
    members: Member[];
    can: { update: boolean; delete: boolean; complete: boolean; completeOnBehalf: boolean };
}

function formatLongDate(due: string | null): string {
    if (!due) return 'Irgendwann';
    return new Intl.DateTimeFormat('de-DE', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date(due.slice(0, 10) + 'T00:00:00'));
}

export default function Show({ task, occurrence, members, can }: ShowProps) {
    const { processing, complete } = useCompleteOccurrence(occurrence, occurrence?.assignee?.color);
    const me = usePage<SharedData>().props.auth.user;

    // Back goes where you came from (Heute, Aufgaben or Archiv); on a fresh load, to Aufgaben.
    const from = previousPage();
    const back =
        from !== null && /^\/(\?|$)/.test(from)
            ? { href: from, label: t('nav.today'), isHistory: true }
            : from !== null && /^\/archive(\?|$)/.test(from)
              ? { href: from, label: t('archive.title'), isHistory: true }
              : from !== null && /^\/tasks(\?|$)/.test(from)
                ? { href: from, label: t('nav.tasks'), isHistory: true }
                : { href: route('tasks.index'), label: t('nav.tasks'), isHistory: false };

    const color = occurrence?.assignee?.color ?? null;
    const ink = color ? contrastTextColor(color) : null;

    return (
        <CheckstuLayout>
            <Head title={task.title} />

            <Link
                href={back.href}
                onClick={(e) => {
                    // Going back through history restores the list's scroll position and its Meine/Alle scope.
                    if (back.isHistory) {
                        e.preventDefault();
                        window.history.back();
                    }
                }}
                className="text-muted-foreground hover:text-foreground mb-3 -ml-2 inline-flex min-h-11 items-center gap-1.5 rounded-full px-2 text-sm font-medium"
            >
                <ArrowLeft className="size-5" />
                {back.label}
            </Link>

            <div className="lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,20rem)] lg:items-start lg:gap-10">
                <div className="min-w-0">
                    {/* The task as one big field, in its assignee's colour. */}
                    <section className={cn('rounded-3xl p-5', !color && 'bg-card border-[1.5px]')} style={color ? memberFill(color) : undefined}>
                        <div className={cn('flex flex-wrap items-center gap-2 text-sm font-bold', !ink && 'text-muted-foreground')}>
                            {task.priority >= 2 && (
                                <span className={cn('rounded-full px-2.5 leading-6', priorityClass[task.priority])}>
                                    {t(priorityLabel[task.priority])}
                                </span>
                            )}
                            {task.is_private && (
                                <span className="inline-flex items-center gap-1">
                                    <Lock className="size-4" />
                                    {t('task.private')}
                                </span>
                            )}
                            {task.is_recurring && (
                                <span className="inline-flex items-center gap-1">
                                    <Repeat className="size-4" />
                                    {t('task.recurring')}
                                </span>
                            )}
                        </div>
                        <h1 className="mt-2 text-2xl font-bold tracking-tight [overflow-wrap:break-word] hyphens-auto">{task.title}</h1>

                        <dl className="mt-5 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-base">
                            <dt className={cn(!ink && 'text-muted-foreground')}>{t('task.dueDate')}</dt>
                            <dd className="font-bold">
                                {formatLongDate(task.due_date)}
                                {occurrence?.status === 'overdue' && ' · überfällig'}
                            </dd>
                            <dt className={cn(!ink && 'text-muted-foreground')}>{t('task.assignee')}</dt>
                            <dd className="font-bold">{task.assignee?.name ?? t('task.unassigned')}</dd>
                            {task.categories.length > 0 && (
                                <>
                                    <dt className={cn(!ink && 'text-muted-foreground')}>Kategorie</dt>
                                    <dd className="flex flex-wrap gap-x-3 font-bold">
                                        {task.categories.map((c) => (
                                            <span key={c.id} className="inline-flex items-center gap-1.5">
                                                <span
                                                    className="size-2.5 rounded-full ring-1 ring-current/30"
                                                    style={{ background: c.color ?? '#999' }}
                                                />
                                                {c.name}
                                            </span>
                                        ))}
                                    </dd>
                                </>
                            )}
                        </dl>

                        {task.description ? (
                            <p className="mt-4 max-w-[65ch] text-base whitespace-pre-line">{task.description}</p>
                        ) : (
                            <p className={cn('mt-4 text-sm', !ink && 'text-muted-foreground')}>{t('task.noDescription')}</p>
                        )}
                    </section>

                    {occurrence && can.complete && (
                        // Pinned above the tab bar on phones, so "done" is always under the thumb.
                        <div className="bg-background sticky bottom-[calc(env(safe-area-inset-bottom)+4.75rem)] z-10 -mx-4 mt-3 px-4 py-2 lg:static lg:m-0 lg:mt-4 lg:bg-transparent lg:p-0">
                            {asksWhoDidIt(can.completeOnBehalf, occurrence, me.id) ? (
                                <WhoDidItMenu
                                    trigger={
                                        <Button size="lg" className="w-full" disabled={processing || occurrence.is_blocked}>
                                            <Check strokeWidth={3} />
                                            {t('task.markDone')}
                                        </Button>
                                    }
                                    members={members}
                                    assigneeId={occurrence.assignee?.id ?? null}
                                    onPick={complete}
                                    align="start"
                                    className="w-(--radix-dropdown-menu-trigger-width)"
                                />
                            ) : (
                                <Button size="lg" className="w-full" disabled={processing || occurrence.is_blocked} onClick={() => complete()}>
                                    <Check strokeWidth={3} />
                                    {t('task.markDone')}
                                </Button>
                            )}
                            {occurrence.is_blocked && occurrence.blocking_titles.length > 0 && (
                                <p className="text-muted-foreground mt-2 flex items-center gap-1.5 text-sm">
                                    <Lock className="size-4" />
                                    {t('task.waitingOn', { task: occurrence.blocking_titles.join(', ') })}
                                </p>
                            )}
                        </div>
                    )}

                    {!occurrence && (
                        <p className="text-done mt-4 flex items-center gap-2 text-base font-bold">
                            <Check className="size-5" strokeWidth={3} />
                            {t('task.alreadyDone')}
                        </p>
                    )}
                </div>

                <aside className="mt-6 lg:mt-0">
                    {task.blocked_by.length > 0 && (
                        <section className="mb-6">
                            <h2 className="mb-2 text-sm font-bold">{t('task.blockedBy')}</h2>
                            <ul className="flex flex-col gap-1">
                                {task.blocked_by.map((b) => (
                                    <li key={b.id} className="flex min-h-11 items-center gap-2 text-base">
                                        {b.done ? (
                                            <Check className="text-done size-5" strokeWidth={3} />
                                        ) : (
                                            <Circle className="text-muted-foreground size-5" />
                                        )}
                                        <Link href={route('tasks.show', b.id)} className="hover:underline">
                                            {b.title}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {task.blocks.length > 0 && (
                        <section className="mb-6">
                            <h2 className="mb-2 text-sm font-bold">{t('task.blocks')}</h2>
                            <ul className="flex flex-col gap-1">
                                {task.blocks.map((b) => (
                                    <li key={b.id} className="flex min-h-11 items-center text-base">
                                        <Link href={route('tasks.show', b.id)} className="hover:underline">
                                            {b.title}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {(can.update || can.delete) && (
                        <div className="mt-2 flex flex-wrap gap-2">
                            {can.update && (
                                <TaskFormDialog
                                    members={members}
                                    task={{
                                        id: task.id,
                                        title: task.title,
                                        description: task.description,
                                        priority: task.priority,
                                        is_private: task.is_private,
                                        due_date: task.due_date,
                                        assignee_id: task.assignee_id,
                                    }}
                                    trigger={
                                        <Button variant="outline">
                                            <Pencil />
                                            {t('common.edit')}
                                        </Button>
                                    }
                                />
                            )}
                            {can.delete && occurrence && (
                                <Button variant="outline" onClick={() => skipOccurrence(occurrence.id, task.title, undefined, back.href)}>
                                    <SkipForward />
                                    Überspringen
                                </Button>
                            )}
                            {can.delete && (
                                <Button
                                    variant="ghost"
                                    className="text-destructive"
                                    onClick={() => deleteTask(task.id, task.title, undefined, back.href)}
                                >
                                    <Trash2 />
                                    {t('common.delete')}
                                </Button>
                            )}
                        </div>
                    )}
                </aside>
            </div>
        </CheckstuLayout>
    );
}
