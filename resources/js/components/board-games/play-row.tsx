import { router } from '@inertiajs/react';
import { Pencil, Trash2, Trophy } from 'lucide-react';

import LogPlayDialog from '@/components/board-games/log-play-dialog';
import { Button } from '@/components/ui/button';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { BoardGamePlay, BoardGameSummary, Member } from '@/types/board-games';

interface PlayRowProps {
    play: BoardGamePlay;
    games: BoardGameSummary[];
    members: Member[];
}

export default function PlayRow({ play, games, members }: PlayRowProps) {
    function remove() {
        if (!confirm(t('boardGames.removePlayConfirm'))) return;
        router.delete(route('boardgames.plays.destroy', play.id), { preserveScroll: true });
    }

    return (
        <div className="rounded-xl border p-3">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{play.board_game.name}</p>
                    <p className="text-muted-foreground text-sm">{play.played_at}</p>
                </div>

                {(play.can.update || play.can.delete) && (
                    <div className="flex shrink-0 gap-1">
                        {play.can.update && (
                            <LogPlayDialog
                                games={games}
                                members={members}
                                play={play}
                                trigger={
                                    <Button size="icon" variant="ghost" aria-label={t('boardGames.editPlay')}>
                                        <Pencil className="size-4" />
                                    </Button>
                                }
                            />
                        )}
                        {play.can.delete && (
                            <Button size="icon" variant="ghost" aria-label={t('boardGames.removePlay')} onClick={remove}>
                                <Trash2 className="size-4" />
                            </Button>
                        )}
                    </div>
                )}
            </div>

            <div className="mt-2 flex flex-wrap gap-1.5">
                {play.players.map((player) => (
                    <span
                        key={player.id}
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs',
                            player.is_winner && 'border-amber-500/50 bg-amber-500/10 font-medium text-amber-700 dark:text-amber-400',
                        )}
                    >
                        {player.is_winner && <Trophy className="size-3" />}
                        {player.name}
                    </span>
                ))}
            </div>

            {play.notes && <p className="text-muted-foreground mt-2 text-sm">{play.notes}</p>}
        </div>
    );
}
