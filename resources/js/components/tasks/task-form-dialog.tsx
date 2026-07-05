import { useForm } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { t } from '@/lib/i18n';
import type { Member } from '@/types/checkstu';

const NONE = 'none';

export interface EditableTask {
    id: number;
    title: string;
    description: string | null;
    priority: number;
    due_date: string | null;
    assignee_id: number | null;
}

interface TaskFormDialogProps {
    members: Member[];
    task?: EditableTask; // present => edit mode
    trigger?: ReactNode; // custom trigger; defaults to a FAB (create mode)
}

export default function TaskFormDialog({ members, task, trigger }: TaskFormDialogProps) {
    const isEdit = Boolean(task);
    const [open, setOpen] = useState(false);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        title: task?.title ?? '',
        description: task?.description ?? '',
        priority: task?.priority ?? 1,
        default_assignee_id: task?.assignee_id ?? (null as number | null),
        due_date: task?.due_date ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const onSuccess = () => {
            if (!isEdit) reset();
            setOpen(false);
        };
        if (isEdit && task) {
            patch(route('tasks.update', task.id), { preserveScroll: true, onSuccess });
        } else {
            post(route('tasks.store'), { preserveScroll: true, onSuccess });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button
                        size="icon"
                        className="fixed bottom-20 right-4 z-20 size-14 rounded-full shadow-lg"
                        aria-label={t('task.newTask')}
                    >
                        <Plus className="size-6" />
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{isEdit ? t('task.editTask') : t('task.newTask')}</DialogTitle>
                    <DialogDescription>Erstelle oder bearbeite eine Aufgabe für den Haushalt.</DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="title">{t('task.title')}</Label>
                        <Input
                            id="title"
                            autoFocus
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="z. B. Staubsaugen"
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">{t('task.description')}</Label>
                        <Input
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label>{t('task.priority')}</Label>
                            <Select value={String(data.priority)} onValueChange={(v) => setData('priority', Number(v))}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="0">{t('priority.low')}</SelectItem>
                                    <SelectItem value="1">{t('priority.normal')}</SelectItem>
                                    <SelectItem value="2">{t('priority.high')}</SelectItem>
                                    <SelectItem value="3">{t('priority.urgent')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="due_date">{t('task.dueDate')}</Label>
                            <Input
                                id="due_date"
                                type="date"
                                value={data.due_date}
                                onChange={(e) => setData('due_date', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>{t('task.assignee')}</Label>
                        <Select
                            value={data.default_assignee_id ? String(data.default_assignee_id) : NONE}
                            onValueChange={(v) => setData('default_assignee_id', v === NONE ? null : Number(v))}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>Niemand</SelectItem>
                                {members.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {t('common.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
