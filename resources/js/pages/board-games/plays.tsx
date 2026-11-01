import { Head } from '@inertiajs/react';

import LogPlayDialog from '@/components/board-games/log-play-dialog';
import PlayRow from '@/components/board-games/play-row';
import { cardGrid, EmptyState } from '@/components/page-header';
import CheckstuLayout, { boardGamesNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { BoardGamePlay, BoardGameSummary, Member } from '@/types/board-games';

interface PlaysProps {
    plays: BoardGamePlay[];
    games: BoardGameSummary[];
    members: Member[];
}

export default function Plays({ plays, games, members }: PlaysProps) {
    return (
        <CheckstuLayout navItems={boardGamesNavItems} context="boardgames">
            <Head title={t('boardGames.plays')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('boardGames.plays')}</h1>

            {plays.length === 0 && <EmptyState>{t('boardGames.emptyPlays')}</EmptyState>}

            <div className={cardGrid}>
                {plays.map((play) => (
                    <PlayRow key={play.id} play={play} games={games} members={members} />
                ))}
            </div>

            {games.length > 0 && <LogPlayDialog games={games} members={members} />}
        </CheckstuLayout>
    );
}
