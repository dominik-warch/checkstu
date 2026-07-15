import { Head, Link, router } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useState } from 'react';

import MediaPoster from '@/components/media/media-poster';
import { Button } from '@/components/ui/button';
import CheckstuLayout, { mediaNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { MediaNextEpisodeEntry } from '@/types/media';

interface HomeProps {
    nextEpisodes: MediaNextEpisodeEntry[];
}

export default function Home({ nextEpisodes }: HomeProps) {
    const [markingId, setMarkingId] = useState<number | null>(null);

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
                                    {entry.next_episode
                                        ? `${t('media.season', { number: entry.next_episode.season_number })} · ${t('media.episode', { number: entry.next_episode.episode_number })} · ${entry.next_episode.name}`
                                        : t('media.continueWatching')}
                                </p>
                            </div>
                        </Link>

                        {entry.next_episode && (
                            <Button
                                size="icon"
                                variant="outline"
                                disabled={markingId === entry.next_episode.id}
                                onClick={() => markWatched(entry.next_episode!.id)}
                                aria-label={t('media.markEpisodeWatched')}
                            >
                                <Check className="size-4" />
                            </Button>
                        )}
                    </div>
                ))}
            </div>
        </CheckstuLayout>
    );
}
