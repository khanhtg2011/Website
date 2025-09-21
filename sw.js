// Service Worker for Photo Gallery Performance Optimization
const CACHE_NAME = 'photo-gallery-v2';
const STATIC_CACHE = 'photo-gallery-static-v2';
const IMAGE_CACHE = 'photo-gallery-images-v2';

// Resources to cache immediately
const STATIC_ASSETS = [
  '/',
  '/index.php',
  '/style.css',
  '/favicon.png',
  '/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => {
        console.log('Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME && cacheName !== STATIC_CACHE && cacheName !== IMAGE_CACHE) {
            console.log('Service Worker: Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Handle image requests with special caching strategy
  if (event.request.destination === 'image' || url.pathname.match(/\.(jpg|jpeg|png|gif|webp)$/)) {
    event.respondWith(handleImageRequest(event.request));
    return;
  }

  // Handle API requests
  if (url.pathname.includes('/list.php') || url.pathname.includes('/albums.php')) {
    event.respondWith(handleApiRequest(event.request));
    return;
  }

  // Handle static assets
  if (STATIC_ASSETS.includes(url.pathname) || url.pathname.match(/\.(css|js|json)$/)) {
    event.respondWith(handleStaticRequest(event.request));
    return;
  }

  // Default network-first strategy for other requests
  event.respondWith(
    fetch(event.request)
      .catch(() => {
        // Fallback to cache if network fails
        return caches.match(event.request);
      })
  );
});

// Handle image requests with cache-first strategy
async function handleImageRequest(request) {
  const cache = await caches.open(IMAGE_CACHE);
  const cachedResponse = await cache.match(request);

  if (cachedResponse) {
    // Return cached version and update in background
    fetch(request).then(networkResponse => {
      if (networkResponse.ok) {
        // Only cache requests with supported schemes (http/https)
        if (request.url.startsWith('http://') || request.url.startsWith('https://')) {
          cache.put(request, networkResponse.clone());
        }
      }
    }).catch(() => {
      // Network failed, keep cached version
    });

    return cachedResponse;
  }

  // Not in cache, fetch from network
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      // Only cache requests with supported schemes (http/https)
      if (request.url.startsWith('http://') || request.url.startsWith('https://')) {
        cache.put(request, networkResponse.clone());
      }
    }
    return networkResponse;
  } catch (error) {
    // Network failed, try to serve a placeholder
    return new Response(
      'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk9mZmxpbmU8L3RleHQ+PC9zdmc+',
      {
        headers: { 'Content-Type': 'image/svg+xml' }
      }
    );
  }
}

// Handle API requests with network-first strategy
async function handleApiRequest(request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      // Only cache GET requests (POST requests cannot be cached)
      if (request.method === 'GET' && (request.url.startsWith('http://') || request.url.startsWith('https://'))) {
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, networkResponse.clone());
      }
    }
    return networkResponse;
  } catch (error) {
    // Network failed, try cache (only for GET requests)
    if (request.method === 'GET') {
      const cachedResponse = await caches.match(request);
      if (cachedResponse) {
        return cachedResponse;
      }
    }

    // Return offline response
    return new Response(JSON.stringify({
      error: 'Offline',
      message: 'Content not available offline'
    }), {
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Handle static assets with cache-first strategy
async function handleStaticRequest(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cachedResponse = await cache.match(request);

  if (cachedResponse) {
    return cachedResponse;
  }

  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      // Only cache requests with supported schemes (http/https)
      if (request.url.startsWith('http://') || request.url.startsWith('https://')) {
        cache.put(request, networkResponse.clone());
      }
    }
    return networkResponse;
  } catch (error) {
    return new Response('Resource not available', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Background sync for failed uploads
self.addEventListener('sync', event => {
  if (event.tag === 'background-upload') {
    event.waitUntil(processBackgroundUploads());
  }
});

async function processBackgroundUploads() {
  // Process any queued uploads when connection is restored
  const cache = await caches.open('upload-queue');
  const requests = await cache.keys();

  for (const request of requests) {
    try {
      await fetch(request);
      await cache.delete(request);
    } catch (error) {
      console.log('Background upload failed:', error);
    }
  }
}

// Push notifications for new photos (if implemented)
self.addEventListener('push', event => {
  if (event.data) {
    const data = event.data.json();
    const options = {
      body: data.body,
      icon: '/favicon.png',
      badge: '/favicon.png',
      data: data.url
    };

    event.waitUntil(
      self.registration.showNotification(data.title, options)
    );
  }
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  event.notification.close();

  event.waitUntil(
    clients.openWindow(event.notification.data || '/')
  );
});