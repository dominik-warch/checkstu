import { router } from '@inertiajs/react';
import { Dices } from 'lucide-react';
import { useState } from 'react';

import { chipClass } from '@/components/chip-row';
import ConfirmDeleteButton from '@/components/confirm-delete-button';
import Pawn from '@/components/pawn';
import { Input } from '@/components/ui/input';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { BoardGame } from '@/types/board-games';

interface BoardGameCardProps {
    game: BoardGame;
}

const shortDate = new Intl.DateTimeFormat('de-DE', { day: 'numeric', month: 'short' });

/** One box on the shelf: its cover, how it plays, and who won the last time it came out. */
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
        game.min_players && game.max_players
            ? game.min_players === game.max_players
                ? `${game.min_players} Pers.`
                : `${game.min_players}–${game.max_players} Pers.`
            : null,
        game.playtime_minutes ? `${game.playtime_minutes} min` : null,
    ].filter(Boolean);

    const last = game.last_play;

    return (
        <div className="bg-card flex flex-col overflow-hidden rounded-2xl border-[1.5px]">
            {game.thumbnail_url ? (
                <img src={game.thumbnail_url} alt="" className="bg-muted aspect-square w-full object-cover" />
            ) : (
                <div className="bg-muted grid aspect-square w-full place-items-center">
                    <Dices className="text-muted-foreground size-10" />
                </div>
            )}

            <div className="flex flex-1 flex-col p-3">
                {draft === null ? (
                    // Tapping the name renames the box; it's the only edit a game has.
                    <button
                        type="button"
                        onClick={() => setDraft(game.name)}
                        aria-label={`${game.name} umbenennen`}
                        className="line-clamp-2 text-left text-base leading-snug font-bold [overflow-wrap:break-word] hyphens-auto underline-offset-4 hover:underline"
                    >
                        {game.name}
                    </button>
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
                        className="h-11"
                    />
                )}
                {meta.length > 0 && <p className="text-muted-foreground text-sm">{meta.join(' · ')}</p>}

                <p className="mt-2 flex min-w-0 items-center gap-1 text-sm">
                    {last ? (
                        <>
                            <span className="text-muted-foreground shrink-0">{shortDate.format(new Date(last.played_at + 'T00:00:00'))}</span>
                            {last.winners.map((w) => (
                                <span key={w.name} className="flex min-w-0 items-center gap-0.5 font-bold">
                                    <Pawn className="size-4 shrink-0" style={w.color ? { color: w.color } : undefined} />
                                    <span className="truncate">{w.name}</span>
                                </span>
                            ))}
                        </>
                    ) : (
                        <span className="text-muted-foreground">Noch nie gespielt</span>
                    )}
                </p>

                <div className="mt-auto flex flex-wrap items-center justify-between gap-1 pt-3">
                    <button type="button" onClick={toggleStatus} className={cn(chipClass(game.status === 'owned'), 'px-3')}>
                        {game.status === 'owned' ? t('boardGames.owned') : t('boardGames.wishlist')}
                    </button>
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
        </div>
    );
}
