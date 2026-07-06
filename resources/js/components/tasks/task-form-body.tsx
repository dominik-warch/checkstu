import { useForm } from '@inertiajs/react';
import { ChevronDown, Plus, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import TitleAutocomplete from '@/components/tasks/title-autocomplete';
import { t } from '@/lib/i18n';
import { buildRrule, RruleFreq, WEEKDAYS } from '@/lib/rrule';
import type { Member, TaskTemplateSummary } from '@/types/checkstu';

const NONE = 'none';

type RecurrenceMode = 'one_off' | 'rrule' | 'explicit_dates' | 'relative';

export interface EditableTask {
    id: number;
    title: string;
    description: string | null;
    priority: number;
    is_private: boolean;
    due_date: string | null;
    assignee_id: number | null;
}

interface TaskFormBodyProps {
    members: Member[];
    task?: EditableTask; // present => edit mode
    templates?: TaskTemplateSummary[]; // name catalogue for the title autocomplete (create mode only)
    onSaved: () => void;
}

/**
 * The create/edit form fields, without a Dialog wrapper — so it can be
 * embedded either directly in TaskFormDialog (edit mode, or the "custom" path
 * from the template picker) without nesting a Dialog inside a Dialog.
 *
 * Editing an existing task's recurrence pattern is out of scope for v1 —
 * delete and recreate it instead. So the recurrence picker only appears when
 * creating a new task; the backend (UpdateTaskAction) never reads these
 * fields on edit.
 */
export default function TaskFormBody({ members, task, templates = [], onSaved }: TaskFormBodyProps) {
    const isEdit = Boolean(task);
    const [showMore, setShowMore] = useState(false);

    const form = useForm({
        title: task?.title ?? '',
        description: task?.description ?? '',
        priority: task?.priority ?? 1,
        is_private: task?.is_private ?? false,
        default_assignee_id: task?.assignee_id ?? (null as number | null),
        due_date: task?.due_date ?? '',

        recurrence_type: 'one_off' as RecurrenceMode,
        rrule_freq: 'WEEKLY' as RruleFreq,
        rrule_interval: 1,
        rrule_byday: [] as string[],
        anchor_date: '',
        relative_interval_days: 3,
        explicit_dates: [] as string[],
    });
    const { data, setData, processing, errors, reset } = form;
    // `errors` is typed from the form's client-side data shape, but the server
    // validates transformed field names too (e.g. `rrule`, computed at submit
    // time — see the transform() call below), so index it loosely for those.
    const serverErrors = errors as Record<string, string | undefined>;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        form.transform((current) => ({
            title: current.title,
            description: current.description,
            priority: current.priority,
            is_private: current.is_private,
            default_assignee_id: current.default_assignee_id,
            due_date: current.due_date,
            ...(isEdit
                ? {}
                : {
                      recurrence_type: current.recurrence_type,
                      ...(current.recurrence_type === 'rrule' && {
                          rrule: buildRrule(current.rrule_freq, current.rrule_interval, current.rrule_byday),
                          anchor_date: current.anchor_date,
                      }),
                      ...(current.recurrence_type === 'relative' && {
                          relative_interval_days: current.relative_interval_days,
                      }),
                      ...(current.recurrence_type === 'explicit_dates' && {
                          explicit_dates: current.explicit_dates.filter(Boolean),
                      }),
                  }),
        }));

        const onSuccess = () => {
            if (!isEdit) reset();
            onSaved();
        };
        if (isEdit && task) {
            form.patch(route('tasks.update', task.id), { preserveScroll: true, onSuccess });
        } else {
            form.post(route('tasks.store'), { preserveScroll: true, onSuccess });
        }
    };

    const intervalUnit =
        data.rrule_freq === 'DAILY'
            ? t('recurrence.intervalDaysUnit')
            : data.rrule_freq === 'MONTHLY'
              ? t('recurrence.intervalMonthsUnit')
              : t('recurrence.intervalWeeksUnit');

    return (
        <form onSubmit={submit} className="grid gap-4">
            <TitleAutocomplete
                value={data.title}
                onChange={(v) => setData('title', v)}
                templates={isEdit ? [] : templates}
                error={errors.title}
            />

            <div className="grid gap-2">
                <Label htmlFor="description">{t('task.description')}</Label>
                <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
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

                {data.recurrence_type !== 'explicit_dates' && (
                    <div className="grid gap-2">
                        <Label htmlFor="due_date">
                            {data.recurrence_type === 'rrule' ? t('recurrence.startsOn') : t('task.dueDate')}
                        </Label>
                        <Input
                            id="due_date"
                            type="date"
                            value={data.recurrence_type === 'rrule' ? data.anchor_date : data.due_date}
                            onChange={(e) =>
                                data.recurrence_type === 'rrule'
                                    ? setData('anchor_date', e.target.value)
                                    : setData('due_date', e.target.value)
                            }
                        />
                        <InputError message={errors.due_date ?? errors.anchor_date} />
                    </div>
                )}
            </div>

            {!data.is_private && (
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
            )}

            <label className="flex items-center gap-3 rounded-lg border p-3">
                <Checkbox
                    checked={data.is_private}
                    onClick={() => {
                        const next = !data.is_private;
                        setData({
                            ...data,
                            is_private: next,
                            default_assignee_id: next ? null : data.default_assignee_id,
                        });
                    }}
                />
                <span className="flex flex-col">
                    <span className="text-sm font-medium">{t('task.private')}</span>
                    <span className="text-muted-foreground text-xs">{t('task.privateHint')}</span>
                </span>
            </label>

            {!isEdit && (
                <Collapsible open={showMore} onOpenChange={setShowMore}>
                    <CollapsibleTrigger asChild>
                        <Button type="button" variant="ghost" size="sm" className="justify-start px-0">
                            <ChevronDown className={`size-4 transition-transform ${showMore ? 'rotate-180' : ''}`} />
                            {t('task.moreOptions')}
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="grid gap-4 pt-2">
                        <div className="grid gap-2">
                            <Label>{t('recurrence.title')}</Label>
                            <Select value={data.recurrence_type} onValueChange={(v) => setData('recurrence_type', v as RecurrenceMode)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="one_off">{t('recurrence.oneOff')}</SelectItem>
                                    <SelectItem value="rrule">{t('recurrence.regular')}</SelectItem>
                                    <SelectItem value="explicit_dates">{t('recurrence.irregular')}</SelectItem>
                                    <SelectItem value="relative">{t('recurrence.everyNDays')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {data.recurrence_type === 'rrule' && (
                            <div className="grid gap-4 rounded-lg border p-3">
                                <div className="grid grid-cols-[1fr_auto_1fr] items-end gap-2">
                                    <div className="grid gap-2">
                                        <Label>{t('recurrence.frequency')}</Label>
                                        <Select value={data.rrule_freq} onValueChange={(v) => setData('rrule_freq', v as RruleFreq)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="DAILY">{t('recurrence.daily')}</SelectItem>
                                                <SelectItem value="WEEKLY">{t('recurrence.weekly')}</SelectItem>
                                                <SelectItem value="MONTHLY">{t('recurrence.monthly')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <span className="text-muted-foreground pb-2 text-sm">{t('recurrence.every')}</span>
                                    <div className="grid gap-2">
                                        <Label htmlFor="rrule_interval">{intervalUnit}</Label>
                                        <Input
                                            id="rrule_interval"
                                            type="number"
                                            min={1}
                                            max={365}
                                            value={data.rrule_interval}
                                            onChange={(e) => setData('rrule_interval', Number(e.target.value) || 1)}
                                        />
                                    </div>
                                </div>

                                {data.rrule_freq === 'WEEKLY' && (
                                    <div className="grid gap-2">
                                        <Label>{t('recurrence.weekdays')}</Label>
                                        <ToggleGroup type="multiple" value={data.rrule_byday} onValueChange={(v) => setData('rrule_byday', v)}>
                                            {WEEKDAYS.map((d) => (
                                                <ToggleGroupItem key={d.value} value={d.value} aria-label={d.label}>
                                                    {d.label}
                                                </ToggleGroupItem>
                                            ))}
                                        </ToggleGroup>
                                    </div>
                                )}
                                <InputError message={serverErrors.rrule} />
                            </div>
                        )}

                        {data.recurrence_type === 'relative' && (
                            <div className="grid gap-2 rounded-lg border p-3">
                                <Label htmlFor="relative_interval_days">{t('recurrence.daysAfterCompletion')}</Label>
                                <Input
                                    id="relative_interval_days"
                                    type="number"
                                    min={1}
                                    max={365}
                                    value={data.relative_interval_days}
                                    onChange={(e) => setData('relative_interval_days', Number(e.target.value) || 1)}
                                />
                                <InputError message={errors.relative_interval_days} />
                            </div>
                        )}

                        {data.recurrence_type === 'explicit_dates' && (
                            <div className="grid gap-2 rounded-lg border p-3">
                                <Label>{t('recurrence.specificDates')}</Label>
                                {data.explicit_dates.map((date, i) => (
                                    <div key={i} className="flex gap-2">
                                        <Input
                                            type="date"
                                            value={date}
                                            onChange={(e) => {
                                                const next = [...data.explicit_dates];
                                                next[i] = e.target.value;
                                                setData('explicit_dates', next);
                                            }}
                                        />
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('common.delete')}
                                            onClick={() =>
                                                setData(
                                                    'explicit_dates',
                                                    data.explicit_dates.filter((_, idx) => idx !== i),
                                                )
                                            }
                                        >
                                            <X className="size-4" />
                                        </Button>
                                    </div>
                                ))}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setData('explicit_dates', [...data.explicit_dates, ''])}
                                >
                                    <Plus className="size-4" />
                                    {t('recurrence.addDate')}
                                </Button>
                                <InputError message={errors.explicit_dates} />
                            </div>
                        )}
                    </CollapsibleContent>
                </Collapsible>
            )}

            <DialogFooter>
                <Button type="submit" disabled={processing}>
                    {t('common.save')}
                </Button>
            </DialogFooter>
        </form>
    );
}
