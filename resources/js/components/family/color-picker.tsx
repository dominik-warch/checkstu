import { Check, X } from 'lucide-react';

import { contrastTextColor, USER_COLOR_PRESETS } from '@/lib/color-contrast';
import { cn } from '@/lib/utils';

interface ColorPickerProps {
    value: string | null;
    onChange: (color: string | null) => void;
    /** Colours other members already wear; shown but not pickable, so no two players share one. */
    taken?: string[];
}

/**
 * The player colours: a closed set (no free colour input), so nobody ends up red or green
 * (overdue/done) or in a hue the board can't tell apart. A colour someone else has is crossed out.
 */
export default function ColorPicker({ value, onChange, taken = [] }: ColorPickerProps) {
    const normalized = value?.toLowerCase() ?? null;
    const takenSet = new Set(taken.map((c) => c.toLowerCase()));

    return (
        <div className="flex flex-wrap items-center gap-1">
            <button
                type="button"
                aria-pressed={normalized === null}
                aria-label="Keine Farbe"
                onClick={() => onChange(null)}
                className="grid size-11 place-items-center rounded-full"
            >
                <span
                    className={cn(
                        'grid size-8 place-items-center rounded-full border-2 border-dashed',
                        normalized === null ? 'border-foreground' : 'border-muted-foreground',
                    )}
                >
                    <X className="text-muted-foreground size-4" />
                </span>
            </button>

            {USER_COLOR_PRESETS.map(({ hex, name }) => {
                const selected = normalized === hex;
                const unavailable = !selected && takenSet.has(hex);
                return (
                    <button
                        key={hex}
                        type="button"
                        aria-pressed={selected}
                        aria-label={unavailable ? `${name} (schon vergeben)` : name}
                        disabled={unavailable}
                        onClick={() => onChange(hex)}
                        className="grid size-11 place-items-center rounded-full disabled:cursor-not-allowed"
                    >
                        <span
                            className={cn(
                                'grid size-8 place-items-center rounded-full',
                                selected && 'ring-foreground ring-offset-background ring-2 ring-offset-2',
                                unavailable && 'opacity-30',
                            )}
                            style={{ backgroundColor: hex, color: contrastTextColor(hex) }}
                        >
                            {selected && <Check className="size-4" strokeWidth={3} />}
                            {unavailable && <X className="size-4" strokeWidth={3} />}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
