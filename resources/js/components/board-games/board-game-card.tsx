import { router } from '@inertiajs/react';
import { Dices, Pencil } from 'lucide-react';
import { useState } from 'react';

import ConfirmDeleteButton from '@/components/confirm-delete-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import type { BoardGame } from '@/types/board-games';

interface BoardGameCardProps {
    game: BoardGame;
}

export default function BoardGameCard({ game }: BoardGameCardProps) {
    const [draft, setDraft] = useState<string | null>(null);

    function saveName() {
        const name = draft?.trim();
        setDraft(null);
        if (name && name !== game.name) {
            router.patch(route('boardgames.update', game.id), { name }, { preserveScroll: true });
        }
    }

    function toggleStatus() {
        const next = game.status === 'owned' ? 'wishlist' : 'owned';
        router.patch(route('boardgames.update', game.id), { status: next }, { preserveScroll: true });
    }

    const meta = [
        game.year_published,
        game.min_players && game.max_players ? `${game.min_players}–${game.max_players}` : null,
        game.playtime_minutes ? `${game.playtime_minutes} min` : null,
    ].filter(Boolean);

    return (
        <div className="bg-card flex items-center gap-3 rounded-2xl border-[1.5px] p-3">
            {game.thumbnail_url ? (
                <img src={game.thumbnail_url} alt={game.name} className="h-16 w-16 shrink-0 rounded-xl object-cover" />
            ) : (
                <div className="bg-muted flex h-16 w-16 shrink-0 items-center justify-center rounded-xl">
                    <Dices className="text-muted-foreground size-6" />
                </div>
            )}

            <div className="min-w-0 flex-1">
                {draft === null ? (
                    <p className="truncate font-medium">{game.name}</p>
                ) : (
                    <Input
                        value={draft}
                        autoFocus
                        aria-label={t('common.edit')}
                        onChange={(e) => setDraft(e.target.value)}
                        onBlur={saveName}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') saveName();
                            if (e.key === 'Escape') setDraft(null);
                        }}
                        className="h-8"
                    />
                )}
                {meta.length > 0 && <p className="text-muted-foreground text-sm">{meta.join(' · ')}</p>}
                <button type="button" onClick={toggleStatus} className="mt-1">
                    <Badge variant="secondary">{game.status === 'owned' ? t('boardGames.owned') : t('boardGames.wishlist')}</Badge>
                </button>
            </div>

            <div className="flex shrink-0 items-center gap-1">
                <Button size="icon" variant="ghost" aria-label={t('common.edit')} onClick={() => setDraft(game.name)}>
                    <Pencil className="size-4" />
                </Button>
                {game.can.delete && (
                    <ConfirmDeleteButton
                        icon
                        label={t('boardGames.removeGame')}
                        confirmLabel="Mit Partien?"
                        onConfirm={() => router.delete(route('boardgames.destroy', game.id), { preserveScroll: true })}
                    />
                )}
            </div>
        </div>
    );
}
