import { router } from '@inertiajs/react';
import { Dices, Pencil } from 'lucide-react';

import LogPlayDialog from '@/components/board-games/log-play-dialog';
import ConfirmDeleteButton from '@/components/confirm-delete-button';
import Pawn from '@/components/pawn';
import { Button } from '@/components/ui/button';
import { contrastTextColor } from '@/lib/color-contrast';
import { t } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { BoardGamePlay, BoardGameSummary, Member } from '@/types/board-games';

interface PlayRowProps {
    play: BoardGamePlay;
    games: BoardGameSummary[];
    members: Member[];
}

const longDate = new Intl.DateTimeFormat('de-DE', { weekday: 'long', day: 'numeric', month: 'long' });

/** One evening at the table: the box, the date, everyone who played, and the winner's pawn in their colour. */
export default function PlayRow({ play, games, members }: PlayRowProps) {
    return (
        <div className="bg-card rounded-2xl border-[1.5px] p-3">
            <div className="flex items-start justify-between gap-3">
                {play.board_game.thumbnail_url ? (
                    <img src={play.board_game.thumbnail_url} alt="" className="bg-muted size-12 shrink-0 rounded-lg object-cover" />
                ) : (
                    <div className="bg-muted grid size-12 shrink-0 place-items-center rounded-lg">
                        <Dices className="text-muted-foreground size-5" />
                    </div>
                )}
                <div className="min-w-0 flex-1">
                    <p className="truncate text-base font-bold">{play.board_game.name}</p>
                    <p className="text-muted-foreground text-sm">{longDate.format(new Date(play.played_at + 'T00:00:00'))}</p>
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
                            <ConfirmDeleteButton
                                icon
                                label={t('boardGames.removePlay')}
                                onConfirm={() => router.delete(route('boardgames.plays.destroy', play.id), { preserveScroll: true })}
                            />
                        )}
                    </div>
                )}
            </div>

            <div className="mt-2 flex flex-wrap gap-1.5">
                {play.players.map((player) => (
                    <span
                        key={player.id}
                        className={cn(
                            'inline-flex items-center gap-1 rounded-full border-[1.5px] px-2.5 py-0.5 text-sm',
                            player.is_winner && 'font-bold',
                            player.is_winner && !player.color && 'bg-foreground text-background border-foreground',
                        )}
                        style={
                            player.is_winner && player.color
                                ? { backgroundColor: player.color, borderColor: player.color, color: contrastTextColor(player.color) }
                                : undefined
                        }
                    >
                        {player.is_winner && <Pawn className="size-4" />}
                        {player.name}
                        {player.is_winner && <span className="sr-only"> ({t('boardGames.winner')})</span>}
                    </span>
                ))}
            </div>

            {play.notes && <p className="text-muted-foreground mt-2 text-sm">{play.notes}</p>}
        </div>
    );
}
