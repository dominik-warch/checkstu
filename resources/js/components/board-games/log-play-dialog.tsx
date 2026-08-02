import { useForm } from '@inertiajs/react';
import { Plus, Trophy, UserPlus, X } from 'lucide-react';
import { ReactNode, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { t } from '@/lib/i18n';
import type { BoardGamePlay, BoardGameSummary, Member } from '@/types/board-games';

interface PlayerRow {
    // Index signature so an array of these satisfies Inertia's FormDataConvertible.
    [key: string]: number | string | boolean | null;
    user_id: number | null;
    guest_name: string;
    is_winner: boolean;
}

interface LogPlayDialogProps {
    games: BoardGameSummary[];
    members: Member[];
    play?: BoardGamePlay; // present => edit mode
    trigger?: ReactNode; // edit mode supplies its own trigger (e.g. an icon button on the row)
}

/**
 * Handles both create and edit — a play's player roster is small and edited
 * as a whole (mirrors UpdateBoardGamePlayAction replacing the roster
 * wholesale server-side), so one dialog covers both flows.
 */
export default function LogPlayDialog({ games, members, play, trigger }: LogPlayDialogProps) {
    const isEdit = Boolean(play);
    const [open, setOpen] = useState(false);

    const form = useForm({
        board_game_id: play?.board_game.id ?? games[0]?.id ?? null,
        played_at: play?.played_at ?? new Date().toISOString().slice(0, 10),
        notes: play?.notes ?? '',
        players: (play?.players.map((p) => ({ user_id: p.user_id, guest_name: p.user_id ? '' : p.name, is_winner: p.is_winner })) ??
            []) as PlayerRow[],
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                if (!isEdit) {
                    form.reset();
                }
            },
        };

        if (isEdit && play) {
            form.patch(route('boardgames.plays.update', play.id), options);
        } else {
            form.post(route('boardgames.plays.store'), options);
        }
    }

    function addMember(userId: number) {
        form.setData('players', [...form.data.players, { user_id: userId, guest_name: '', is_winner: false }]);
    }

    function addGuest() {
        form.setData('players', [...form.data.players, { user_id: null, guest_name: '', is_winner: false }]);
    }

    function updatePlayer(index: number, patch: Partial<PlayerRow>) {
        form.setData(
            'players',
            form.data.players.map((p, i) => (i === index ? ({ ...p, ...patch } as PlayerRow) : p)),
        );
    }

    function removePlayer(index: number) {
        form.setData(
            'players',
            form.data.players.filter((_, i) => i !== index),
        );
    }

    const pickedUserIds = form.data.players.map((p) => p.user_id).filter((id): id is number => id !== null);
    const availableMembers = members.filter((m) => !pickedUserIds.includes(m.id));

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (!next) form.clearErrors();
            }}
        >
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button
                        size="icon"
                        className="fixed right-4 bottom-[calc(env(safe-area-inset-bottom)+5rem)] z-20 size-14 rounded-full shadow-lg"
                        aria-label={t('boardGames.logPlay')}
                    >
                        <Plus className="size-6" />
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{isEdit ? t('boardGames.editPlay') : t('boardGames.logPlay')}</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="grid gap-2">
                        <Label>{t('boardGames.game')}</Label>
                        <Select
                            value={form.data.board_game_id ? String(form.data.board_game_id) : ''}
                            onValueChange={(v) => form.setData('board_game_id', Number(v))}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {games.map((g) => (
                                    <SelectItem key={g.id} value={String(g.id)}>
                                        {g.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.board_game_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="played_at">{t('boardGames.playedAt')}</Label>
                        <Input id="played_at" type="date" value={form.data.played_at} onChange={(e) => form.setData('played_at', e.target.value)} />
                        <InputError message={form.errors.played_at} />
                    </div>

                    <div className="grid gap-2">
                        <Label>{t('boardGames.players')}</Label>
                        <div className="flex flex-col gap-2">
                            {form.data.players.map((player, index) => (
                                <div key={index} className="flex items-center gap-2 rounded-lg border p-2">
                                    <button
                                        type="button"
                                        onClick={() => updatePlayer(index, { is_winner: !player.is_winner })}
                                        aria-label={t('boardGames.winner')}
                                        aria-pressed={player.is_winner}
                                    >
                                        <Trophy className={player.is_winner ? 'size-5 text-amber-500' : 'text-muted-foreground size-5'} />
                                    </button>

                                    {player.user_id !== null ? (
                                        <span className="flex-1 text-sm font-medium">{members.find((m) => m.id === player.user_id)?.name ?? '—'}</span>
                                    ) : (
                                        <Input
                                            className="flex-1"
                                            placeholder={t('boardGames.guestName')}
                                            value={player.guest_name}
                                            onChange={(e) => updatePlayer(index, { guest_name: e.target.value })}
                                        />
                                    )}

                                    <button type="button" onClick={() => removePlayer(index)} aria-label={t('common.delete')}>
                                        <X className="text-muted-foreground size-4" />
                                    </button>
                                </div>
                            ))}
                        </div>
                        <InputError message={form.errors.players} />

                        <div className="mt-1 flex flex-wrap gap-2">
                            {availableMembers.map((m) => (
                                <Button key={m.id} type="button" size="sm" variant="outline" onClick={() => addMember(m.id)}>
                                    <UserPlus className="size-3.5" />
                                    {m.name}
                                </Button>
                            ))}
                            <Button type="button" size="sm" variant="outline" onClick={addGuest}>
                                <UserPlus className="size-3.5" />
                                {t('boardGames.addGuest')}
                            </Button>
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">{t('boardGames.notes')}</Label>
                        <Input id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {t('common.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
