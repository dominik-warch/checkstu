import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { UpdateToast } from './components/update-toast';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Inertia's client-side navigation (every <Link>/router.visit — i.e. almost
// all in-app navigation) goes through fetch(), which the service worker
// deliberately never intercepts (task data must stay live). So a failed
// visit while offline never hits the SW's /offline.html fallback — that
// fallback only fires for full browser navigations (typing a URL, reloading,
// opening the installed PWA fresh). Without this, a dropped connection while
// using the app just silently swallows the click. Force the same offline
// page for both paths.
router.on('exception', (event) => {
    if (!navigator.onLine) {
        event.preventDefault();
        window.location.href = '/offline.html';
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
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
