import confetti from 'canvas-confetti';

/**
 * A short celebratory burst when a task gets marked done. Skipped entirely for
 * prefers-reduced-motion — this is a delight, not a functional cue, so there's
 * nothing lost by turning it off.
 */
export function celebrateCompletion(accentColor?: string | null): void {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const colors = accentColor ? [accentColor, '#fbbf24', '#ffffff'] : ['#22c55e', '#fbbf24', '#3b82f6'];

    confetti({
        particleCount: 90,
        spread: 75,
        startVelocity: 45,
        origin: { y: 0.7 },
        colors,
        disableForReducedMotion: true,
    });
}
