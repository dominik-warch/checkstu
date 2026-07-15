import { Plus } from 'lucide-react';
import { useState } from 'react';

import MediaSearch from '@/components/media/media-search';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { t } from '@/lib/i18n';

/**
 * Thin Dialog wrapper around MediaSearch. Deliberately not split into a
 * Dialog/FormBody pair like TaskFormDialog — search-and-add isn't a single
 * submit, it's a list of independent per-result actions, so there's no shared
 * form state to hoist out.
 */
export default function MediaSearchDialog() {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    size="icon"
                    className="fixed right-4 bottom-[calc(env(safe-area-inset-bottom)+5rem)] z-20 size-14 rounded-full shadow-lg"
                    aria-label={t('media.addNew')}
                >
                    <Plus className="size-6" />
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{t('media.addNew')}</DialogTitle>
                </DialogHeader>
                <MediaSearch />
            </DialogContent>
        </Dialog>
    );
}
