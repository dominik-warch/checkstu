import { Check, ChevronDown } from 'lucide-react';
import { useState } from 'react';

import { WatchDates } from '@/components/media/watch-history';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { MediaEpisodeDetail } from '@/types/media';

interface EpisodeRowProps {
    episode: MediaEpisodeDetail;
    onToggle: () => void;
    onAddWatch: () => void;
    onChangeWatchDate: (watchId: number, date: string) => void;
    onRemoveWatch: (watchId: number) => void;
}

function formatAirDate(date: string | null): string | null {
    if (!date) return null;
    return new Intl.DateTimeFormat('de-DE', { day: 'numeric', month: 'short', year: '2-digit' }).format(new Date(date + 'T00:00:00'));
}

/** One line per episode: tick, number, title, air date. Watch dates fold out on demand. */
export default function EpisodeRow({ episode, onToggle, onAddWatch, onChangeWatchDate, onRemoveWatch }: EpisodeRowProps) {
    const [open, setOpen] = useState(false);
    const watched = episode.watches.length > 0;
    const airDate = formatAirDate(episode.air_date);

    return (
        <div className={cn(open && 'bg-muted/50')}>
            <div className="flex min-h-13 items-center gap-1 pr-1 pl-2">
                <button
                    type="button"
                    onClick={onToggle}
                    aria-pressed={watched}
                    aria-label={`${t('media.episode', { number: episode.episode_number })}: ${watched ? t('media.unmarkEpisodeWatched') : t('media.markEpisodeWatched')}`}
                    className="grid size-11 shrink-0 place-items-center rounded-full"
                >
                    <span
                        className={cn(
                            'grid size-7 place-items-center rounded-full border-2 transition-colors duration-200',
                            watched ? 'bg-player text-player-ink border-player' : 'border-input',
                        )}
                    >
                        {watched && <Check className="animate-field-fill size-4" strokeWidth={3} />}
                    </span>
                </button>

                <span className="text-muted-foreground w-6 shrink-0 text-right text-sm tabular-nums">{episode.episode_number}</span>
                <span className={cn('min-w-0 flex-1 truncate pl-2 text-base', watched && 'text-muted-foreground')}>{episode.name}</span>

                {episode.watches.length > 1 && (
                    <span className="bg-muted shrink-0 rounded-full px-2 text-xs leading-5 font-bold tabular-nums">{episode.watches.length}×</span>
                )}
                {airDate && <time className="text-muted-foreground shrink-0 pl-1 text-sm tabular-nums">{airDate}</time>}

                {watched ? (
                    <button
                        type="button"
                        onClick={() => setOpen((o) => !o)}
                        aria-expanded={open}
                        aria-label={t('media.watchedAt')}
                        className="text-muted-foreground hover:text-foreground grid size-11 shrink-0 place-items-center rounded-full"
                    >
                        <ChevronDown className={cn('size-5 transition-transform duration-200', open && 'rotate-180')} />
                    </button>
                ) : (
                    <span className="size-11 shrink-0" aria-hidden />
                )}
            </div>

            {open && watched && (
                <div className="pr-3 pb-2 pl-[5.25rem]">
                    <WatchDates watches={episode.watches} onAdd={onAddWatch} onChangeDate={onChangeWatchDate} onRemove={onRemoveWatch} />
                </div>
            )}
        </div>
    );
}
