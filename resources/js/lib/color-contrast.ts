/**
 * Curated preset palette for the user color picker — chosen to have decent,
 * unambiguous contrast with computed black/white text (avoids muddy
 * mid-luminance colors that would sit right on the threshold). Red and green
 * are left out on purpose: they mean "überfällig" and "erledigt" on the board.
 */
export const USER_COLOR_PRESETS: string[] = [
    '#f97316', // orange
    '#f59e0b', // amber
    '#facc15', // yellow
    '#06b6d4', // cyan
    '#3b82f6', // blue
    '#4f46e5', // indigo
    '#a855f7', // purple
    '#ec4899', // pink
    '#64748b', // slate
];

function relativeLuminance(hex: string): number {
    const channel = (offset: number) => {
        const c = parseInt(hex.slice(offset, offset + 2), 16) / 255;
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    };
    return 0.2126 * channel(0) + 0.7152 * channel(2) + 0.0722 * channel(4);
}

/**
 * Picks ink (#14161c) or white text for a given hex background color: whichever
 * has the higher WCAG contrast ratio against it.
 */
export function contrastTextColor(hex: string): '#14161c' | '#ffffff' {
    const match = /^#?([0-9a-f]{6})$/i.exec(hex.trim());
    if (!match) {
        return '#14161c';
    }

    const bg = relativeLuminance(match[1]);
    const ink = relativeLuminance('14161c');
    const onWhite = 1.05 / (bg + 0.05);
    const onInk = (bg + 0.05) / (ink + 0.05);

    return onInk >= onWhite ? '#14161c' : '#ffffff';
}
