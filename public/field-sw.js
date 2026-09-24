// Service worker of the technician PWA (/field, 14.md). Network-first for
// everything the app needs, falling back to the last cached copy offline.
// Sync POSTs are never cached — the offline queue lives in IndexedDB.
const CACHE = 'custovis-field-v1';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

const cacheable = (url) =>
    url.origin === self.location.origin &&
    (url.pathname === '/field' || url.pathname === '/field/today' || url.pathname.startsWith('/build/') || url.pathname === '/field-manifest.webmanifest');

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || !cacheable(url)) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(event.request, copy));
                }
                return response;
            })
            .catch(() => caches.match(event.request)),
    );
});
