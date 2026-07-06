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
    trigger?: ReactNode; // custom trigger; defaults to a FAB (create mode)
}

export default function TaskFormDialog({ members, task, templates, trigger }: TaskFormDialogProps) {
    const isEdit = Boolean(task);
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button
                        size="icon"
                        className="fixed bottom-[calc(env(safe-area-inset-bottom)+5rem)] right-4 z-20 size-14 rounded-full shadow-lg"
                        aria-label={t('task.newTask')}
                    >
                        <Plus className="size-6" />
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{isEdit ? t('task.editTask') : t('task.newTask')}</DialogTitle>
                    <DialogDescription>Erstelle oder bearbeite eine Aufgabe für den Haushalt.</DialogDescription>
                </DialogHeader>

                <TaskFormBody members={members} task={task} templates={templates} onSaved={() => setOpen(false)} />
            </DialogContent>
        </Dialog>
    );
}
