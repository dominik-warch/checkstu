import { router } from '@inertiajs/react';
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
 * raw-fetch + X-XSRF-TOKEN pattern as use-push-subscription.ts. Toggling a
 * single episode updates local state optimistically rather than reloading the
 * whole Inertia page — an acceptable tradeoff for snappy taps, since a single
 * episode rarely flips the header's overall watch status. Marking the whole
 * season, by contrast, goes through Inertia (router.post) like the show-level
 * "mark all watched" already does, so the header status and other seasons'
 * counts refresh immediately instead of needing a manual reload.
 */
export default function SeasonAccordion({ season }: SeasonAccordionProps) {
    const [open, setOpen] = useState(false);
    const [episodes, setEpisodes] = useState<MediaEpisodeDetail[] | null>(null);
    const [loading, setLoading] = useState(false);
    const [markingAll, setMarkingAll] = useState(false);

    function loadEpisodes() {
        return fetch(route('media.seasons.episodes', season.id), { headers: { Accept: 'application/json' } })
            .then((res) => res.json())
            .then((data: { episodes: MediaEpisodeDetail[] }) => setEpisodes(data.episodes));
    }

    useEffect(() => {
        if (!open || episodes !== null) {
            return;
        }

        setLoading(true);
        loadEpisodes().finally(() => setLoading(false));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, episodes, season.id]);

    function toggle(episode: MediaEpisodeDetail) {
        const nowWatched = episode.watches.length === 0;
        // A placeholder row (blank date, matching what the server actually creates)
        // gives instant checkbox feedback; loadEpisodes() reconciles real ids after.
        setEpisodes(
            (prev) =>
                prev?.map((e) => (e.id === episode.id ? { ...e, watches: nowWatched ? [{ id: -1, watched_at: null }] : [] } : e)) ?? prev,
        );

        fetch(route(nowWatched ? 'media.episodes.watch.store' : 'media.episodes.watch.destroy', episode.id), {
            method: nowWatched ? 'POST' : 'DELETE',
            headers: { 'Content-Type': 'application/json', ...xsrfHeader() },
            credentials: 'same-origin',
        }).then(loadEpisodes);
    }

    function addWatch(episode: MediaEpisodeDetail) {
        fetch(route('media.episodes.watch.store', episode.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', ...xsrfHeader() },
            credentials: 'same-origin',
        }).then(loadEpisodes);
    }

    function changeWatchDate(episode: MediaEpisodeDetail, watchId: number, date: string) {
        setEpisodes(
            (prev) =>
                prev?.map((e) =>
                    e.id === episode.id ? { ...e, watches: e.watches.map((w) => (w.id === watchId ? { ...w, watched_at: date || null } : w)) } : e,
                ) ?? prev,
        );

        fetch(route('media.episodeWatches.update', watchId), {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', ...xsrfHeader() },
            credentials: 'same-origin',
            body: JSON.stringify({ watched_at: date || null }),
        });
    }

    function removeWatch(episode: MediaEpisodeDetail, watchId: number) {
        setEpisodes(
            (prev) => prev?.map((e) => (e.id === episode.id ? { ...e, watches: e.watches.filter((w) => w.id !== watchId) } : e)) ?? prev,
        );

        fetch(route('media.episodeWatches.destroy', watchId), {
            method: 'DELETE',
            headers: xsrfHeader(),
            credentials: 'same-origin',
        });
    }

    function markSeasonWatched(e: MouseEvent) {
        e.stopPropagation();
        setMarkingAll(true);
        setEpisodes((prev) => prev?.map((ep) => (ep.watches.length > 0 ? ep : { ...ep, watches: [{ id: -1, watched_at: null }] })) ?? prev);

        router.post(
            route('media.seasons.watchAll', season.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    setMarkingAll(false);
                    loadEpisodes();
                },
            },
        );
    }

    const watchedCount = episodes ? episodes.filter((ep) => ep.watches.length > 0).length : season.watched_count;

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
                {episodes?.map((episode) => (
                    <EpisodeRow
                        key={episode.id}
                        episode={episode}
                        onToggle={() => toggle(episode)}
                        onAddWatch={() => addWatch(episode)}
                        onChangeWatchDate={(watchId, date) => changeWatchDate(episode, watchId, date)}
                        onRemoveWatch={(watchId) => removeWatch(episode, watchId)}
                    />
                ))}
            </CollapsibleContent>
        </Collapsible>
    );
}
