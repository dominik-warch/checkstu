import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

import ChipRow from '@/components/chip-row';
import MediaPoster from '@/components/media/media-poster';
import { cardGrid, EmptyState } from '@/components/page-header';
import { completeRingClass, RingCheck } from '@/components/tasks/task-card';
import { showUndo } from '@/components/undo-toast';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { MediaHomeFilters, MediaHomeSort, MediaNextEpisodeEntry } from '@/types/media';

interface HomeProps {
    nextEpisodes: MediaNextEpisodeEntry[];
    filters: MediaHomeFilters;
}

export default function Home({ nextEpisodes, filters }: HomeProps) {
    const [markingId, setMarkingId] = useState<number | null>(null);

    const sortOptions: { value: MediaHomeSort; label: string }[] = [
        { value: 'last_watched', label: t('media.sortLastWatched') },
        { value: 'alphabetical', label: t('media.sortAlphabetical') },
        { value: 'fewest_remaining', label: t('media.sortFewestRemaining') },
    ];

    function updateSort(sort: MediaHomeSort) {
        router.get(route('media.home'), { sort }, { preserveState: true, preserveScroll: true, replace: true });
    }

    // A full round trip (not the optimistic local-toggle used in season-accordion.tsx) is
    // deliberate here: marking the shown episode watched can advance which episode is "next"
    // or drop the show off this list entirely (now completed) — both require the server's
    // recomputed state, not a client-side guess.
    function markWatched(episodeId: number, title: string, label: string) {
        setMarkingId(episodeId);
        router.post(
            route('media.episodes.watch.store', episodeId),
            {},
            {
                preserveScroll: true,
                onSuccess: () =>
                    showUndo({
                        title,
                        note: `${label} gesehen`,
                        undo: () => router.delete(route('media.episodes.watch.destroy', episodeId), { preserveScroll: true }),
                    }),
                onFinish: () => setMarkingId(null),
            },
        );
    }

    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={t('media.home')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('media.nextEpisode')}</h1>

            <ChipRow label="Sortierung" options={sortOptions} value={filters.sort} onChange={updateSort} className="mb-4" />

            {nextEpisodes.length === 0 && <EmptyState>{t('media.nothingInProgress')}</EmptyState>}

            <div className={cardGrid}>
                {nextEpisodes.map((entry) => (
                    <div key={entry.media_item.id} className="bg-card flex items-center gap-3 rounded-2xl border-[1.5px] p-3">
                        <Link
                            href={route('media.items.show', entry.media_item.id)}
                            className="hover:bg-muted/60 flex min-w-0 flex-1 items-center gap-3 rounded-xl"
                        >
                            <MediaPoster path={entry.media_item.poster_path} alt={entry.media_item.title_de} className="h-20 w-14" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">{entry.media_item.title_de}</p>
                                <p className="text-muted-foreground text-sm">
                                    {t('media.season', { number: entry.next_episode.season_number })} ·{' '}
                                    {t('media.episode', { number: entry.next_episode.episode_number })} · {entry.next_episode.name}
                                </p>
                            </div>
                        </Link>

                        <button
                            type="button"
                            disabled={markingId === entry.next_episode.id}
                            onClick={() =>
                                markWatched(
                                    entry.next_episode.id,
                                    entry.media_item.title_de,
                                    `S${entry.next_episode.season_number} F${entry.next_episode.episode_number}`,
                                )
                            }
                            aria-label={`${entry.media_item.title_de}, ${t('media.season', { number: entry.next_episode.season_number })} ${t('media.episode', { number: entry.next_episode.episode_number })}: ${t('media.markEpisodeWatched')}`}
                            className={completeRingClass(false)}
                        >
                            <RingCheck />
                        </button>
                    </div>
                ))}
            </div>
        </CheckstuLayout>
    );
}
