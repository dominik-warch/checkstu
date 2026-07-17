import { t } from '@/lib/i18n';
import type { SharedByEntry, WatchStatus } from '@/types/media';

const statusLabel: Record<WatchStatus, string> = {
    watchlist: t('media.watchlist'),
    watching: t('media.watching'),
    completed: t('media.completed'),
};

interface SharedByListProps {
    members: SharedByEntry[];
}

/** Detail-page section naming which other household members have this item, and their status. */
export default function SharedByList({ members }: SharedByListProps) {
    if (members.length === 0) return null;

    return (
        <div className="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            <span className="text-muted-foreground">{t('media.sharedWith')}</span>
            {members.map((member) => (
                <span key={member.id} className="inline-flex items-center gap-1.5">
                    <span className="size-2 rounded-full" style={{ background: member.color ?? '#999' }} />
                    {member.name} · {statusLabel[member.status]}
                </span>
            ))}
        </div>
    );
}
