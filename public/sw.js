const CACHE_NAME = "v1.0.0";
const OFFLINE_URL = "offline";

self.addEventListener("install", event => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            let ruta_principal = "front/";

            let biblioteca_front = [
                { file: "css/app.css", ver: "1.0.0" },
                { file: "js/app.js", ver: "1.0.0" },
                { file: "images/app/icons/icon.png", ver: "1.0.0" },
                { file: "images/app/icons/icon-192.png", ver: "1.0.0" },
                { file: "images/app/icons/icon-512.png", ver: "1.0.0" },

                { file: "layout/layout.css", ver: "1.0.0" },
                { file: "layout/swicth_layout.css", ver: "1.0.0" },
                { file: "layout/swicth_layout.js", ver: "1.0.0" },
                { file: "layout/template.js", ver: "1.0.0" },
                { file: "layout/toggle_template.js", ver: "1.0.0" },

                { file: "vendor/fontGoogle/fonts.css", ver: "1.0.0" },
                { file: "vendor/dataTable/jquery.dataTables.min.js", ver: "1.0.0" },
                { file: "vendor/sweetalert/animate.min.css", ver: "1.0.0" },
                { file: "vendor/sweetalert/default.css", ver: "1.0.0" },
                { file: "vendor/sweetalert/sweetalert2@11.js", ver: "1.0.0" },
                { file: "vendor/jquery/jquery.min.js", ver: "1.0.0" },
                { file: "vendor/mdboostrap/css/all.min6.0.0.css", ver: "1.0.0" },
                { file: "vendor/mdboostrap/css/mdb.min7.2.0.css", ver: "1.0.0" },
                { file: "vendor/mdboostrap/js/mdb.umd.min7.2.0.js", ver: "1.0.0" },
            ];

            // Generamos las URLS
            let archivos_finales = biblioteca_front.map(biblioteca => {
                return `${ruta_principal}${biblioteca.file}?v=${biblioteca.ver}`
            });

            // Agregamos la URL offline
            archivos_finales.push(OFFLINE_URL);

            console.log("⏳ Intentando cachear archivos:");

            return cache.addAll(archivos_finales)
                .then(() => console.log("✅ Instalación completada con éxito"))
                .catch(err => console.error("❌ ERROR CRÍTICO EN INSTALACIÓN:", err));
        })
    );
});

self.addEventListener("activate", event => {
    const cacheWhitelist = [CACHE_NAME];

    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    // Si la caché no es la actual, la borramos
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        console.log("🗑️ Borrando caché antigua:", cacheName);
                        return caches.delete(cacheName); 
                        // AQUÍ BORRAMOS EL .then(() => window.location.reload())
                    }
                })
            );
        }).then(() => {
            // Esto es suficiente para tomar el control sin recargar
            console.log("Clients claimed - El SW ya controla la página");
            return self.clients.claim();
        })
    );
});

self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request).then(response => {
            // 1. Si está en caché, lo devolvemos
            if (response) {
                return response;
            }

            // 2. Si no, vamos a internet
            return fetch(event.request).catch(error => {
                // 3. Fallo de red (Offline)
                console.log("⚠️ Fallo de red detectado en:", event.request.url);

                if (event.request.mode === 'navigate' ||
                    (event.request.method === 'GET' && event.request.headers.get('accept').includes('text/html'))) {

                    return caches.match(OFFLINE_URL);
                }
            });
        })
    );
});
// ... Todo tu código anterior (install, activate, fetch) ...

/*// 1. ESCUCHAR EL MENSAJE DEL SERVIDOR (PUSH)
self.addEventListener('push', event => {
    console.log('[Service Worker] Push recibido.');

    let data = { title: 'Nuevo Mensaje', body: 'Tienes una notificación', url: '/' };

    if (event.data) {
        // Si Laravel mandó JSON, lo parseamos
        try {
            data = event.data.json();
        } catch (e) {
            console.log("El push no es JSON");
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: 'front/images/app/icons/icon-192.png', // Ajusta tu ruta
        badge: 'front/images/app/icons/icon-192.png', // Icono pequeño en barra de estado
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/' // Guardamos la URL para usarla al hacer clic
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// 2. ESCUCHAR EL CLIC EN LA NOTIFICACIÓN
self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] Notificación clickeada.');

    event.notification.close(); // Cierra la notificación

    event.waitUntil(
        // Abrir la ventana o enfocarla si ya está abierta
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            // Intentamos ver si la URL ya está abierta para enfocarla
            const urlToOpen = event.notification.data.url;
            
            for (const client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // Si no está abierta, abrimos una nueva
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});*/