/**
 * Zouetech-PMS - Service Worker
 * Provides offline shell, static asset caching, and push notification handling.
 */
const CACHE_VERSION = 'v1';
const CACHE_STATIC = 'zouetech-pms-static-' + CACHE_VERSION;
const CACHE_DYNAMIC = 'zouetech-pms-dynamic-' + CACHE_VERSION;

// App base path (where sw.js lives)
const BASE = self.location.pathname.replace(/\/sw\.js.*$/, '') || '/';
const BASE_SLASH = BASE.endsWith('/') ? BASE : BASE + '/';
const OFFLINE_PATH = BASE_SLASH + 'offline.html';

// Static assets to cache on install (CDN + local)
const STATIC_ASSETS = [
    BASE_SLASH,
    BASE_SLASH + 'login.php',
    BASE_SLASH + 'offline.html',
    'https://cdn.tailwindcss.com',
    'https://code.jquery.com/jquery-3.7.1.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Rajdhani:wght@500;600;700&family=JetBrains+Mono:wght@400;500&display=swap'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_STATIC).then((cache) => {
            return cache.addAll(STATIC_ASSETS.map(u => u.startsWith('http') ? u : (self.location.origin + (u.startsWith('/') ? u : '/' + u)))).catch(() => {});
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(keys.filter(k => k.startsWith('zouetech-pms-') && k !== CACHE_STATIC && k !== CACHE_DYNAMIC).map(k => caches.delete(k)));
        }).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);
    if (url.origin !== self.location.origin) {
        if (url.hostname === 'cdn.tailwindcss.com' || url.hostname === 'code.jquery.com' || url.hostname === 'cdn.jsdelivr.net' || url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
            e.respondWith(cacheFirst(e.request));
            return;
        }
        return;
    }
    if (url.pathname.indexOf('/api/') !== -1) {
        e.respondWith(networkOnly(e.request));
        return;
    }
    e.respondWith(networkFirst(e.request));
});

function networkFirst(req) {
    return fetch(req).then((res) => {
        const clone = res.clone();
        caches.open(CACHE_DYNAMIC).then((c) => c.put(req, clone));
        return res;
    }).catch(() => {
        return caches.match(req).then((cached) => {
            if (cached) return cached;
            if (req.mode === 'navigate') return caches.match(self.location.origin + (OFFLINE_PATH.startsWith('/') ? OFFLINE_PATH : '/' + OFFLINE_PATH));
            return new Response('', { status: 503, statusText: 'Service Unavailable' });
        });
    });
}

function cacheFirst(req) {
    return caches.match(req).then((cached) => cached || fetch(req).then((res) => {
        const clone = res.clone();
        caches.open(CACHE_STATIC).then((c) => c.put(req, clone));
        return res;
    }));
}

function networkOnly(req) {
    return fetch(req);
}

self.addEventListener('push', (e) => {
    let data = { title: 'Zouetech-PMS', body: 'You have a new notification.' };
    try {
        if (e.data) data = e.data.json();
    } catch (_) {
        if (e.data) data.body = e.data.text();
    }
    e.waitUntil(
        self.registration.showNotification(data.title || 'Zouetech-PMS', {
            body: data.body || '',
            icon: self.location.origin + BASE_SLASH + 'pwa-icons/icon.png',
            badge: self.location.origin + BASE_SLASH + 'pwa-icons/icon.png',
            tag: data.tag || 'default',
            requireInteraction: !!data.requireInteraction,
            data: data.data || { url: data.url || '/' }
        })
    );
});

self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    const url = e.notification.data?.url || '/';
    e.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            if (clients.length) clients[0].focus();
            else self.clients.openWindow(self.location.origin + BASE_SLASH.replace(/\/$/, '') + (url.startsWith('/') ? url : '/' + url));
        })
    );
});
