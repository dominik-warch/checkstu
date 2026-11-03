import { router } from '@inertiajs/react';

// Inertia moves through history with pushState, so document.referrer never changes inside the app.
// Remember the page before this one ourselves, for back links that should go where you came from.
let current: string | null = null;
let previous: string | null = null;

router.on('navigate', (event) => {
    const url = event.detail.page.url;
    if (url === current) return; // a partial reload of the same page (e.g. after completing) isn't a move
    previous = current;
    current = url;
});

/** Path (with query) of the in-app page you came from, or null on a fresh load. */
export function previousPage(): string | null {
    return previous;
}
