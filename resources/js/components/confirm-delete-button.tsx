import { Trash2 } from 'lucide-react';
import { useState } from 'react';

import { cn } from '@/lib/utils';

interface ConfirmDeleteButtonProps {
    /** What the button says (and, as an icon, its accessible name): "Spiel entfernen", "Mitglied löschen", … */
    label: string;
    onConfirm: () => void;
    /** What it asks once armed, when the default ("Mitglied löschen?", or "Wirklich?" as an icon) isn't enough. */
    confirmLabel?: string;
    /** A trash icon in a row instead of a full-width text button. */
    icon?: boolean;
    className?: string;
}

/**
 * For deletes that can't be undone: the first tap arms it ("Mitglied löschen?"), the second deletes.
 * Tapping elsewhere disarms it. Replaces the browser's own confirm() popup.
 */
export default function ConfirmDeleteButton({ label, onConfirm, confirmLabel, icon = false, className }: ConfirmDeleteButtonProps) {
    const [armed, setArmed] = useState(false);

    return (
        <button
            type="button"
            onClick={() => (armed ? onConfirm() : setArmed(true))}
            onBlur={() => setArmed(false)}
            aria-label={icon && !armed ? label : undefined}
            className={cn(
                'flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-full text-sm font-bold transition-colors duration-200',
                armed ? 'bg-destructive text-destructive-foreground px-4' : 'text-muted-foreground hover:text-foreground hover:bg-muted',
                icon ? !armed && 'size-11' : 'px-4',
                className,
            )}
        >
            {armed ? (
                (confirmLabel ?? (icon ? 'Wirklich?' : `${label}?`))
            ) : (
                <>
                    <Trash2 className="size-5" />
                    {!icon && label}
                </>
            )}
        </button>
    );
}
