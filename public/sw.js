const CACHE_VERSION = 'harmonia-v12';
const CACHE_STATIC   = 'harmonia-static-v12';
const CACHE_PAGES    = 'harmonia-pages-v12';
const CACHE_FONTS    = 'harmonia-fonts-v12';
const CACHE_IMAGES   = 'harmonia-images-v12';
const CACHE_API      = 'harmonia-api-v12';

const STATIC_ASSETS = [
    '/assets/css/admin.css',
    '/assets/css/citoyen.css',
    '/assets/css/landing.css',
    '/assets/css/fonts.css',
    '/assets/vendor/bootstrap/bootstrap.min.css',
    '/assets/vendor/bootstrap/bootstrap.bundle.min.js',
    '/assets/vendor/mdi/css/materialdesignicons.min.css',
    '/assets/vendor/mdi/fonts/materialdesignicons-webfont.woff2',
    '/assets/vendor/fonts/Inter-Regular.woff2',
    '/assets/vendor/fonts/Inter-SemiBold.woff2',
    '/assets/vendor/fonts/SpaceGrotesk-700.woff2',
    '/assets/vendor/zxing/index.min.js',
    '/assets/img/icon-192.png',
    '/assets/img/icon-512.png',
    '/assets/img/icon-256.png',
    '/offline.html'
];

const FONT_URLS = ['fonts.googleapis.com', 'fonts.gstatic.com', 'cdn.jsdelivr.net'];

/* ── Install ── */
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

/* ── Activate ── */
self.addEventListener('activate', event => {
    const KEEP = [CACHE_STATIC, CACHE_PAGES, CACHE_FONTS, CACHE_IMAGES, CACHE_API, CACHE_VERSION];
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => !KEEP.includes(k)).map(k => caches.delete(k))
            ))
            .then(() => self.clients.claim())
            .then(() => syncOfflineScans())
    );
});

/* ── Fetch strategies ── */
self.addEventListener('fetch', event => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    /* API / XHR → network-first with short timeout, cache fallback */
    if (url.pathname.startsWith('/api/') || request.headers.get('X-Requested-With') === 'XMLHttpRequest') {
        event.respondWith(networkFirstWithTimeout(request, CACHE_API, 3000));
        return;
    }

    /* Static assets → cache-first */
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request, CACHE_STATIC));
        return;
    }

    /* Fonts → cache-first, long-lived */
    if (isFont(url)) {
        event.respondWith(cacheFirst(request, CACHE_FONTS));
        return;
    }

    /* Images → stale-while-revalidate */
    if (isImage(url.pathname)) {
        event.respondWith(staleWhileRevalidate(request, CACHE_IMAGES));
        return;
    }

    /* Navigation (HTML pages) → network-first, offline fallback */
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(networkFirst(request, CACHE_PAGES));
        return;
    }

    /* Default → stale-while-revalidate */
    event.respondWith(staleWhileRevalidate(request, CACHE_PAGES));
});

/* ── Strategies ── */

function cacheFirst(request, cacheName) {
    return caches.match(request).then(cached => {
        if (cached) return cached;
        return fetch(request).then(response => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(cacheName).then(c => c.put(request, clone));
            }
            return response;
        });
    });
}

function networkFirst(request, cacheName) {
    return fetch(request).then(response => {
        if (response.ok) {
            const clone = response.clone();
            caches.open(cacheName).then(c => c.put(request, clone));
        }
        return response;
    }).catch(() => {
        return caches.match(request).then(cached => cached || caches.match('/offline.html'));
    });
}

function networkFirstWithTimeout(request, cacheName, timeout) {
    return Promise.race([
        fetch(request).then(response => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(cacheName).then(c => c.put(request, clone));
            }
            return response;
        }),
        new Promise(resolve => {
            setTimeout(() => {
                caches.match(request).then(cached => {
                    resolve(cached || new Response('{"offline":true}', {
                        status: 503,
                        headers: { 'Content-Type': 'application/json' }
                    }));
                });
            }, timeout);
        })
    ]).catch(() => {
        return caches.match(request).then(cached => cached || new Response('{"offline":true}', {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        }));
    });
}

function staleWhileRevalidate(request, cacheName) {
    return caches.match(request).then(cached => {
        const fetching = fetch(request).then(response => {
            if (response.ok) {
                const clone = response.clone();
                caches.open(cacheName).then(c => c.put(request, clone));
            }
            return response;
        }).catch(() => cached);
        return cached || fetching;
    });
}

/* ── Helpers ── */

function isStaticAsset(path) {
    return /\.(css|js|min\.js|min\.css)$/i.test(path)
        || path.includes('/vendor/')
        || path === '/sw.js';
}

function isFont(url) {
    return FONT_URLS.some(d => url.hostname.includes(d))
        || /\.(woff2?|ttf|otf|eot)$/i.test(url.pathname);
}

function isImage(path) {
    return /\.(png|jpg|jpeg|gif|svg|webp|ico|avif)$/i.test(path);
}

/* ── Push notifications ── */
self.addEventListener('push', event => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { title: 'حومتي ايفانت', body: event.data ? event.data.text() : '' }; }
    event.waitUntil(
        self.registration.showNotification(data.title || 'حومتي ايفانت', {
            body: data.body || '',
            icon: data.icon || '/assets/img/icon-192.png',
            badge: '/assets/img/icon-192.png',
            vibrate: [100, 50, 100],
            data: { url: data.url || '/', ...data },
            actions: data.actions || [
                { action: 'open', title: 'Ouvrir', icon: '/assets/img/icon-192.png' },
                { action: 'dismiss', title: 'Fermer' }
            ],
            tag: data.tag || 'harmonia-default',
            renotify: true,
            requireInteraction: data.priority === 'high'
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const action = event.action;
    if (action === 'dismiss') return;

    const target = event.notification.data?.url || '/';
    const eventId = event.notification.data?.evenement_id;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
            for (const client of windowClients) {
                if (client.url.includes(target) && 'focus' in client) {
                    return client.focus();
                }
            }
            const url = eventId ? target + (target.includes('?') ? '&' : '?') + 'from_push=1' : target;
            return clients.openWindow(url);
        })
    );
});

/* ── Background sync for offline scans ── */
self.addEventListener('sync', event => {
    if (event.tag === 'sync-scans') {
        event.waitUntil(syncOfflineScans());
    }
    if (event.tag === 'sync-comments') {
        event.waitUntil(syncOfflineComments());
    }
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

async function syncOfflineScans() {
    const clients_list = await self.clients.matchAll();
    clients_list.forEach(client => {
        client.postMessage({ type: 'SYNC_SCANS' });
    });
}

async function syncOfflineComments() {
    const clients_list = await self.clients.matchAll();
    clients_list.forEach(client => {
        client.postMessage({ type: 'SYNC_COMMENTS' });
    });
}

async function syncNotifications() {
    const clients_list = await self.clients.matchAll();
    clients_list.forEach(client => {
        client.postMessage({ type: 'SYNC_NOTIFICATIONS' });
    });
}

/* ── Periodic background sync (for notifications refresh) ── */
self.addEventListener('periodicsync', event => {
    if (event.tag === 'refresh-notifications') {
        event.waitUntil(syncNotifications());
    }
});

/* ── Message handler ── */
self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    if (event.data?.type === 'CACHE_URLS') {
        const urls = event.data.urls || [];
        caches.open(CACHE_PAGES).then(cache => {
            urls.forEach(url => {
                fetch(url).then(res => {
                    if (res.ok) cache.put(url, res);
                }).catch(() => {});
            });
        });
    }
    if (event.data?.type === 'CACHE_EVENT_DETAIL') {
        const url = event.data.url;
        if (url) {
            caches.open(CACHE_PAGES).then(cache => {
                fetch(url).then(res => {
                    if (res.ok) cache.put(url, res);
                }).catch(() => {});
            });
        }
    }
    if (event.data?.type === 'GET_VAPID_KEY') {
        event.source.postMessage({ type: 'VAPID_KEY', key: event.data.key });
    }
    if (event.data?.type === 'OFFLINE_SCAN_QUEUED') {
        /* Try to sync immediately when we come back online */
        if (self.registration.sync) {
            self.registration.sync.register('sync-scans').catch(() => {});
        }
    }
});
