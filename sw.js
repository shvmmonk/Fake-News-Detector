/**
 * FakeGuard Service Worker v2.0
 * Strategy:
 *   - Shell (HTML/CSS/JS/fonts)  → Cache First, fallback to network
 *   - API calls (ai_check.php)   → Network First, no cache
 *   - Images (img_cache)         → Cache First, long TTL
 *   - PHP pages                  → Network First, stale-while-revalidate
 *   - Offline fallback           → Custom offline page
 */

const VERSION   = 'fakeguard-v2';
const SHELL     = 'fg-shell-v2';
const IMAGES    = 'fg-images-v2';
const PAGES     = 'fg-pages-v2';

// Files to cache immediately on install (app shell)
const PRECACHE = [
  '/',
  '/index.php',
  '/news_short.php',
  '/articles.php',
  '/verify.php',
  '/offline.html',
  'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Oswald:wght@400;500;600;700&family=Source+Sans+3:wght@300;400;600&display=swap',
  'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap',
  'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
];

// ── INSTALL: precache the shell ──────────────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(SHELL)
      .then(cache => cache.addAll(PRECACHE.map(url => new Request(url, { mode: 'cors' })).filter(() => true)))
      .then(() => self.skipWaiting())
      .catch(err => console.warn('[SW] Precache partial failure:', err))
  );
});

// ── ACTIVATE: remove old caches ──────────────────────────────
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys
          .filter(k => ![SHELL, IMAGES, PAGES].includes(k))
          .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── FETCH: route requests ────────────────────────────────────
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip non-GET and cross-origin (except fonts/cdn)
  if (event.request.method !== 'GET') return;

  // 1. AI check + scrape endpoints → always network, never cache
  if (url.pathname.includes('ai_check') || url.pathname.includes('scrape_news')) {
    event.respondWith(fetch(event.request));
    return;
  }

  // 2. Image proxy → cache first (images are heavy, TTL managed by PHP)
  if (url.pathname.includes('image_proxy') || url.pathname.includes('img_cache')) {
    event.respondWith(cacheFirst(event.request, IMAGES));
    return;
  }

  // 3. Google Fonts & CDN → cache first
  if (url.hostname.includes('fonts.googleapis') ||
      url.hostname.includes('fonts.gstatic') ||
      url.hostname.includes('cdnjs.cloudflare')) {
    event.respondWith(cacheFirst(event.request, SHELL));
    return;
  }

  // 4. PHP pages → network first, fallback to cache, then offline
  if (url.pathname.endsWith('.php') || url.pathname === '/') {
    event.respondWith(networkFirst(event.request));
    return;
  }

  // 5. Everything else → cache first
  event.respondWith(cacheFirst(event.request, SHELL));
});

// ── Strategy: Cache First ────────────────────────────────────
async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return offlineFallback(request);
  }
}

// ── Strategy: Network First ──────────────────────────────────
async function networkFirst(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(PAGES);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;
    return offlineFallback(request);
  }
}

// ── Offline Fallback ─────────────────────────────────────────
async function offlineFallback(request) {
  const cached = await caches.match('/offline.html');
  if (cached) return cached;

  // If no offline.html cached, return a minimal inline fallback
  return new Response(`
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>FakeGuard — Offline</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;}
  body{background:#0d0d0d;color:#f0f0f0;font-family:sans-serif;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    min-height:100vh;text-align:center;padding:32px;}
  .icon{font-size:4rem;margin-bottom:20px;}
  h1{font-size:2rem;font-weight:900;margin-bottom:12px;color:#e63232;}
  p{color:#777;font-size:0.9rem;line-height:1.7;max-width:320px;}
  a{display:inline-block;margin-top:24px;padding:12px 28px;background:#e63232;
    color:#fff;text-decoration:none;border-radius:4px;font-size:0.85rem;}
</style>
</head>
<body>
  <div class="icon">📡</div>
  <h1>You're Offline</h1>
  <p>FakeGuard needs an internet connection to verify news articles. Connect and try again.</p>
  <a href="javascript:location.reload()">Try Again →</a>
</body>
</html>
  `, {
    status: 200,
    headers: { 'Content-Type': 'text/html; charset=utf-8' }
  });
}

// ── Push Notifications (for future use) ─────────────────────
self.addEventListener('push', event => {
  if (!event.data) return;
  const data = event.data.json();
  event.waitUntil(
    self.registration.showNotification(data.title || 'FakeGuard Alert', {
      body:    data.body    || 'New fact-check available.',
      icon:    '/icons/icon-192.png',
      badge:   '/icons/icon-72.png',
      vibrate: [200, 100, 200],
      data:    { url: data.url || '/index.php' },
      actions: [
        { action: 'view', title: 'View Now' },
        { action: 'dismiss', title: 'Dismiss' }
      ]
    })
  );
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  if (event.action === 'view' || !event.action) {
    const url = event.notification.data?.url || '/index.php';
    event.waitUntil(clients.openWindow(url));
  }
});