import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import ConfirmDeleteButton from '@/components/confirm-delete-button';
import MediaPoster from '@/components/media/media-poster';
import SharedByList from '@/components/media/shared-by-list';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { BookItemDetail, WatchStatus } from '@/types/media';

interface BookShowProps {
    item: BookItemDetail;
}

const statusLabel: Record<WatchStatus, string> = {
    watchlist: t('media.watchlist'),
    watching: t('media.watching'),
    completed: t('media.completed'),
    dropped: t('media.dropped'),
};

export default function BookShow({ item }: BookShowProps) {
    const [processing, setProcessing] = useState(false);

    function toggleRead() {
        if (!item.entry) return;
        setProcessing(true);
        router.patch(
            route('books.entries.update', item.entry.id),
            { status: item.entry.status === 'completed' ? 'watchlist' : 'completed', read_at: null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    function changeDate(date: string) {
        if (!item.entry) return;
        router.patch(route('books.entries.update', item.entry.id), { status: 'completed', read_at: date || null }, { preserveScroll: true });
    }

    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={item.title} />

            <Link
                href={route('media.library')}
                className="text-muted-foreground hover:text-foreground mb-3 -ml-2 inline-flex min-h-11 items-center gap-1.5 rounded-full px-2 text-sm font-medium"
            >
                <ArrowLeft className="size-4" />
                {t('common.back')}
            </Link>

            <div className="flex gap-4">
                <MediaPoster path={item.thumbnail_url} alt={item.title} size="w185" className="h-44 w-30" />
                <div className="min-w-0 flex-1">
                    <h1 className="text-2xl font-bold tracking-tight">{item.title}</h1>
                    {item.authors && <p className="text-muted-foreground text-sm">{t('media.byAuthor', { authors: item.authors })}</p>}
                    {item.entry && (
                        <Badge variant="secondary" className="mt-2">
                            {statusLabel[item.entry.status]}
                        </Badge>
                    )}
                    {item.published_date && <p className="text-muted-foreground mt-2 text-sm">{item.published_date.slice(0, 4)}</p>}
                </div>
            </div>

            {item.overview && <p className="mt-4 text-sm">{item.overview}</p>}

            <SharedByList members={item.shared_by} />

            {item.entry && (
                <div className="mt-4 flex items-center gap-2">
                    <Button type="button" variant="outline" disabled={processing} onClick={toggleRead}>
                        {item.entry.status === 'completed' ? t('media.addToWatchlist') : t('media.markRead')}
                    </Button>
                    {item.entry.status === 'completed' && (
                        <input
                            type="date"
                            value={item.entry.read_at ?? ''}
                            onChange={(e) => changeDate(e.target.value)}
                            aria-label={t('media.readAt')}
                            className="border-input bg-card h-11 rounded-full border-[1.5px] px-4 text-base"
                        />
                    )}
                </div>
            )}

            {item.entry && (
                <ConfirmDeleteButton
                    label={t('media.removeFromLibrary')}
                    onConfirm={() => router.delete(route('books.entries.destroy', item.entry!.id), { preserveScroll: true })}
                    className="mt-8"
                />
            )}
        </CheckstuLayout>
    );
}
