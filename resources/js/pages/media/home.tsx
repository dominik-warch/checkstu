import { Head, Link } from '@inertiajs/react';

import MediaPoster from '@/components/media/media-poster';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { MediaNextEpisodeEntry } from '@/types/media';

interface HomeProps {
    nextEpisodes: MediaNextEpisodeEntry[];
}

export default function Home({ nextEpisodes }: HomeProps) {
    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={t('media.home')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('media.nextEpisode')}</h1>

            {nextEpisodes.length === 0 && (
                <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">{t('media.nothingInProgress')}</div>
            )}

            <div className="flex flex-col gap-2">
                {nextEpisodes.map((entry) => (
                    <Link
                        key={entry.media_item.id}
                        href={route('media.items.show', entry.media_item.id)}
                        className="hover:bg-muted/50 flex items-center gap-3 rounded-xl border p-3"
                    >
                        <MediaPoster path={entry.media_item.poster_path} alt={entry.media_item.title_de} className="h-20 w-14" />
                        <div className="min-w-0 flex-1">
                            <p className="truncate font-medium">{entry.media_item.title_de}</p>
                            <p className="text-muted-foreground text-sm">
                                {entry.next_episode
                                    ? `${t('media.season', { number: entry.next_episode.season_number })} · ${t('media.episode', { number: entry.next_episode.episode_number })} · ${entry.next_episode.name}`
                                    : t('media.continueWatching')}
                            </p>
                        </div>
                    </Link>
                ))}
            </div>
        </CheckstuLayout>
    );
}
