import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import type { MediaWatchRecord } from '@/types/media';

interface WatchHistoryProps {
    watches: MediaWatchRecord[];
    onAdd: () => void;
    onChangeDate: (watchId: number, date: string) => void;
    onRemove: (watchId: number) => void;
}

/** Total watch count = every logged date, however many — first watch and rewatches are the same kind of row. */
export default function WatchHistory({ watches, onAdd, onChangeDate, onRemove }: WatchHistoryProps) {
    const [open, setOpen] = useState(false);

    return (
        <div className="flex flex-col gap-1">
            <div className="flex items-center gap-1">
                <button
                    type="button"
                    className="text-muted-foreground text-xs enabled:hover:underline"
                    onClick={() => setOpen((o) => !o)}
                    disabled={watches.length === 0}
                >
                    {t('media.watchedTimes', { count: watches.length })}
                </button>
                <Button type="button" size="icon" variant="ghost" className="size-5" onClick={onAdd} aria-label={t('media.logAnotherWatch')}>
                    <Plus className="size-3" />
                </Button>
            </div>
            {open && watches.length > 0 && (
                <div className="flex flex-col gap-1 pl-1">
                    {watches.map((watch) => (
                        <div key={watch.id} className="flex items-center gap-2">
                            <input
                                type="date"
                                value={watch.watched_at ?? ''}
                                onChange={(e) => onChangeDate(watch.id, e.target.value)}
                                aria-label={t('media.watchedAt')}
                                className="border-input bg-background rounded-md border px-2 py-1 text-xs"
                            />
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-5"
                                onClick={() => onRemove(watch.id)}
                                aria-label={t('media.remove')}
                            >
                                <Trash2 className="size-3" />
                            </Button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
