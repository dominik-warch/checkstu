import { Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

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

/** Marking read and its date live on the detail page (media/book-show.tsx) — this row is just an entry point + quick remove. */
export default function BookEntryCard({ entry }: BookEntryCardProps) {
    const item = entry.book_item;

    function remove() {
        if (!confirm(t('media.removeConfirm'))) return;
        router.delete(route('books.entries.destroy', entry.id), { preserveScroll: true });
    }

    return (
        <div className="flex items-center gap-3 rounded-xl border p-3">
            <Link href={route('books.items.show', item.id)} className="flex min-w-0 flex-1 items-center gap-3">
                <MediaPoster path={item.thumbnail_url} alt={item.title} className="h-20 w-14" />
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{item.title}</p>
                    {item.authors && <p className="text-muted-foreground truncate text-sm">{t('media.byAuthor', { authors: item.authors })}</p>}
                    <Badge variant="secondary" className="mt-1">
                        {statusLabel[entry.status]}
                    </Badge>
                </div>
            </Link>

            <Button size="icon" variant="ghost" aria-label={t('media.remove')} onClick={remove}>
                <Trash2 className="size-4" />
            </Button>
        </div>
    );
}
