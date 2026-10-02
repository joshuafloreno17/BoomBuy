// Bump the version whenever this file changes — the old cache is deleted on activate.
const CACHE_NAME = 'boombuy-v2';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/manifest.json',
    '/images/boombuy-logo.png',
    '/images/icon.svg',
    '/favicon.ico',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE_NAME)
                        .map((key) => caches.delete(key))
                )
            )
    );
    self.clients.claim();
});

function saveCopy(request, response) {
    if (response.ok && new URL(request.url).origin === self.location.origin) {
        const clone = response.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
    }

    return response;
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    // Page navigations: try the network first so content stays fresh,
    // fall back to the offline page when there's no connection.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Stylesheets and scripts: network first, so a deploy shows up right
    // away; the cached copy is only for when the user is offline.
    if (request.destination === 'style' || request.destination === 'script') {
        event.respondWith(
            fetch(request)
                .then((response) => saveCopy(request, response))
                .catch(() => caches.match(request))
        );
        return;
    }

    // Images and fonts rarely change: cache first, then the network.
    if (request.destination === 'image' || request.destination === 'font') {
        event.respondWith(
            caches.match(request).then((cached) =>
                cached || fetch(request).then((response) => saveCopy(request, response))
            )
        );
        return;
    }

    // Everything else (fetch()/AJAX data like messages, cart, search
    // suggestions) always goes straight to the server — never cached.
});
