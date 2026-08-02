import { router } from '@inertiajs/react';
import { Dices, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import type { BoardGame } from '@/types/board-games';

interface BoardGameCardProps {
    game: BoardGame;
}

export default function BoardGameCard({ game }: BoardGameCardProps) {
    function toggleStatus() {
        const next = game.status === 'owned' ? 'wishlist' : 'owned';
        router.patch(route('boardgames.update', game.id), { status: next }, { preserveScroll: true });
    }

    function remove() {
        if (!confirm(t('boardGames.removeGameConfirm'))) return;
        router.delete(route('boardgames.destroy', game.id), { preserveScroll: true });
    }

    const meta = [
        game.year_published,
        game.min_players && game.max_players ? `${game.min_players}–${game.max_players}` : null,
        game.playtime_minutes ? `${game.playtime_minutes} min` : null,
    ].filter(Boolean);

    return (
        <div className="flex items-center gap-3 rounded-xl border p-3">
            {game.thumbnail_url ? (
                <img src={game.thumbnail_url} alt={game.name} className="h-16 w-16 shrink-0 rounded-lg object-cover" />
            ) : (
                <div className="bg-muted flex h-16 w-16 shrink-0 items-center justify-center rounded-lg">
                    <Dices className="text-muted-foreground size-6" />
                </div>
            )}

            <div className="min-w-0 flex-1">
                <p className="truncate font-medium">{game.name}</p>
                {meta.length > 0 && <p className="text-muted-foreground text-sm">{meta.join(' · ')}</p>}
                <button type="button" onClick={toggleStatus} className="mt-1">
                    <Badge variant="secondary">{game.status === 'owned' ? t('boardGames.owned') : t('boardGames.wishlist')}</Badge>
                </button>
            </div>

            {game.can.delete && (
                <Button size="icon" variant="ghost" aria-label={t('boardGames.removeGame')} onClick={remove}>
                    <Trash2 className="size-4" />
                </Button>
            )}
        </div>
    );
}
