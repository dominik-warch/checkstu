import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import MediaPoster from '@/components/media/media-poster';
import SeasonAccordion from '@/components/media/season-accordion';
import { Badge } from '@/components/ui/badge';
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

            <h2 className="mt-6 mb-2 font-semibold">{t('media.seasons')}</h2>
            <div className="flex flex-col gap-2">
                {item.seasons.map((season) => (
                    <SeasonAccordion key={season.id} season={season} />
                ))}
            </div>
        </CheckstuLayout>
    );
}
