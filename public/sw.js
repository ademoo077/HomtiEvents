const CACHE = 'harmonia-v1';
const CORE = [
    '/',
    '/offline.html',
    '/assets/css/landing.css',
    '/assets/img/icon-192.png',
    '/assets/img/icon-512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(CORE)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const req = event.request;
    if (req.method !== 'GET') return;

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/offline.html'))
        );
        return;
    }

    event.respondWith(
        caches.match(req).then(cached => {
            const network = fetch(req).then(res => {
                if (res.ok && new URL(req.url).origin === location.origin) {
                    const clone = res.clone();
                    caches.open(CACHE).then(cache => cache.put(req, clone));
                }
                return res;
            }).catch(() => cached);
            return cached || network;
        })
    );
});

self.addEventListener('push', event => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { title: 'Wilaya Harmonia', body: event.data ? event.data.text() : '' }; }
    event.waitUntil(
        self.registration.showNotification(data.title || 'Wilaya Harmonia', {
            body: data.body || '',
            icon: '/assets/img/icon-192.png',
            badge: '/assets/img/icon-192.png',
            data: { url: data.url || '/' }
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url || '/'));
});
