const CACHE_NAME = 'cabinet-pwa-v13';
const STATIC_ASSETS = [
  '/offline.html',
  '/branding/logo.svg',
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
  const url = new URL(event.request.url);

  if (event.request.method !== 'GET' || url.origin !== self.location.origin) return;

  // Livewire is upgraded independently from the PWA. Serving a cached
  // Livewire runtime from an older release prevents wire:click actions from
  // reaching the server, so these assets must always come from the network.
  if (url.pathname.startsWith('/livewire/')) {
    event.respondWith(fetch(event.request).catch(() => new Response('', { status: 503, statusText: 'Offline' })));
    return;
  }

  if (event.request.mode === 'navigate') {
    event.respondWith(fetch(event.request).catch(() => caches.match('/offline.html')));
    return;
  }

  event.respondWith(
    caches.match(event.request).then(cached => cached || fetch(event.request)
      .then(response => {
        if (response.ok && ['style', 'script', 'image', 'font'].includes(event.request.destination)) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
        }
        return response;
      })
      .catch(() => new Response('', { status: 503, statusText: 'Offline' })))
  );
});

self.addEventListener('push', event => {
  let payload = {};
  try { payload = event.data ? event.data.json() : {}; } catch (_) {}

  event.waitUntil(Promise.all([
    self.registration.showNotification(payload.title || 'Nouvelle notification', {
      body: payload.body || 'Vous avez une nouvelle notification.',
      icon: '/branding/logo.svg',
      badge: '/branding/logo.svg',
      tag: payload.tag || 'cabinet-notification',
      dir: payload.dir || 'auto',
      data: { url: payload.url || '/' },
      vibrate: [150, 80, 150]
    }),
    typeof self.registration.setAppBadge === 'function' && payload.badgeCount
      ? self.registration.setAppBadge(payload.badgeCount)
      : Promise.resolve()
  ]));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async clients => {
    const exactClient = clients.find(client => client.url === target);
    if (exactClient) return exactClient.focus();

    const appClient = clients.find(client => new URL(client.url).origin === self.location.origin);
    if (appClient && 'navigate' in appClient) {
      await appClient.navigate(target);
      return appClient.focus();
    }

    return self.clients.openWindow(target);
  }));
});
