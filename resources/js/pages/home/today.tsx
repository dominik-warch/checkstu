import { Head, Link, usePage } from '@inertiajs/react';
import { Dices } from 'lucide-react';
import { useEffect, useRef } from 'react';

import { EmptyState } from '@/components/page-header';
import Pawn from '@/components/pawn';
import SectionHead from '@/components/section-head';
import ScopeFilter from '@/components/tasks/scope-filter';
import TaskCard, { formatDue } from '@/components/tasks/task-card';
import TaskFormDialog from '@/components/tasks/task-form-dialog';
import { useCompleteOccurrence } from '@/hooks/use-complete-occurrence';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { Member, Occurrence, TaskAbilities, TaskTemplateSummary } from '@/types/checkstu';

interface TodayProps {
    occurrences: Occurrence[];
    progress: { done: number; open: number };
    filters: { scope: 'all' | 'mine' };
    members: Member[];
    templates: TaskTemplateSummary[];
    can: TaskAbilities;
}

export default function Today({ occurrences, progress, filters, members, templates, can }: TodayProps) {
    const user = usePage<SharedData>().props.auth.user;
    const firstName = user.name.split(' ')[0];

    // Up next: your own most pressing task, else the first one up for grabs. Pressing means priority first
    // (Dringend before Hoch before the rest), then the server's due-date order (sort is stable).
    const actionable = occurrences.filter((o) => !o.is_blocked).sort((a, b) => b.priority - a.priority);
    const upNext = actionable.find((o) => o.assignee?.id === user.id) ?? actionable.find((o) => o.assignee === null) ?? null;

    const rest = occurrences.filter((o) => o !== upNext);
    const blocked = rest.filter((o) => o.is_blocked);
    const overdue = rest.filter((o) => !o.is_blocked && o.status === 'overdue');
    const today = rest.filter((o) => !o.is_blocked && o.status !== 'overdue');

    // Under the "Heute" head every due label would just repeat "Heute", so that group drops it.
    const card = (o: Occurrence, showDue = true) => (
        <TaskCard key={o.id} occurrence={o} members={members} canCompleteOnBehalf={can.completeOnBehalf} showDue={showDue} />
    );

    return (
        <CheckstuLayout>
            <Head title={t('nav.today')} />

            <div className="md:grid md:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] md:items-start md:gap-6 lg:grid-cols-[minmax(0,23rem)_minmax(0,1fr)] lg:gap-10">
                {/* The player's own corner of the board: what's next, and how far home today. */}
                <section
                    aria-label="Als Nächstes"
                    className="bg-player text-player-ink -mx-4 -mt-4 px-4 pt-2 pb-5 md:sticky md:top-[calc(env(safe-area-inset-top)+4.5rem)] md:m-0 md:rounded-3xl md:p-5 lg:top-10"
                >
                    <p className="text-base font-medium">
                        Hallo {firstName}
                        {upNext ? ', als Nächstes:' : '!'}
                    </p>

                    {upNext ? (
                        <UpNextPlate
                            occurrence={upNext}
                            playerColor={(user.color as string | null) ?? null}
                            remaining={Math.max(0, progress.open - (upNext.assignee ? 1 : 0))}
                        />
                    ) : (
                        <AllClear />
                    )}

                    <HomeFields done={progress.done} open={progress.open} />
                </section>

                <div className="min-w-0">
                    <div className="mt-4 flex items-center justify-between gap-4 md:mt-0">
                        <h1 className="text-2xl font-bold tracking-tight">{t('nav.today')}</h1>
                        <ScopeFilter routeName="home" scope={filters.scope} className="w-44" />
                    </div>

                    {rest.length === 0 && (
                        <EmptyState className="mt-6">
                            {filters.scope === 'mine' ? 'Sonst steht heute nichts an.' : 'Heute steht nichts mehr an.'}
                        </EmptyState>
                    )}

                    {overdue.length > 0 && (
                        <section>
                            <SectionHead count={overdue.length}>
                                <span className="text-urgent">Überfällig</span>
                            </SectionHead>
                            <div className="flex flex-col gap-2">{overdue.map((o) => card(o))}</div>
                        </section>
                    )}
                    {today.length > 0 && (
                        <section>
                            <SectionHead count={today.length}>Fällig heute</SectionHead>
                            <div className="flex flex-col gap-2">{today.map((o) => card(o, false))}</div>
                        </section>
                    )}
                    {blocked.length > 0 && (
                        <section>
                            <SectionHead count={blocked.length}>Blockiert</SectionHead>
                            <div className="flex flex-col gap-2">{blocked.map((o) => card(o))}</div>
                        </section>
                    )}
                </div>
            </div>

            {can.createTask && <TaskFormDialog members={members} templates={templates} />}
        </CheckstuLayout>
    );
}

/** The one reversed plate on the screen: the task to do next, with a big round target to finish it. */
function UpNextPlate({ occurrence, playerColor, remaining }: { occurrence: Occurrence; playerColor: string | null; remaining: number }) {
    const { processing, complete } = useCompleteOccurrence(occurrence, occurrence.assignee?.color ?? playerColor);
    const overdue = occurrence.status === 'overdue';
    // formatDue says "Gestern"/"7. Nov." on its own; inside this sentence it reads "seit gestern" / "am 7. Nov.".
    const due = formatDue(occurrence.due_date);
    const dueInSentence = ['Heute', 'Morgen', 'Gestern', 'Irgendwann'].includes(due) ? due.toLowerCase() : overdue ? due : `am ${due}`;
    const priority = occurrence.priority >= 2 ? t(occurrence.priority === 3 ? 'priority.urgent' : 'priority.high') : null;

    return (
        <div className="bg-plate text-plate-foreground mt-2 flex items-center gap-4 rounded-3xl p-4 pl-5">
            <div className="min-w-0 flex-1">
                <Link
                    href={route('tasks.show', occurrence.task_id)}
                    className="block text-2xl font-bold tracking-tight [overflow-wrap:break-word] hyphens-auto underline-offset-4 [hyphenate-limit-chars:auto_3_4] hover:underline"
                >
                    {occurrence.title}
                </Link>
                <p className="mt-1.5 text-sm text-white/75">
                    <span className={cn(overdue && 'font-bold text-[#ff8fa3]')}>
                        {overdue ? 'Überfällig seit ' : 'Fällig '}
                        {dueInSentence}
                    </span>
                    {priority && <span className="font-bold text-white"> · {priority}</span>}
                    {!occurrence.assignee && ' · Für alle'}
                    {' · '}
                    {remaining === 0 ? 'danach hast du frei.' : remaining === 1 ? 'danach noch 1 Aufgabe.' : `danach noch ${remaining} Aufgaben.`}
                </p>
            </div>
            <button
                type="button"
                onClick={() => complete()}
                disabled={processing}
                className="group/go flex shrink-0 flex-col items-center gap-1 transition-transform duration-150 active:scale-90 disabled:opacity-60"
            >
                <span className="border-player grid size-16 place-items-center rounded-full border-4">
                    <Pawn className="text-player size-8 transition-transform duration-300 ease-[var(--ease-pop)] group-hover/go:-translate-y-1 group-hover/go:scale-110" />
                </span>
                <span className="text-sm font-bold">Fertig!</span>
                <span className="sr-only">{occurrence.title}</span>
            </button>
        </div>
    );
}

function AllClear() {
    return (
        <div className="bg-plate text-plate-foreground mt-2 flex items-center gap-4 rounded-3xl p-5">
            <div className="min-w-0 flex-1">
                <p className="text-2xl font-bold tracking-tight">Alles geschafft.</p>
                <p className="mt-1.5 text-sm text-white/70">Für dich steht heute nichts mehr an.</p>
            </div>
            <Link
                href="/boardgames"
                className="grid size-16 shrink-0 place-items-center rounded-full border-4 border-white/25 hover:border-white/60"
                aria-label="Zeit für ein Brettspiel"
                title="Zeit für ein Brettspiel?"
            >
                <Dices className="size-7" />
            </Link>
        </div>
    );
}

/**
 * Your home fields for today, Mensch-ärgere-dich-nicht style: one circle per task of
 * yours, filled as you finish them, with your pawn on the next field. When you finish
 * one, the field fills and the pawn hops on. Only ever your own progress, never a comparison.
 */
function HomeFields({ done, open }: { done: number; open: number }) {
    const total = done + open;
    const previousDone = useRef(done);
    const moved = done > previousDone.current;
    useEffect(() => {
        previousDone.current = done;
    }, [done]);

    if (total === 0) return null;

    const shown = Math.min(total, 10);
    // The pawn stands on the next open field, or on the last one once you're home.
    const pawnAt = Math.min(done, shown - 1);
    const home = done >= total;

    return (
        <div className="mt-4 flex items-center gap-3">
            <ol className="flex flex-1 flex-wrap gap-1.5" aria-hidden>
                {Array.from({ length: shown }, (_, i) => (
                    <li
                        key={i}
                        className={cn(
                            'grid size-7 place-items-center rounded-full border-[2.5px] border-current',
                            i < done && 'bg-player-ink',
                            moved && i === done - 1 && 'animate-field-fill',
                        )}
                    >
                        {i === pawnAt && (
                            <Pawn key={done} className={cn('size-5', home ? 'text-player' : 'text-current', moved && 'animate-pawn-hop')} />
                        )}
                    </li>
                ))}
            </ol>
            <p className="shrink-0 text-sm font-bold tabular-nums">{home ? 'Alles im Ziel!' : `${done} von ${total} geschafft`}</p>
        </div>
    );
}
