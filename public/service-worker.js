const CACHE_NAME = 'abokisub-version-force-refresh';

self.addEventListener('install', event => {
    // Force this new service worker to become the active one, bypassing the "wait" state.
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        // Delete ALL existing caches (including the old design cache)
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    return caches.delete(cacheName);
                })
            );
        }).then(() => {
            // Take control of all open pages immediately
            return self.clients.claim();
        })
    );
});
