const CACHE_NAME = 'rep-shell-v1';
const PRECACHE_URLS = [
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

// Cache-first for static assets (css/js/icons/fonts); everything else (pages,
// Livewire requests) always goes to the network — this is installability +
// asset caching only, not offline page rendering.
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    const isStaticAsset = /\.(?:css|js|png|jpg|jpeg|svg|woff2?|ico)$/.test(url.pathname);

    if (event.request.method !== 'GET' || !isStaticAsset) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(event.request).then((response) => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return response;
            });
        })
    );
});
