import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { announceDone } from '@/components/undo-toast';
import { celebrateCompletion } from '@/lib/confetti';
import type { SharedData } from '@/types';
import type { Occurrence } from '@/types/checkstu';

/** Shared complete-occurrence mutation (with confetti and the undo toast) for the task card, the plate and the task detail page. */
export function useCompleteOccurrence(occurrence: Occurrence | null, assigneeColor?: string | null) {
    const me = usePage<SharedData>().props.auth.user;
    const [processing, setProcessing] = useState(false);

    // `by` is the member an admin picked in "Wer hat es erledigt?"; without it the server credits the assignee, else you.
    const complete = (by?: { id: number; name: string }) => {
        if (!occurrence) return;
        const credited = by ?? occurrence.assignee;
        setProcessing(true);
        router.post(route('occurrences.complete', occurrence.id), by ? { completed_by_user_id: by.id } : {}, {
            preserveScroll: true,
            onSuccess: () => {
                celebrateCompletion(assigneeColor);
                announceDone({ occurrenceId: occurrence.id, title: occurrence.title, by: credited && credited.id !== me.id ? credited.name : null });
            },
            onFinish: () => setProcessing(false),
        });
    };

    return { processing, complete };
}
