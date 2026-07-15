import { CheckCheck } from 'lucide-react';
import { MouseEvent, useEffect, useState } from 'react';

import EpisodeRow from '@/components/media/episode-row';
import { Button } from '@/components/ui/button';
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
 * episode (or marking the whole season) updates local state optimistically
 * rather than reloading the whole Inertia page — the header's overall watch
 * status only catches up on the next full page load, which is an acceptable
 * v1 tradeoff for snappy taps.
 */
export default function SeasonAccordion({ season }: SeasonAccordionProps) {
    const [open, setOpen] = useState(false);
    const [episodes, setEpisodes] = useState<MediaEpisodeDetail[] | null>(null);
    const [loading, setLoading] = useState(false);
    const [markingAll, setMarkingAll] = useState(false);

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

    function markSeasonWatched(e: MouseEvent) {
        e.stopPropagation();
        setMarkingAll(true);
        setEpisodes((prev) => prev?.map((ep) => (ep.watched ? ep : { ...ep, watched: true })) ?? prev);

        fetch(route('media.seasons.watchAll', season.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...xsrfHeader() },
            credentials: 'same-origin',
        }).finally(() => setMarkingAll(false));
    }

    const watchedCount = episodes ? episodes.filter((ep) => ep.watched).length : season.watched_count;

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="rounded-lg border">
            <div className="flex items-center justify-between gap-2 pr-2">
                <CollapsibleTrigger className="flex flex-1 items-center justify-between px-3 py-2 text-left">
                    <span className="font-medium">{season.name}</span>
                    <span className="text-muted-foreground text-sm">
                        {watchedCount}/{season.episode_count}
                    </span>
                </CollapsibleTrigger>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    disabled={markingAll}
                    onClick={markSeasonWatched}
                    aria-label={t('media.markSeasonWatched')}
                >
                    <CheckCheck className="size-4" />
                </Button>
            </div>
            <CollapsibleContent className="divide-y border-t">
                {loading && <p className="text-muted-foreground p-3 text-sm">{t('common.loading')}</p>}
                {episodes?.map((episode) => <EpisodeRow key={episode.id} episode={episode} onToggle={() => toggle(episode)} />)}
            </CollapsibleContent>
        </Collapsible>
    );
}
