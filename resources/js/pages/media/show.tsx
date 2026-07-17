import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCheck } from 'lucide-react';
import { useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
import SeasonAccordion from '@/components/media/season-accordion';
import SharedByList from '@/components/media/shared-by-list';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { MediaItemDetail, WatchStatus } from '@/types/media';

interface ShowProps {
    item: MediaItemDetail;
}

const statusLabel: Record<WatchStatus, string> = {
    watchlist: t('media.watchlist'),
    watching: t('media.watching'),
    completed: t('media.completed'),
};

export default function Show({ item }: ShowProps) {
    const [processing, setProcessing] = useState(false);
    // Bumped after "mark all watched" succeeds, forcing every SeasonAccordion to remount —
    // any that were already expanded had their own stale locally-fetched episode list, which
    // a plain Inertia prop refresh (item.seasons[].watched_count) doesn't reach into.
    const [refreshKey, setRefreshKey] = useState(0);

    function markAllWatched() {
        setProcessing(true);
        router.post(
            route('media.items.watchAll', item.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setRefreshKey((k) => k + 1),
                onFinish: () => setProcessing(false),
            },
        );
    }

    function toggleWatched() {
        if (!item.entry) return;
        setProcessing(true);
        router.patch(
            route('media.entries.update', item.entry.id),
            { status: item.entry.status === 'completed' ? 'watchlist' : 'completed', watched_at: null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    function changeDate(date: string) {
        if (!item.entry) return;
        router.patch(route('media.entries.update', item.entry.id), { status: 'completed', watched_at: date || null }, { preserveScroll: true });
    }

    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={item.title_de} />

            <Link href={route('media.library')} className="text-muted-foreground mb-4 inline-flex items-center gap-1 text-sm hover:underline">
                <ArrowLeft className="size-4" />
                {t('common.back')}
            </Link>

            <div className="flex gap-4">
                <MediaPoster path={item.poster_path} alt={item.title_de} size="w185" className="h-44 w-30" />
                <div className="min-w-0 flex-1">
                    <h1 className="text-2xl font-bold tracking-tight">{item.title_de}</h1>
                    <p className="text-muted-foreground text-sm">{item.title_en}</p>
                    {item.entry && (
                        <Badge variant="secondary" className="mt-2">
                            {statusLabel[item.entry.status]}
                        </Badge>
                    )}
                    {item.release_date && <p className="text-muted-foreground mt-2 text-sm">{item.release_date.slice(0, 4)}</p>}
                </div>
            </div>

            {item.overview && <p className="mt-4 text-sm">{item.overview}</p>}

            <SharedByList members={item.shared_by} />

            {item.type === 'tv' && item.entry && (
                <Button type="button" variant="outline" size="sm" className="mt-4" disabled={processing} onClick={markAllWatched}>
                    <CheckCheck className="size-4" />
                    {t('media.markAllWatched')}
                </Button>
            )}

            {item.type === 'movie' && item.entry && (
                <div className="mt-4 flex items-center gap-2">
                    <Button type="button" variant="outline" size="sm" disabled={processing} onClick={toggleWatched}>
                        {item.entry.status === 'completed' ? t('media.addToWatchlist') : t('media.markWatched')}
                    </Button>
                    {item.entry.status === 'completed' && (
                        <input
                            type="date"
                            value={item.entry.watched_at ?? ''}
                            onChange={(e) => changeDate(e.target.value)}
                            aria-label={t('media.watchedAt')}
                            className="border-input bg-background rounded-md border px-2 py-1 text-sm"
                        />
                    )}
                </div>
            )}

            {item.type === 'tv' && (
                <>
                    <h2 className="mt-6 mb-2 font-semibold">{t('media.seasons')}</h2>
                    <div className="flex flex-col gap-2">
                        {item.seasons.map((season) => (
                            <SeasonAccordion key={`${season.id}-${refreshKey}`} season={season} />
                        ))}
                    </div>
                </>
            )}
        </CheckstuLayout>
    );
}
