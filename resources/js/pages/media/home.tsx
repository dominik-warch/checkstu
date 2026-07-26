import { Head, Link, router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
import { Button } from '@/components/ui/button';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
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
    function markWatched(episodeId: number) {
        setMarkingId(episodeId);
        router.post(route('media.episodes.watch.store', episodeId), {}, { preserveScroll: true, onFinish: () => setMarkingId(null) });
    }

    return (
        <CheckstuLayout navItems={mediaNavItems} context="media">
            <Head title={t('media.home')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('media.nextEpisode')}</h1>

            <div className="mb-4 flex flex-wrap gap-2">
                {sortOptions.map((opt) => (
                    <Button
                        key={opt.value}
                        type="button"
                        size="sm"
                        variant={filters.sort === opt.value ? 'default' : 'outline'}
                        className={cn('flex-1')}
                        onClick={() => updateSort(opt.value)}
                    >
                        {opt.label}
                    </Button>
                ))}
            </div>

            {nextEpisodes.length === 0 && (
                <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">{t('media.nothingInProgress')}</div>
            )}

            <div className="flex flex-col gap-2">
                {nextEpisodes.map((entry) => (
                    <div key={entry.media_item.id} className="flex items-center gap-3 rounded-xl border p-3">
                        <Link
                            href={route('media.items.show', entry.media_item.id)}
                            className="hover:bg-muted/50 flex min-w-0 flex-1 items-center gap-3 rounded-lg"
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

                        <Button
                            size="icon"
                            variant="outline"
                            disabled={markingId === entry.next_episode.id}
                            onClick={() => markWatched(entry.next_episode.id)}
                            aria-label={t('media.markEpisodeWatched')}
                        >
                            <Check className="size-4" />
                        </Button>
                    </div>
                ))}
            </div>
        </CheckstuLayout>
    );
}
