import { router } from '@inertiajs/react';
import { useState } from 'react';

import { celebrateCompletion } from '@/lib/confetti';

/** Shared complete-occurrence mutation (with confetti) for the task card and the task detail page. */
export function useCompleteOccurrence(occurrenceId: number, assigneeColor?: string | null) {
    const [processing, setProcessing] = useState(false);

    const complete = (completedByUserId?: number) => {
        setProcessing(true);
        router.post(route('occurrences.complete', occurrenceId), completedByUserId ? { completed_by_user_id: completedByUserId } : {}, {
            preserveScroll: true,
            onSuccess: () => celebrateCompletion(assigneeColor),
            onFinish: () => setProcessing(false),
        });
    };

    return { processing, complete };
}
