import { Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import type { MediaEntrySummary, WatchStatus } from '@/types/media';

const statusLabel: Record<WatchStatus, string> = {
    watchlist: t('media.watchlist'),
    watching: t('media.watching'),
    completed: t('media.completed'),
};

interface MediaEntryCardProps {
    entry: MediaEntrySummary;
}

export default function MediaEntryCard({ entry }: MediaEntryCardProps) {
    const item = entry.media_item;
    const isMovie = item.type === 'movie';
    const [processing, setProcessing] = useState(false);

    function toggleWatched() {
        setProcessing(true);
        router.patch(
            route('media.entries.update', entry.id),
            { status: entry.status === 'completed' ? 'watchlist' : 'completed', watched_at: null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    function changeDate(date: string) {
        router.patch(route('media.entries.update', entry.id), { status: 'completed', watched_at: date || null }, { preserveScroll: true });
    }

    function remove() {
        if (!confirm(t('media.removeConfirm'))) return;
        router.delete(route('media.entries.destroy', entry.id), { preserveScroll: true });
    }

    const info = (
        <>
            <MediaPoster path={item.poster_path} alt={item.title_de} className="h-20 w-14" />
            <div className="min-w-0 flex-1">
                <p className="truncate font-medium">{item.title_de}</p>
                <p className="text-muted-foreground truncate text-sm">{item.title_en}</p>
                <Badge variant="secondary" className="mt-1">
                    {statusLabel[entry.status]}
                </Badge>
            </div>
        </>
    );

    return (
        <div className="flex items-center gap-3 rounded-xl border p-3">
            {item.type === 'tv' ? (
                <Link href={route('media.items.show', item.id)} className="flex min-w-0 flex-1 items-center gap-3">
                    {info}
                </Link>
            ) : (
                <div className="flex min-w-0 flex-1 items-center gap-3">{info}</div>
            )}

            <div className="flex shrink-0 flex-col items-end gap-1.5">
                {isMovie && (
                    <>
                        <Button size="sm" variant="outline" disabled={processing} onClick={toggleWatched}>
                            {entry.status === 'completed' ? t('media.addToWatchlist') : t('media.markWatched')}
                        </Button>
                        {entry.status === 'completed' && (
                            <input
                                type="date"
                                value={entry.watched_at ?? ''}
                                onChange={(e) => changeDate(e.target.value)}
                                aria-label={t('media.watchedAt')}
                                className="border-input bg-background rounded-md border px-2 py-1 text-xs"
                            />
                        )}
                    </>
                )}
                <Button size="icon" variant="ghost" aria-label={t('media.remove')} onClick={remove}>
                    <Trash2 className="size-4" />
                </Button>
            </div>
        </div>
    );
}
