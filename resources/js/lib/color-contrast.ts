/**
 * Curated preset palette for the user color picker — chosen to have decent,
 * unambiguous contrast with computed black/white text (avoids muddy
 * mid-luminance colors that would sit right on the threshold).
 */
export const USER_COLOR_PRESETS: string[] = [
    '#ef4444', // red
    '#f97316', // orange
    '#f59e0b', // amber
    '#84cc16', // lime
    '#22c55e', // green
    '#14b8a6', // teal
    '#3b82f6', // blue
    '#6366f1', // indigo
    '#a855f7', // purple
    '#ec4899', // pink
    '#64748b', // slate
];

/**
 * Picks black or white text for a given hex background color using the YIQ
 * perceived-brightness formula — the standard, simple approach for this exact
 * "what text color reads on an arbitrary background" problem.
 */
export function contrastTextColor(hex: string): '#000000' | '#ffffff' {
    const match = /^#?([0-9a-f]{6})$/i.exec(hex.trim());
    if (!match) {
        return '#000000';
    }

    const value = match[1];
    const r = parseInt(value.slice(0, 2), 16);
    const g = parseInt(value.slice(2, 4), 16);
    const b = parseInt(value.slice(4, 6), 16);

    const yiq = (r * 299 + g * 587 + b * 114) / 1000;

    return yiq >= 128 ? '#000000' : '#ffffff';
}
