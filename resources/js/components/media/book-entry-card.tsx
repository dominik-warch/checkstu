import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import type { BookEntrySummary, WatchStatus } from '@/types/media';

const statusLabel: Record<WatchStatus, string> = {
    watchlist: t('media.watchlist'),
    watching: t('media.watching'),
    completed: t('media.completed'),
};

interface BookEntryCardProps {
    entry: BookEntrySummary;
}

/** Books have no detail page (no sub-structure like episodes to view) — everything happens inline, same as movies. */
export default function BookEntryCard({ entry }: BookEntryCardProps) {
    const item = entry.book_item;
    const [processing, setProcessing] = useState(false);

    function toggleRead() {
        setProcessing(true);
        router.patch(
            route('books.entries.update', entry.id),
            { status: entry.status === 'completed' ? 'watchlist' : 'completed', read_at: null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    function changeDate(date: string) {
        router.patch(route('books.entries.update', entry.id), { status: 'completed', read_at: date || null }, { preserveScroll: true });
    }

    function remove() {
        if (!confirm(t('media.removeConfirm'))) return;
        router.delete(route('books.entries.destroy', entry.id), { preserveScroll: true });
    }

    return (
        <div className="flex items-center gap-3 rounded-xl border p-3">
            <div className="flex min-w-0 flex-1 items-center gap-3">
                <MediaPoster path={item.thumbnail_url} alt={item.title} className="h-20 w-14" />
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{item.title}</p>
                    {item.authors && <p className="text-muted-foreground truncate text-sm">{t('media.byAuthor', { authors: item.authors })}</p>}
                    <Badge variant="secondary" className="mt-1">
                        {statusLabel[entry.status]}
                    </Badge>
                </div>
            </div>

            <div className="flex shrink-0 flex-col items-end gap-1.5">
                <Button size="sm" variant="outline" disabled={processing} onClick={toggleRead}>
                    {entry.status === 'completed' ? t('media.addToWatchlist') : t('media.markRead')}
                </Button>
                {entry.status === 'completed' && (
                    <input
                        type="date"
                        value={entry.read_at ?? ''}
                        onChange={(e) => changeDate(e.target.value)}
                        aria-label={t('media.readAt')}
                        className="border-input bg-background rounded-md border px-2 py-1 text-xs"
                    />
                )}
                <Button size="icon" variant="ghost" aria-label={t('media.remove')} onClick={remove}>
                    <Trash2 className="size-4" />
                </Button>
            </div>
        </div>
    );
}
