const CACHE_NAME = 'qpos-sw-v9';
const STATIC_EXT = /\.(?:js|css|png|jpg|jpeg|gif|webp|svg|ico|woff2?|ttf|eot|map)(?:\?.*)?$/i;

self.addEventListener('install', function (event) {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (names) {
      return Promise.all(
        names.map(function (name) {
          if (name !== CACHE_NAME) {
            return caches.delete(name);
          }
        })
      );
    }).then(function () {
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', function (event) {
  var request = event.request;
  var url;

  try {
    url = new URL(request.url);
  } catch (e) {
    return;
  }

  // Never touch non-GET (form POSTs / PUT / DELETE must hit the server)
  if (request.method !== 'GET') {
    return;
  }

  // Ignore extensions / analytics / cross-origin noise
  if (url.protocol === 'chrome-extension:' || url.hostname.indexOf('cloudflareinsights.com') !== -1) {
    return;
  }

  var accept = request.headers.get('Accept') || '';
  var isDocument =
    request.mode === 'navigate' ||
    accept.indexOf('text/html') !== -1;

  // HTML pages: always network — never cache (fixes "settings not updating")
  if (isDocument) {
    event.respondWith(
      fetch(request).catch(function () {
        return caches.match(request);
      })
    );
    return;
  }

  // API / AJAX: network only
  if (
    request.headers.get('X-Requested-With') === 'XMLHttpRequest' ||
    accept.indexOf('application/json') !== -1 ||
    url.pathname.indexOf('/sells/pos/') !== -1
  ) {
    event.respondWith(
      fetch(request).catch(function () {
        return new Response(
          JSON.stringify({
            success: 0,
            msg: 'You are offline.',
            offline: true,
          }),
          { headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  // Static assets only: cache-first
  if (!STATIC_EXT.test(url.pathname)) {
    return; // let browser handle normally
  }

  event.respondWith(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.match(request).then(function (cached) {
        var networkFetch = fetch(request)
          .then(function (response) {
            if (response && response.status === 200 && response.type === 'basic') {
              cache.put(request, response.clone());
            }
            return response;
          })
          .catch(function () {
            return cached;
          });
        return cached || networkFetch;
      });
    })
  );
});
