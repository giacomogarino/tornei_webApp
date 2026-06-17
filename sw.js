/**
 * Matchora Tornei — Service Worker
 * Strategia: Network-first con fallback cache per asset statici.
 * Le pagine PHP non vengono mai servite dalla cache (dati real-time).
 */

const CACHE_NAME   = 'matchora-v1';
const STATIC_CACHE = 'matchora-static-v1';

// Asset statici da pre-cachare
const STATIC_ASSETS = [
  '/css/base.css',
  '/css/navbar.css',
  '/css/footer.css',
  '/css/tabella_tornei.css',
  '/css/torneo_struttura.css',
  '/assets/matchora_icon.png',
  '/offline.php',
];

// ── Install: pre-cacha gli asset statici ──────────────────────────
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(STATIC_CACHE)
      .then(c => c.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ── Activate: elimina cache vecchie ───────────────────────────────
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(k => k !== CACHE_NAME && k !== STATIC_CACHE)
            .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ── Fetch: network-first ──────────────────────────────────────────
self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // Solo richieste GET sulla stessa origine
  if (e.request.method !== 'GET' || url.origin !== location.origin) return;

  // CSS/JS/immagini/font: cache-first
  if (/\.(css|js|png|jpg|jpeg|webp|woff2?|svg|ico)(\?.*)?$/.test(url.pathname)) {
    e.respondWith(
      caches.match(e.request).then(cached => cached ||
        fetch(e.request).then(res => {
          if (res.ok) {
            const clone = res.clone();
            caches.open(STATIC_CACHE).then(c => c.put(e.request, clone));
          }
          return res;
        })
      )
    );
    return;
  }

  // Pagine PHP: network-first, offline fallback
  if (url.pathname.endsWith('.php') || url.pathname === '/') {
    e.respondWith(
      fetch(e.request).catch(() =>
        caches.match('/offline.php').then(r => r || new Response(
          '<h1>Sei offline</h1><p>Controlla la connessione e riprova.</p>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        ))
      )
    );
  }
});
