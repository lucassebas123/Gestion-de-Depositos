const CACHE_NAME = 'gestor-insumos-v1';
const ASSETS_TO_CACHE = [
  './',
  'style.css',
  'logo1.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11'
];

// 1. Instalación: Cachear recursos estáticos
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(ASSETS_TO_CACHE))
  );
});

// 2. Activación: Limpiar cachés viejos
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }));
    })
  );
});

// 3. Intercepción de peticiones (Estrategia: Network First)
// Intentamos ir a la red para tener datos frescos (PHP). Si falla, buscamos en caché.
self.addEventListener('fetch', (event) => {
  // Solo interceptamos peticiones GET
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Si la respuesta es válida, la clonamos y actualizamos el caché
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }
        const responseToCache = response.clone();
        caches.open(CACHE_NAME)
          .then((cache) => {
            // No cacheamos las APIs ni las vistas dinámicas pesadas, solo assets
            if (event.request.url.match(/\.(css|js|png|jpg|svg|woff2)$/)) {
                cache.put(event.request, responseToCache);
            }
          });
        return response;
      })
      .catch(() => {
        // Si falla la red (offline), intentamos servir desde caché
        return caches.match(event.request);
      })
  );
});