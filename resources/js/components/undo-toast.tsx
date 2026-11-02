import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface Done {
    occurrenceId: number;
    title: string;
    /** Who got the credit, or null when it was you. */
    by: string | null;
}

const EVENT = 'checkstu:done';

/** Tell the undo toast a task was just completed. */
export function announceDone(done: Done) {
    window.dispatchEvent(new CustomEvent<Done>(EVENT, { detail: done }));
}

/**
 * "Erledigt von Leo · Rückgängig" for five seconds after any completion. Lives outside the
 * page so it survives the card it came from disappearing; hovering or focusing it holds it open.
 */
export function UndoToast() {
    const [done, setDone] = useState<Done | null>(null);
    const [held, setHeld] = useState(false);

    useEffect(() => {
        const show = (e: Event) => setDone((e as CustomEvent<Done>).detail);
        window.addEventListener(EVENT, show);
        return () => window.removeEventListener(EVENT, show);
    }, []);

    useEffect(() => {
        if (!done || held) return;
        const timer = setTimeout(() => setDone(null), 5000);
        return () => clearTimeout(timer);
    }, [done, held]);

    const undo = () => {
        if (!done) return;
        router.delete(route('occurrences.restore', done.occurrenceId), { preserveScroll: true });
        setDone(null);
        setHeld(false);
    };

    return (
        <div
            role="status"
            className="pointer-events-none fixed right-[5.5rem] bottom-[calc(env(safe-area-inset-bottom)+5.25rem)] left-4 z-40 lg:right-6 lg:bottom-6 lg:left-auto lg:w-96"
        >
            {done && (
                <div
                    key={done.occurrenceId}
                    className="bg-foreground text-background animate-toast-in pointer-events-auto flex min-h-14 items-center gap-3 rounded-2xl py-1.5 pr-1.5 pl-4"
                    onPointerEnter={() => setHeld(true)}
                    onPointerLeave={() => setHeld(false)}
                    onFocus={() => setHeld(true)}
                    onBlur={() => setHeld(false)}
                >
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-bold">{done.title}</p>
                        <p className="text-sm">{done.by ? `Erledigt von ${done.by}` : 'Von dir erledigt'}</p>
                    </div>
                    <button
                        type="button"
                        onClick={undo}
                        className="bg-background text-foreground min-h-11 shrink-0 rounded-full px-4 text-sm font-bold"
                    >
                        Rückgängig
                    </button>
                </div>
            )}
        </div>
    );
}
