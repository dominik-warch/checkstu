import { Plus } from 'lucide-react';
import { ReactNode, useState } from 'react';

import TaskFormBody, { EditableTask } from '@/components/tasks/task-form-body';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { t } from '@/lib/i18n';
import type { Member, TaskTemplateSummary } from '@/types/checkstu';

export type { EditableTask };

interface TaskFormDialogProps {
    members: Member[];
    task?: EditableTask; // present => edit mode
    templates?: TaskTemplateSummary[]; // name catalogue for the title autocomplete (create mode)
    trigger?: ReactNode; // custom trigger; defaults to the "+" docked in the middle of the phone tab bar (create mode)
    defaultDue?: string; // Y-m-d a new task starts with
}

export default function TaskFormDialog({ members, task, templates, trigger, defaultDue }: TaskFormDialogProps) {
    const isEdit = Boolean(task);
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button
                        size="icon"
                        className="bg-player text-player-ink hover:bg-player ring-card lg:bg-player-ink lg:text-player lg:hover:bg-player-ink fixed bottom-[calc(env(safe-area-inset-bottom)+0.875rem)] left-1/2 z-30 size-14 -translate-x-1/2 rounded-full ring-4 hover:brightness-110 lg:top-5 lg:right-auto lg:bottom-auto lg:left-[12.75rem] lg:size-11 lg:translate-x-0 lg:ring-0"
                        aria-label={t('task.newTask')}
                    >
                        <Plus className="size-6" />
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{isEdit ? t('task.editTask') : t('task.newTask')}</DialogTitle>
                    <DialogDescription>
                        {isEdit ? 'Passe Titel, Fälligkeit oder Zuständigkeit an.' : 'Was steht an, und wer ist dran?'}
                    </DialogDescription>
                </DialogHeader>

                <TaskFormBody members={members} task={task} templates={templates} defaultDue={defaultDue} onSaved={() => setOpen(false)} />
            </DialogContent>
        </Dialog>
    );
}
