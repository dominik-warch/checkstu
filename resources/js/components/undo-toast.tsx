import { useEffect, useState } from 'react';

interface Undoable {
    title: string;
    /** "Von dir erledigt", "Erledigt von Leo", "Gelöscht", … */
    note: string;
    undo: () => void;
}

const EVENT = 'checkstu:undo';

/** Offer five seconds to take back what just happened (a completion, a delete). */
export function showUndo(undoable: Undoable) {
    window.dispatchEvent(new CustomEvent<Undoable>(EVENT, { detail: undoable }));
}

/**
 * The bar under the thumb that says what just happened and offers "Rückgängig". Lives outside
 * the page so it survives the card it came from disappearing; hovering or focusing it holds it open.
 */
export function UndoToast() {
    const [shown, setShown] = useState<Undoable | null>(null);
    const [held, setHeld] = useState(false);

    useEffect(() => {
        const show = (e: Event) => setShown((e as CustomEvent<Undoable>).detail);
        window.addEventListener(EVENT, show);
        return () => window.removeEventListener(EVENT, show);
    }, []);

    useEffect(() => {
        if (!shown || held) return;
        const timer = setTimeout(() => setShown(null), 5000);
        return () => clearTimeout(timer);
    }, [shown, held]);

    const undo = () => {
        shown?.undo();
        setShown(null);
        setHeld(false);
    };

    return (
        <div
            role="status"
            className="pointer-events-none fixed right-[5.5rem] bottom-[calc(env(safe-area-inset-bottom)+5.25rem)] left-4 z-40 lg:right-6 lg:bottom-6 lg:left-auto lg:w-96"
        >
            {shown && (
                <div
                    key={`${shown.title}${shown.note}`}
                    className="bg-foreground text-background animate-toast-in pointer-events-auto flex min-h-14 items-center gap-3 rounded-2xl py-1.5 pr-1.5 pl-4 [--color-ring:var(--background)] [--ring:var(--background)]"
                    onPointerEnter={() => setHeld(true)}
                    onPointerLeave={() => setHeld(false)}
                    onFocus={() => setHeld(true)}
                    onBlur={() => setHeld(false)}
                >
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-bold">{shown.title}</p>
                        <p className="text-sm">{shown.note}</p>
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
