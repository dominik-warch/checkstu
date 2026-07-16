import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
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

            <Link href={route('media.library')} className="text-muted-foreground mb-4 inline-flex items-center gap-1 text-sm hover:underline">
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

            {item.entry && (
                <div className="mt-4 flex items-center gap-2">
                    <Button type="button" variant="outline" size="sm" disabled={processing} onClick={toggleRead}>
                        {item.entry.status === 'completed' ? t('media.addToWatchlist') : t('media.markRead')}
                    </Button>
                    {item.entry.status === 'completed' && (
                        <input
                            type="date"
                            value={item.entry.read_at ?? ''}
                            onChange={(e) => changeDate(e.target.value)}
                            aria-label={t('media.readAt')}
                            className="border-input bg-background rounded-md border px-2 py-1 text-sm"
                        />
                    )}
                </div>
            )}
        </CheckstuLayout>
    );
}
