import { Head, router } from '@inertiajs/react';

import BoardGameCard from '@/components/board-games/board-game-card';
import BoardGameSearchDialog from '@/components/board-games/board-game-search-dialog';
import { Button } from '@/components/ui/button';
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

            <div className="mb-4 flex gap-2">
                {options.map((opt) => (
                    <Button
                        key={opt.value}
                        type="button"
                        size="sm"
                        variant={filters.status === opt.value ? 'default' : 'outline'}
                        className="flex-1"
                        onClick={() => setStatus(opt.value)}
                    >
                        {opt.label}
                    </Button>
                ))}
            </div>

            <div className="flex flex-col gap-2">
                {games.length === 0 && (
                    <div className="text-muted-foreground rounded-xl border border-dashed p-8 text-center">{t('boardGames.emptyCatalogue')}</div>
                )}
                {games.map((game) => (
                    <BoardGameCard key={game.id} game={game} />
                ))}
            </div>

            <BoardGameSearchDialog />
        </CheckstuLayout>
    );
}
