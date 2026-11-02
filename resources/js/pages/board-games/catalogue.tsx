import { Head, router } from '@inertiajs/react';

import BoardGameCard from '@/components/board-games/board-game-card';
import BoardGameSearchDialog from '@/components/board-games/board-game-search-dialog';
import ChipRow from '@/components/chip-row';
import { EmptyState } from '@/components/page-header';
import CheckstuLayout, { boardGamesNavItems } from '@/layouts/checkstu-layout';
import { t } from '@/lib/i18n';
import type { BoardGame, BoardGameCatalogueFilters } from '@/types/board-games';

interface CatalogueProps {
    games: BoardGame[];
    filters: BoardGameCatalogueFilters;
}

export default function Catalogue({ games, filters }: CatalogueProps) {
    function setStatus(status: BoardGameCatalogueFilters['status']) {
        router.get(route('boardgames.catalogue'), { status }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const options: { value: BoardGameCatalogueFilters['status']; label: string }[] = [
        { value: 'all', label: t('common.all') },
        { value: 'owned', label: t('boardGames.owned') },
        { value: 'wishlist', label: t('boardGames.wishlist') },
    ];

    return (
        <CheckstuLayout navItems={boardGamesNavItems} context="boardgames">
            <Head title={t('boardGames.catalogue')} />

            <h1 className="mb-4 text-2xl font-bold tracking-tight">{t('boardGames.catalogue')}</h1>

            <ChipRow label="Status" options={options} value={filters.status} onChange={setStatus} className="mb-4" />

            {games.length === 0 && <EmptyState>{t('boardGames.emptyCatalogue')}</EmptyState>}

            {/* The shelf: box covers side by side, more of them as the screen widens. */}
            <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 lg:gap-3 xl:grid-cols-5">
                {games.map((game) => (
                    <BoardGameCard key={game.id} game={game} />
                ))}
            </div>

            <BoardGameSearchDialog />
        </CheckstuLayout>
    );
}
