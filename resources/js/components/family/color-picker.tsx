import { Palette, X } from 'lucide-react';

import { USER_COLOR_PRESETS } from '@/lib/color-contrast';
import { cn } from '@/lib/utils';

interface ColorPickerProps {
    value: string | null;
    onChange: (color: string | null) => void;
}

/**
 * Preset swatches for a quick pick, a "no color" clear option, and a native
 * <input type="color"> (styled invisible, overlaid on a small trigger circle)
 * for anything outside the presets.
 */
export default function ColorPicker({ value, onChange }: ColorPickerProps) {
    const normalized = value?.toLowerCase() ?? null;

    return (
        <div className="flex flex-wrap items-center gap-2">
            <button
                type="button"
                onClick={() => onChange(null)}
                className={cn(
                    'flex size-8 items-center justify-center rounded-full border-2',
                    normalized === null ? 'border-foreground' : 'border-transparent',
                )}
                aria-label="Keine Farbe"
                aria-pressed={normalized === null}
            >
                <X className="text-muted-foreground size-4" />
            </button>

            {USER_COLOR_PRESETS.map((preset) => (
                <button
                    key={preset}
                    type="button"
                    onClick={() => onChange(preset)}
                    className={cn('size-8 rounded-full border-2', normalized === preset ? 'border-foreground' : 'border-transparent')}
                    style={{ backgroundColor: preset }}
                    aria-label={preset}
                    aria-pressed={normalized === preset}
                />
            ))}

            <label className="border-muted-foreground relative flex size-8 cursor-pointer items-center justify-center rounded-full border-2 border-dashed">
                <input
                    type="color"
                    value={value ?? '#888888'}
                    onChange={(e) => onChange(e.target.value)}
                    className="absolute inset-0 size-full cursor-pointer opacity-0"
                    aria-label="Eigene Farbe wählen"
                />
                <Palette className="text-muted-foreground pointer-events-none size-4" />
            </label>
        </div>
    );
}
