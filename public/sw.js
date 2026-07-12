// checkstu service worker — static-asset caching + offline fallback only.
// Deliberately never caches navigations, Inertia data requests, or any API
// call: task state is shared/live household data and must never be served stale.

const CACHE_VERSION = 'v2';
const STATIC_CACHE = `checkstu-static-${CACHE_VERSION}`;

const PRECACHE_URLS = [
    '/manifest.webmanifest',
    '/offline.html',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-512-maskable.png',
    '/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('checkstu-') && key !== STATIC_CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

function isCacheableStaticAsset(url) {
    return url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname === '/manifest.webmanifest';
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    if (isCacheableStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        const copy = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                        return response;
                    }),
            ),
        );
    }

    // Everything else (Inertia page-data fetches, API calls, auth) passes
    // straight through to the network untouched — never cached.
});

self.addEventListener('message', (event) => {
    if (event.data === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const payload = event.data.json();
    const data = payload.data || {};

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: payload.icon || '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            data,
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (new URL(client.url).origin === self.location.origin && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }

            return clients.openWindow(url);
        }),
    );
});
