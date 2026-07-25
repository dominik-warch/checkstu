import WatchHistory from '@/components/media/watch-history';
import { Checkbox } from '@/components/ui/checkbox';
import { t } from '@/lib/i18n';
import type { MediaEpisodeDetail } from '@/types/media';

interface EpisodeRowProps {
    episode: MediaEpisodeDetail;
    onToggle: () => void;
    onAddWatch: () => void;
    onChangeWatchDate: (watchId: number, date: string) => void;
    onRemoveWatch: (watchId: number) => void;
}

export default function EpisodeRow({ episode, onToggle, onAddWatch, onChangeWatchDate, onRemoveWatch }: EpisodeRowProps) {
    const watched = episode.watches.length > 0;

    return (
        <div className="hover:bg-muted/50 flex flex-col gap-1 px-3 py-2">
            <label className="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                <Checkbox
                    checked={watched}
                    onCheckedChange={onToggle}
                    aria-label={watched ? t('media.unmarkEpisodeWatched') : t('media.markEpisodeWatched')}
                />
                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm">
                        {t('media.episode', { number: episode.episode_number })} · {episode.name}
                    </p>
                    {episode.air_date && <p className="text-muted-foreground text-xs">{episode.air_date}</p>}
                </div>
            </label>
            {watched && (
                <div className="pl-9">
                    <WatchHistory watches={episode.watches} onAdd={onAddWatch} onChangeDate={onChangeWatchDate} onRemove={onRemoveWatch} />
                </div>
            )}
        </div>
    );
}
