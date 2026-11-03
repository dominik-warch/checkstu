import { useForm, usePage } from '@inertiajs/react';
import { ChevronDown, Plus, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { chipClass } from '@/components/chip-row';
import InputError from '@/components/input-error';
import TitleAutocomplete from '@/components/tasks/title-autocomplete';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { memberFill } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { buildRrule, RruleFreq, WEEKDAYS } from '@/lib/rrule';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import type { Member, TaskTemplateSummary } from '@/types/checkstu';

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
    /** Due date a new task starts with (Y-m-d); Heute passes today so what you add there shows up there. */
    defaultDue?: string;
    onSaved: () => void;
}

/** Local Y-m-d for today plus `offset` days (not toISOString, which is UTC and can be a day off). */
export function isoDay(offset = 0): string {
    const d = new Date();
    d.setDate(d.getDate() + offset);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
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
export default function TaskFormBody({ members, task, templates = [], defaultDue = '', onSaved }: TaskFormBodyProps) {
    const isEdit = Boolean(task);
    const [showMore, setShowMore] = useState(Boolean(task?.description) || Boolean(task?.is_private) || (task !== undefined && task.priority !== 1));
    const { auth } = usePage<SharedData>().props;

    const form = useForm({
        title: task?.title ?? '',
        description: task?.description ?? '',
        priority: task?.priority ?? 1,
        is_private: task?.is_private ?? false,
        default_assignee_id: task?.assignee_id ?? (null as number | null),
        due_date: task?.due_date?.slice(0, 10) ?? defaultDue,

        recurrence_type: 'one_off' as RecurrenceMode,
        rrule_freq: 'WEEKLY' as RruleFreq,
        rrule_interval: 1,
        rrule_byday: [] as string[],
        anchor_date: '',
        relative_interval_days: 3,
        explicit_dates: [] as string[],
    });
    const { data, setData, processing, errors, reset } = form;
    // "Datum…" opens the native picker for anything beyond today/tomorrow/someday.
    const [pickDate, setPickDate] = useState(() => data.due_date !== '' && data.due_date !== isoDay() && data.due_date !== isoDay(1));
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
                          rrule: buildRrule(current.rrule_freq, current.rrule_interval || 1, current.rrule_byday),
                          anchor_date: current.anchor_date,
                      }),
                      ...(current.recurrence_type === 'relative' && {
                          relative_interval_days: current.relative_interval_days || 1,
                      }),
                      ...(current.recurrence_type === 'explicit_dates' && {
                          explicit_dates: current.explicit_dates.filter(Boolean),
                      }),
                  }),
        }));

        const onSuccess = () => {
            if (!isEdit) {
                reset();
                setPickDate(false);
            }
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
            <TitleAutocomplete value={data.title} onChange={(v) => setData('title', v)} templates={isEdit ? [] : templates} error={errors.title} />

            {/* Whose task it is, in their colour: the question the sheet asks ("wer ist dran?") answered at a glance. */}
            <div className="grid gap-2">
                <Label id="assignee_label">{t('task.assignee')}</Label>
                <div className="flex flex-wrap gap-1.5" role="group" aria-labelledby="assignee_label">
                    {/* A private task must always have an assignee — otherwise nobody could see it. */}
                    {!data.is_private && (
                        <button
                            type="button"
                            aria-pressed={data.default_assignee_id === null}
                            onClick={() => setData('default_assignee_id', null)}
                            className={chipClass(data.default_assignee_id === null)}
                        >
                            {t('task.unassigned')}
                        </button>
                    )}
                    {members.map((m) => {
                        const selected = data.default_assignee_id === m.id;
                        return (
                            <button
                                key={m.id}
                                type="button"
                                aria-pressed={selected}
                                onClick={() => setData('default_assignee_id', m.id)}
                                className={cn(chipClass(selected), 'gap-1.5', selected && m.color && 'border-transparent')}
                                style={selected && m.color ? memberFill(m.color) : undefined}
                            >
                                {!selected && (
                                    <span
                                        className="size-2.5 rounded-full"
                                        style={{ background: m.color ?? 'var(--muted-foreground)' }}
                                        aria-hidden
                                    />
                                )}
                                {m.name}
                            </button>
                        );
                    })}
                </div>
                <InputError message={errors.default_assignee_id} />
            </div>

            {data.recurrence_type === 'rrule' && (
                <div className="grid gap-2">
                    <Label htmlFor="due_date">{t('recurrence.startsOn')}</Label>
                    <Input id="due_date" type="date" value={data.anchor_date} onChange={(e) => setData('anchor_date', e.target.value)} />
                    <InputError message={errors.anchor_date} />
                </div>
            )}

            {/* The common answers are one tap; only "Datum…" needs the native picker. */}
            {(data.recurrence_type === 'one_off' || data.recurrence_type === 'relative') && (
                <div className="grid gap-2">
                    <Label id="due_label">{t('task.dueDate')}</Label>
                    <div className="flex flex-wrap gap-1" role="group" aria-labelledby="due_label">
                        {[
                            { label: 'Heute', value: isoDay() },
                            { label: 'Morgen', value: isoDay(1) },
                            { label: 'Irgendwann', value: '' },
                        ].map((option) => (
                            <button
                                key={option.label}
                                type="button"
                                aria-pressed={!pickDate && data.due_date === option.value}
                                onClick={() => {
                                    setPickDate(false);
                                    setData('due_date', option.value);
                                }}
                                className={cn(chipClass(!pickDate && data.due_date === option.value), 'px-2.5')}
                            >
                                {option.label}
                            </button>
                        ))}
                        <button type="button" aria-pressed={pickDate} onClick={() => setPickDate(true)} className={cn(chipClass(pickDate), 'px-2.5')}>
                            {pickDate && data.due_date
                                ? new Intl.DateTimeFormat('de-DE', { day: 'numeric', month: 'short' }).format(new Date(data.due_date + 'T00:00:00'))
                                : 'Datum…'}
                        </button>
                    </div>
                    {pickDate && (
                        <Input
                            id="due_date"
                            type="date"
                            aria-labelledby="due_label"
                            value={data.due_date}
                            onChange={(e) => setData('due_date', e.target.value)}
                        />
                    )}
                    <InputError message={errors.due_date} />
                </div>
            )}

            {/* Recurrence sits right under the date it changes ("Fällig am" becomes "Startet am" for Regelmäßig). */}
            {!isEdit && (
                <div className="grid gap-2">
                    <Label id="recurrence_label">{t('recurrence.title')}</Label>
                    <div className="flex flex-wrap gap-1.5" role="group" aria-labelledby="recurrence_label">
                        {(
                            [
                                ['one_off', t('recurrence.oneOff')],
                                ['rrule', t('recurrence.regular')],
                                ['explicit_dates', t('recurrence.irregular')],
                                ['relative', t('recurrence.everyNDays')],
                            ] as [RecurrenceMode, string][]
                        ).map(([mode, label]) => (
                            <button
                                key={mode}
                                type="button"
                                aria-pressed={data.recurrence_type === mode}
                                onClick={() => setData('recurrence_type', mode)}
                                className={chipClass(data.recurrence_type === mode)}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                    {data.recurrence_type === 'rrule' && (
                        <div className="grid gap-4 rounded-lg border p-3">
                            <div className="grid grid-cols-[1fr_auto_1fr] items-end gap-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="rrule_freq">{t('recurrence.frequency')}</Label>
                                    <Select value={data.rrule_freq} onValueChange={(v) => setData('rrule_freq', v as RruleFreq)}>
                                        <SelectTrigger id="rrule_freq">
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
                                        inputMode="numeric"
                                        min={1}
                                        max={365}
                                        value={data.rrule_interval || ''}
                                        onChange={(e) => setData('rrule_interval', e.target.value === '' ? 0 : Number(e.target.value))}
                                        onBlur={() => {
                                            if (!data.rrule_interval) setData('rrule_interval', 1);
                                        }}
                                    />
                                </div>
                            </div>

                            {data.rrule_freq === 'WEEKLY' && (
                                <div className="grid gap-2">
                                    <Label id="weekdays_label">{t('recurrence.weekdays')}</Label>
                                    <ToggleGroup
                                        type="multiple"
                                        aria-labelledby="weekdays_label"
                                        value={data.rrule_byday}
                                        onValueChange={(v) => setData('rrule_byday', v)}
                                    >
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
                                inputMode="numeric"
                                min={1}
                                max={365}
                                value={data.relative_interval_days || ''}
                                onChange={(e) => setData('relative_interval_days', e.target.value === '' ? 0 : Number(e.target.value))}
                                onBlur={() => {
                                    if (!data.relative_interval_days) setData('relative_interval_days', 1);
                                }}
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
                                        aria-label={`${t('recurrence.specificDates')} ${i + 1}`}
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
                            <Button type="button" variant="outline" size="sm" onClick={() => setData('explicit_dates', [...data.explicit_dates, ''])}>
                                <Plus className="size-4" />
                                {t('recurrence.addDate')}
                            </Button>
                            <InputError message={errors.explicit_dates} />
                        </div>
                    )}
                </div>
            )}

            {/* Less common choices, folded so the sheet ends at its button; opens by itself when editing a task that uses any of them. */}
            <Collapsible open={showMore} onOpenChange={setShowMore}>
                <CollapsibleTrigger asChild>
                    <Button type="button" variant="ghost" size="sm" className="justify-start px-0">
                        <ChevronDown className={`size-4 transition-transform ${showMore ? 'rotate-180' : ''}`} />
                        {t('task.moreOptions')}
                    </Button>
                </CollapsibleTrigger>
                <CollapsibleContent className="grid gap-4 pt-2">
                    <div className="grid gap-2">
                        <Label htmlFor="priority">{t('task.priority')}</Label>
                        <Select value={String(data.priority)} onValueChange={(v) => setData('priority', Number(v))}>
                            <SelectTrigger id="priority">
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

                    <label className="flex items-center gap-3 rounded-lg border p-3">
                        <Checkbox
                            aria-label={t('task.private')}
                            checked={data.is_private}
                            onClick={() => {
                                const next = !data.is_private;
                                setData({
                                    ...data,
                                    is_private: next,
                                    // Ensure an assignee is set the moment privacy is turned on; leave it
                                    // untouched otherwise (turning privacy back off shouldn't unassign it).
                                    default_assignee_id: next && !data.default_assignee_id ? auth.user.id : data.default_assignee_id,
                                });
                            }}
                        />
                        <span className="flex flex-col">
                            <span className="text-sm font-medium">{t('task.private')}</span>
                            <span className="text-muted-foreground text-xs">{t('task.privateHint')}</span>
                        </span>
                    </label>
                    <div className="grid gap-2">
                        <Label htmlFor="description">{t('task.description')}</Label>
                        <Input id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        <InputError message={errors.description} />
                    </div>
                </CollapsibleContent>
            </Collapsible>

            {/* Pinned to the bottom of the sheet, so you never have to scroll past the options to save. */}
            <DialogFooter className="bg-background sticky -bottom-[calc(env(safe-area-inset-bottom)+1.25rem)] -mx-5 px-5 pt-2 pb-[calc(env(safe-area-inset-bottom)+1.25rem)] sm:-bottom-6 sm:-mx-6 sm:px-6 sm:pb-6">
                <Button type="submit" disabled={processing}>
                    {isEdit ? t('common.save') : 'Aufgabe anlegen'}
                </Button>
            </DialogFooter>
        </form>
    );
}
