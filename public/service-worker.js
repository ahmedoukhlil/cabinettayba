const CACHE_NAME = 'tayba-pwa-v1';
const STATIC_ASSETS = [
  '/offline.html',
  '/icons/pwa-192.png',
  '/icons/pwa-512.png',
  '/css/app.css',
  '/js/pwa.js'
];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET' || new URL(event.request.url).origin !== self.location.origin) return;

  if (event.request.mode === 'navigate') {
    event.respondWith(fetch(event.request).catch(() => caches.match('/offline.html')));
    return;
  }

  event.respondWith(
    caches.match(event.request).then(cached => cached || fetch(event.request).then(response => {
      if (response.ok && ['style', 'script', 'image', 'font'].includes(event.request.destination)) {
        const copy = response.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
      }
      return response;
    }))
  );
});

self.addEventListener('push', event => {
  let payload = {};
  try { payload = event.data ? event.data.json() : {}; } catch (_) {}

  event.waitUntil(self.registration.showNotification(payload.title || 'Cabinet Tayba', {
    body: payload.body || 'Vous avez une nouvelle notification.',
    icon: '/icons/pwa-192.png',
    badge: '/icons/pwa-192.png',
    tag: payload.tag || 'tayba-notification',
    dir: payload.dir || 'auto',
    data: { url: payload.url || '/' },
    vibrate: [150, 80, 150]
  }));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
    const existing = clients.find(client => client.url === target);
    return existing ? existing.focus() : self.clients.openWindow(target);
  }));
});
