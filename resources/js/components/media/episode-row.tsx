import { Checkbox } from '@/components/ui/checkbox';
import { t } from '@/lib/i18n';
import type { MediaEpisodeDetail } from '@/types/media';

interface EpisodeRowProps {
    episode: MediaEpisodeDetail;
    onToggle: () => void;
    onChangeDate: (date: string) => void;
}

export default function EpisodeRow({ episode, onToggle, onChangeDate }: EpisodeRowProps) {
    return (
        <div className="hover:bg-muted/50 flex items-center gap-3 px-3 py-2">
            <label className="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                <Checkbox
                    checked={episode.watched}
                    onCheckedChange={onToggle}
                    aria-label={episode.watched ? t('media.unmarkEpisodeWatched') : t('media.markEpisodeWatched')}
                />
                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm">
                        {t('media.episode', { number: episode.episode_number })} · {episode.name}
                    </p>
                    {episode.air_date && <p className="text-muted-foreground text-xs">{episode.air_date}</p>}
                </div>
            </label>
            {episode.watched && (
                <input
                    type="date"
                    value={episode.watched_at ?? ''}
                    onChange={(e) => onChangeDate(e.target.value)}
                    aria-label={t('media.watchedAt')}
                    className="border-input bg-background rounded-md border px-2 py-1 text-sm"
                />
            )}
        </div>
    );
}
