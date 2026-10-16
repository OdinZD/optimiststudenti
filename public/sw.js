/*
 * Optimist — service worker.
 *
 * Strict, privacy-safe caching for an app that holds minors' PII:
 *   - Cache-first ONLY for the public app shell: /build/* (Vite), /icons/*, fonts (.woff2),
 *     /manifest.json, /favicon.ico, and the offline page.
 *   - Navigations (HTML) go to the network; the offline page is the only fallback and is never
 *     written to the cache.
 *   - Everything else (/livewire/*, /storage/*, /izjava/*, Excel/PDF exports, any POST, anything
 *     behind auth) is plain network passthrough and is NEVER cached.
 *
 * Because the cache only ever holds the public shell, logging out leaves no personal data behind.
 * When this file changes, bump the cache version (v1 -> v2) so activate() purges the old cache.
 */
const CACHE = 'optimist-static-v1';
const OFFLINE_URL = '/offline.html';
const PRECACHE = [
    OFFLINE_URL,
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

function isStaticAsset(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/manifest.json'
        || url.pathname === '/favicon.ico'
        || url.pathname.endsWith('.woff2');
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Never intervene on writes (POST etc.) — let Livewire/forms hit the network untouched.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Only ever handle our own origin.
    if (url.origin !== self.location.origin) {
        return;
    }

    // HTML navigations: network-first, offline page as the only fallback, response never cached.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Public app shell: cache-first, then fill the cache.
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(request).then((hit) => hit || fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(request, copy));
                return response;
            }))
        );
        return;
    }

    // Everything else (/livewire/*, /storage/*, /izjava/*, exports, APIs): network passthrough, no cache.
});
