import { router } from '@inertiajs/react';
import { Lock, Repeat, RotateCcw } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { contrastTextColor } from '@/lib/color-contrast';
import { useSwipeToComplete } from '@/hooks/use-swipe-to-complete';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Occurrence } from '@/types/checkstu';

const priorityLabel = ['priority.low', 'priority.normal', 'priority.high', 'priority.urgent'] as const;
const priorityClass = [
    'bg-muted text-muted-foreground',
    'bg-muted text-muted-foreground',
    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
];

function formatCompletedAt(iso: string | null): string {
    if (!iso) return '';
    const date = new Date(iso);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dateOnly = new Date(date);
    dateOnly.setHours(0, 0, 0, 0);
    const diff = Math.round((dateOnly.getTime() - today.getTime()) / 86_400_000);
    const time = new Intl.DateTimeFormat('de-DE', { hour: '2-digit', minute: '2-digit' }).format(date);
    if (diff === 0) return `Heute, ${time}`;
    if (diff === -1) return `Gestern, ${time}`;
    return new Intl.DateTimeFormat('de-DE', { day: '2-digit', month: 'short' }).format(date);
}

interface ArchiveCardProps {
    occurrence: Occurrence;
}

export default function ArchiveCard({ occurrence }: ArchiveCardProps) {
    const [processing, setProcessing] = useState(false);

    const restore = () => {
        setProcessing(true);
        router.delete(route('occurrences.restore', occurrence.id), {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    };

    const swipe = useSwipeToComplete(restore, !processing);
    const reached = swipe.dx >= 96;

    const color = occurrence.completed_by
        ? (occurrence.assignee?.color ?? null)
        : null;
    const textColor = color ? contrastTextColor(color) : null;
    const mutedStyle = textColor ? { color: textColor, opacity: 0.75 } : undefined;

    return (
        <div className="relative overflow-hidden rounded-xl">
            <div
                className={cn(
                    'absolute inset-0 flex items-center gap-2 pl-4 text-white transition-colors',
                    reached ? 'bg-amber-600' : 'bg-amber-500/70',
                )}
                aria-hidden
            >
                <RotateCcw className="size-5" />
                {reached && <span className="text-sm font-medium">{t('archive.restore')}</span>}
            </div>

            <div
                className={cn('flex items-center gap-3 border p-3', !color && 'bg-background')}
                style={{
                    transform: `translateX(${swipe.dx}px)`,
                    transition: swipe.animating ? 'transform 200ms ease-out' : undefined,
                    touchAction: 'pan-y',
                    ...(color ? { backgroundColor: color, color: textColor ?? undefined } : {}),
                }}
                {...swipe.handlers}
            >
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <span className="truncate font-medium line-through">{occurrence.title}</span>
                        <Badge variant="secondary" className={cn('shrink-0', priorityClass[occurrence.priority])}>
                            {t(priorityLabel[occurrence.priority])}
                        </Badge>
                        {occurrence.is_private && (
                            <Lock className={cn('size-3.5 shrink-0', !color && 'text-muted-foreground')} style={mutedStyle} aria-label={t('task.private')} />
                        )}
                        {occurrence.is_recurring && (
                            <Repeat className={cn('size-3.5 shrink-0', !color && 'text-muted-foreground')} style={mutedStyle} aria-label={t('task.recurring')} />
                        )}
                    </div>

                    <div className={cn('mt-1 text-sm', !color && 'text-muted-foreground')} style={mutedStyle}>
                        {occurrence.completed_by
                            ? t('archive.completedBy', { name: occurrence.completed_by.name })
                            : null}
                        {' · '}
                        {formatCompletedAt(occurrence.completed_at)}
                    </div>
                </div>
            </div>
        </div>
    );
}
