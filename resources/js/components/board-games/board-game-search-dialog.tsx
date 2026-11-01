import { Plus } from 'lucide-react';
import { useState } from 'react';

import BoardGameSearch from '@/components/board-games/board-game-search';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { t } from '@/lib/i18n';

/**
 * Thin Dialog wrapper around BoardGameSearch — mirrors MediaSearchDialog.
 * Search-and-add isn't a single submit, it's a list of independent per-result
 * actions, so there's no shared form state to hoist into a form dialog.
 */
export default function BoardGameSearchDialog() {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    size="icon"
                    className="bg-player text-player-ink hover:bg-player ring-background lg:bg-player-ink lg:text-player lg:hover:bg-player-ink fixed right-4 bottom-[calc(env(safe-area-inset-bottom)+5.25rem)] z-30 size-14 rounded-full ring-4 transition-transform duration-300 ease-[var(--ease-out-expo)] group-data-[scroll=down]/shell:translate-y-[calc(100%+6rem)] hover:brightness-110 lg:top-5 lg:right-auto lg:bottom-auto lg:left-[12.75rem] lg:size-11 lg:ring-0 lg:group-data-[scroll=down]/shell:translate-y-0"
                    aria-label={t('boardGames.addGame')}
                >
                    <Plus className="size-6" />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('boardGames.addGame')}</DialogTitle>
                </DialogHeader>
                <BoardGameSearch />
            </DialogContent>
        </Dialog>
    );
}
