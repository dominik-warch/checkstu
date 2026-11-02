import { Head, Link } from '@inertiajs/react';

import MediaPoster from '@/components/media/media-poster';
import { cardGrid, EmptyState } from '@/components/page-header';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { ComingUpItem } from '@/types/media';

interface ComingUpProps {
    items: ComingUpItem[];
}

function dayLabel(date: string): string {
    const d = new Date(date + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = Math.round((d.getTime() - today.getTime()) / 86_400_000);
    if (diff === 0) return 'Heute';
    if (diff === 1) return 'Morgen';
    return new Intl.DateTimeFormat('de-DE', { weekday: 'long', day: 'numeric', month: 'long' }).format(d);
}

function ComingUpRow({ item }: { item: ComingUpItem }) {
    if (item.kind === 'book') {
        return (
            <div className="bg-card flex items-center gap-3 rounded-2xl border-[1.5px] p-3">
                <MediaPoster path={item.book_item.thumbnail_url} alt={item.book_item.title} className="h-20 w-14" />
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{item.book_item.title}</p>
                    <p className="text-muted-foreground text-sm">{t('media.typeBook')}</p>
                </div>
            </div>
        );
    }

    const content = (
        <>
            <MediaPoster path={item.media_item.poster_path} alt={item.media_item.title_de} className="h-20 w-14" />
            <div className="min-w-0 flex-1">
                <p className="truncate font-medium">{item.media_item.title_de}</p>
                <p className="text-muted-foreground text-sm">
                    {item.episode
                        ? `${t('media.season', { number: item.episode.season_number })} · ${t('media.episode', { number: item.episode.episode_number })} · ${item.episode.name}`
                        : t('media.typeMovie')}
                </p>
            </div>
        </>
    );

    return item.media_item.type === 'tv' ? (
        <Link
            href={route('media.items.show', item.media_item.id)}
            className="bg-card hover:bg-muted/60 flex items-center gap-3 rounded-2xl border-[1.5px] p-3 transition-colors"
        >
            {content}
        </Link>
    ) : (
        <div className="bg-card flex items-center gap-3 rounded-2xl border-[1.5px] p-3">{content}</div>
    );
}

export default function ComingUp({ items }: ComingUpProps) {
    // Group by date (already sorted ascending server-side), same pattern as the task list.
    const groups = new Map<string, ComingUpItem[]>();
    for (const item of items) {
        const list = groups.get(item.date) ?? [];
        list.push(item);
        groups.set(item.date, list);
    }

    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={t('media.comingUp')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('media.comingUp')}</h1>

            {items.length === 0 && <EmptyState>{t('media.comingUpEmpty')}</EmptyState>}

            {[...groups.entries()].map(([date, list]) => (
                <section key={date} className="mb-6">
                    <h2 className="mb-2 text-sm font-bold">{dayLabel(date)}</h2>
                    <div className={cardGrid}>
                        {list.map((item) => (
                            <ComingUpRow key={item.kind === 'book' ? `book-${item.book_item.id}` : `media-${item.media_item.id}`} item={item} />
                        ))}
                    </div>
                </section>
            ))}
        </CheckstuLayout>
    );
}
