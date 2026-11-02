import { Link } from '@inertiajs/react';

import MediaPoster from '@/components/media/media-poster';
import SharedByDots from '@/components/media/shared-by-dots';
import { t } from '@/lib/i18n';
import type { BookEntrySummary, WatchStatus } from '@/types/media';

const statusLabel: Record<WatchStatus, string> = {
    watchlist: t('media.watchlist'),
    watching: t('media.watching'),
    completed: t('media.completed'),
    dropped: t('media.dropped'),
};

interface BookEntryCardProps {
    entry: BookEntrySummary;
}

/** Marking read and its date live on the detail page (media/book-show.tsx) — this row is just an entry point (removing it lives there too). */
export default function BookEntryCard({ entry }: BookEntryCardProps) {
    const item = entry.book_item;

    const meta = item.authors;

    return (
        <div className="bg-card flex items-center rounded-2xl border-[1.5px] p-1.5">
            <Link href={route('books.items.show', item.id)} className="hover:bg-muted/60 flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1">
                <MediaPoster path={item.thumbnail_url} alt={item.title} className="h-14 w-10 rounded-md" />
                <div className="min-w-0 flex-1">
                    <p className="truncate text-base font-medium">{item.title}</p>
                    <p className="text-muted-foreground flex min-w-0 items-center gap-1.5 text-sm">
                        <span className="text-foreground shrink-0 font-bold">{statusLabel[entry.status]}</span>
                        {meta && <span className="truncate">· {meta}</span>}
                        <SharedByDots members={entry.shared_by} />
                    </p>
                </div>
            </Link>
        </div>
    );
}
