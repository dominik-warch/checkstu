import { Checkbox } from '@/components/ui/checkbox';
import { t } from '@/lib/i18n';
import type { MediaEpisodeDetail } from '@/types/media';

interface EpisodeRowProps {
    episode: MediaEpisodeDetail;
    onToggle: () => void;
}

export default function EpisodeRow({ episode, onToggle }: EpisodeRowProps) {
    return (
        <label className="hover:bg-muted/50 flex cursor-pointer items-center gap-3 px-3 py-2">
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
    );
}
