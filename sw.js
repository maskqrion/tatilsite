const CACHE_NAME = 'seckin-rotalar-cache-v3'; // Önbellek sürümü güncellendi
const urlsToCache = [
  '/',
  '/index.html',
  '/rotalar.html',
  '/hakkinda.html',
  '/iletisim.html',
  '/genel-bilgiler.html',
  '/style.css',
  '/main.js',
  '/anasayfa.js',
  '/rotalar.js',
  '/favori.js',
  'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700;800&display=swap'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Önbellek açıldı');
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
    // API çağrıları için Stale-While-Revalidate stratejisi
    if (event.request.url.includes('getir.php') || event.request.url.includes('islemleri.php')) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return cache.match(event.request).then(cachedResponse => {
                    const fetchPromise = fetch(event.request).then(networkResponse => {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                    return cachedResponse || fetchPromise;
                });
            })
        );
        return;
    }

    // Google Fonts gibi dış kaynaklar için ağ öncelikli strateji (mevcut koddan)
    if (event.request.url.startsWith('https://fonts.gstatic.com')) {
        event.respondWith(
            caches.open(CACHE_NAME).then(cache => {
                return fetch(event.request).then(response => {
                    cache.put(event.request, response.clone());
                    return response;
                }).catch(() => {
                    return caches.match(event.request);
                });
            })
        );
        return;
    }

    // Geri kalanlar için önbellek öncelikli strateji (mevcut koddan)
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});

self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});