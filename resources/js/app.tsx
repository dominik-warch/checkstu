import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { showUndo, UndoToast } from './components/undo-toast';
import { UpdateToast } from './components/update-toast';
import { initializeTheme } from './hooks/use-appearance';
import './lib/previous-page';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Inertia's in-app visits go through fetch(), which the service worker never
// intercepts (task data must stay live), so a dropped connection would just
// swallow the tap. Instead of leaving the app for /offline.html (which only
// serves full page loads now), stay where you are and say the tap didn't
// arrive, with "Nochmal" repeating exactly that visit.
let lastVisit: { url: URL; method: string; data: unknown } | null = null;
router.on('start', (event) => {
    lastVisit = event.detail.visit;
});
router.on('exception', (event) => {
    const error = event.detail.exception as { code?: string } | undefined;
    if (navigator.onLine && error?.code !== 'ERR_NETWORK') return;
    event.preventDefault();

    const visit = lastVisit;
    const isChange = visit !== null && visit.method !== 'get';
    showUndo({
        title: isChange ? 'Nicht angekommen' : 'Seite nicht geladen',
        note: isChange ? 'Keine Verbindung, nichts gespeichert.' : 'Keine Verbindung.',
        actionLabel: 'Nochmal',
        undo: () => {
            if (visit)
                router.visit(visit.url.href, { method: visit.method as 'get', data: visit.data as Record<string, never>, preserveScroll: true });
        },
    });
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <UndoToast />
                <UpdateToast />
            </>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
