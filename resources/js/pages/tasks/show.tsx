import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Check, Circle, Pencil, Trash2 } from 'lucide-react';

import TaskFormDialog from '@/components/tasks/task-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import CheckstuLayout from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { CategoryTag, Member } from '@/types/checkstu';

interface TaskDetail {
    id: number;
    title: string;
    description: string | null;
    priority: number;
    due_date: string | null;
    assignee_id: number | null;
    assignee: { id: number; name: string } | null;
    category_ids: number[];
    categories: CategoryTag[];
    blocked_by: { id: number; title: string; done: boolean }[];
    blocks: { id: number; title: string }[];
}

interface ShowProps {
    task: TaskDetail;
    members: Member[];
    can: { update: boolean; delete: boolean };
}

const priorityLabel = ['priority.low', 'priority.normal', 'priority.high', 'priority.urgent'] as const;

export default function Show({ task, members, can }: ShowProps) {
    const remove = () => {
        if (confirm(t('task.deleteConfirm'))) {
            router.delete(route('tasks.destroy', task.id));
        }
    };

    return (
        <CheckstuLayout>
            <Head title={task.title} />

            <Link href={route('tasks.index')} className="text-muted-foreground mb-4 inline-flex items-center gap-1 text-sm">
                <ArrowLeft className="size-4" />
                {t('nav.tasks')}
            </Link>

            <div className="mb-4 flex items-start justify-between gap-3">
                <h1 className="text-2xl font-bold tracking-tight">{task.title}</h1>
                <Badge variant="secondary">{t(priorityLabel[task.priority])}</Badge>
            </div>

            <dl className="mb-6 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                <dt className="text-muted-foreground">{t('task.dueDate')}</dt>
                <dd>{task.due_date ?? 'Irgendwann'}</dd>
                <dt className="text-muted-foreground">{t('task.assignee')}</dt>
                <dd>{task.assignee?.name ?? '—'}</dd>
                <dt className="text-muted-foreground">{t('task.description')}</dt>
                <dd>{task.description ?? <span className="text-muted-foreground">{t('task.noDescription')}</span>}</dd>
            </dl>

            {task.categories.length > 0 && (
                <div className="mb-6 flex flex-wrap gap-2">
                    {task.categories.map((c) => (
                        <Badge key={c.id} variant="outline">
                            {c.name}
                        </Badge>
                    ))}
                </div>
            )}

            {task.blocked_by.length > 0 && (
                <section className="mb-6">
                    <h2 className="text-muted-foreground mb-2 text-sm font-semibold uppercase">{t('task.blockedBy')}</h2>
                    <ul className="flex flex-col gap-1">
                        {task.blocked_by.map((b) => (
                            <li key={b.id} className="flex items-center gap-2 text-sm">
                                {b.done ? (
                                    <Check className="size-4 text-emerald-600" />
                                ) : (
                                    <Circle className="text-muted-foreground size-4" />
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
                    <h2 className="text-muted-foreground mb-2 text-sm font-semibold uppercase">{t('task.blocks')}</h2>
                    <ul className="flex flex-col gap-1">
                        {task.blocks.map((b) => (
                            <li key={b.id} className="text-sm">
                                <Link href={route('tasks.show', b.id)} className="hover:underline">
                                    {b.title}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            {(can.update || can.delete) && (
                <div className="flex gap-2">
                    {can.update && (
                        <TaskFormDialog
                            members={members}
                            task={{
                                id: task.id,
                                title: task.title,
                                description: task.description,
                                priority: task.priority,
                                due_date: task.due_date,
                                assignee_id: task.assignee_id,
                            }}
                            trigger={
                                <Button variant="outline">
                                    <Pencil className="size-4" />
                                    {t('common.edit')}
                                </Button>
                            }
                        />
                    )}
                    {can.delete && (
                        <Button variant="ghost" className="text-destructive" onClick={remove}>
                            <Trash2 className="size-4" />
                            {t('common.delete')}
                        </Button>
                    )}
                </div>
            )}
        </CheckstuLayout>
    );
}
