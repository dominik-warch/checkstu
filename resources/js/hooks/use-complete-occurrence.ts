import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { showUndo } from '@/components/undo-toast';
import { celebrateCompletion } from '@/lib/confetti';
import type { SharedData } from '@/types';
import type { Occurrence } from '@/types/checkstu';

/** Shared complete-occurrence mutation (with confetti and the undo toast) for the task card, the plate and the task detail page. */
export function useCompleteOccurrence(occurrence: Occurrence | null, assigneeColor?: string | null) {
    const me = usePage<SharedData>().props.auth.user;
    const [processing, setProcessing] = useState(false);

    // `by` is the member an admin picked in "Wer hat es erledigt?"; without it the server credits the assignee, else you.
    // `onFailed` runs when the request didn't go through (offline, server error), e.g. to bring a swiped card back.
    const complete = (by?: { id: number; name: string }, onFailed?: () => void) => {
        if (!occurrence) return;
        const credited = by ?? occurrence.assignee;
        let succeeded = false;
        setProcessing(true);
        router.post(route('occurrences.complete', occurrence.id), by ? { completed_by_user_id: by.id } : {}, {
            preserveScroll: true,
            onSuccess: () => {
                succeeded = true;
                celebrateCompletion(assigneeColor);
                showUndo({
                    title: occurrence.title,
                    note: credited && credited.id !== me.id ? `Erledigt von ${credited.name}` : 'Von dir erledigt',
                    undo: () => router.delete(route('occurrences.restore', occurrence.id), { preserveScroll: true }),
                });
            },
            onFinish: () => {
                setProcessing(false);
                if (!succeeded) onFailed?.();
            },
        });
    };

    return { processing, complete };
}
