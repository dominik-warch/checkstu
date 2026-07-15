import { useEffect, useState } from 'react';

import EpisodeRow from '@/components/media/episode-row';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { t } from '@/lib/i18n';
import { xsrfHeader } from '@/lib/xsrf';
import type { MediaEpisodeDetail, MediaSeasonSummary } from '@/types/media';

interface SeasonAccordionProps {
    season: MediaSeasonSummary;
}

/**
 * Lazy fetch-on-expand: episodes only load when a season is opened, same
 * raw-fetch + X-XSRF-TOKEN pattern as use-push-subscription.ts. Toggling an
 * episode updates local state optimistically rather than reloading the whole
 * Inertia page — the header's overall watch status only catches up on the
 * next full page load, which is an acceptable v1 tradeoff for snappy taps.
 */
export default function SeasonAccordion({ season }: SeasonAccordionProps) {
    const [open, setOpen] = useState(false);
    const [episodes, setEpisodes] = useState<MediaEpisodeDetail[] | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open || episodes !== null) {
            return;
        }

        setLoading(true);
        fetch(route('media.seasons.episodes', season.id), { headers: { Accept: 'application/json' } })
            .then((res) => res.json())
            .then((data: { episodes: MediaEpisodeDetail[] }) => setEpisodes(data.episodes))
            .finally(() => setLoading(false));
    }, [open, episodes, season.id]);

    function toggle(episode: MediaEpisodeDetail) {
        const nowWatched = !episode.watched;
        setEpisodes((prev) => prev?.map((e) => (e.id === episode.id ? { ...e, watched: nowWatched } : e)) ?? prev);

        fetch(route(nowWatched ? 'media.episodes.watch.store' : 'media.episodes.watch.destroy', episode.id), {
            method: nowWatched ? 'POST' : 'DELETE',
            headers: { 'Content-Type': 'application/json', ...xsrfHeader() },
            credentials: 'same-origin',
        });
    }

    const watchedCount = episodes ? episodes.filter((e) => e.watched).length : season.watched_count;

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="rounded-lg border">
            <CollapsibleTrigger className="flex w-full items-center justify-between px-3 py-2 text-left">
                <span className="font-medium">{season.name}</span>
                <span className="text-muted-foreground text-sm">
                    {watchedCount}/{season.episode_count}
                </span>
            </CollapsibleTrigger>
            <CollapsibleContent className="divide-y border-t">
                {loading && <p className="text-muted-foreground p-3 text-sm">{t('common.loading')}</p>}
                {episodes?.map((episode) => <EpisodeRow key={episode.id} episode={episode} onToggle={() => toggle(episode)} />)}
            </CollapsibleContent>
        </Collapsible>
    );
}
