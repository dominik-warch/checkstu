import { ChevronDown, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { MediaWatchRecord } from '@/types/media';

interface WatchDatesProps {
    watches: MediaWatchRecord[];
    onAdd: () => void;
    onChangeDate: (watchId: number, date: string) => void;
    onRemove: (watchId: number) => void;
}

/** Every logged watch as an editable date, plus "watched again". First watch and rewatches are the same kind of row. */
export function WatchDates({ watches, onAdd, onChangeDate, onRemove }: WatchDatesProps) {
    return (
        <div className="flex flex-col gap-1">
            {watches.map((watch) => (
                <div key={watch.id} className="flex items-center gap-1">
                    <input
                        type="date"
                        value={watch.watched_at ?? ''}
                        onChange={(e) => onChangeDate(watch.id, e.target.value)}
                        aria-label={t('media.watchedAt')}
                        className="border-input bg-card h-10 rounded-lg border-[1.5px] px-3 text-base"
                    />
                    <button
                        type="button"
                        onClick={() => onRemove(watch.id)}
                        aria-label={t('media.remove')}
                        className="text-muted-foreground hover:text-foreground hover:bg-muted grid size-11 place-items-center rounded-full"
                    >
                        <Trash2 className="size-5" />
                    </button>
                </div>
            ))}
            <button
                type="button"
                onClick={onAdd}
                className="hover:bg-muted -ml-2 inline-flex min-h-11 items-center gap-1.5 self-start rounded-full px-2 text-sm font-bold"
            >
                <Plus className="size-4" strokeWidth={3} />
                {t('media.logAnotherWatch')}
            </button>
        </div>
    );
}

/** Collapsed by default: "2× gesehen" opens the dates. */
export default function WatchHistory(props: WatchDatesProps) {
    const [open, setOpen] = useState(false);

    return (
        <div className="flex flex-col gap-1">
            <button
                type="button"
                className="text-muted-foreground hover:text-foreground -ml-2 inline-flex min-h-11 items-center gap-1 self-start rounded-full px-2 text-sm font-medium"
                onClick={() => setOpen((o) => !o)}
                aria-expanded={open}
            >
                {t('media.watchedTimes', { count: props.watches.length })}
                <ChevronDown className={cn('size-4 transition-transform duration-200', open && 'rotate-180')} />
            </button>
            {open && <WatchDates {...props} />}
        </div>
    );
}
