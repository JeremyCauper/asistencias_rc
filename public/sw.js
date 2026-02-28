// =======================================================
// CONFIGURACIÓN
// =======================================================
const VERSION = "3.4.11";
const CACHE_STATIC_NAME = "pwa-static-v" + VERSION;
const CACHE_INMUTABLE_NAME = "pwa-inmutable-v1";
const CACHE_DYNAMIC_NAME = "pwa-dynamic-v1";

// RUTAS DE TU SISTEMA
const RUTA_FRONT = "front/";
const RUTA_BIENVENIDO = "index.html";
const RUTA_OFFLINE = "offline.html";

// Lista de archivos que quieres precachear
const PRECACHE_STATIC_FILES = [

    { file: "index.html" },
    { file: "offline.html" },
    // IMG
    { file: "images/app/icons/icon.webp" },
    { file: "images/app/icons/icon-badge.webp" },
    { file: "images/app/icons/icon-96.webp" },
    { file: "images/app/icons/icon-192.webp" },
    { file: "images/app/icons/icon-512.webp" },

    // CSS
    { file: "css/app.css" },
    { file: "layout/layout.css" },
    { file: "layout/swicth_layout.css" },

    // JS
    { file: "js/app.js" },
    { file: "layout/swicth_layout.js" },
    { file: "layout/toggle_template.js" },
    { file: "layout/template.js" },
];

const PRECACHE_INMUTABLE_FILES = [
    // IMG
    { file: "images/app/LogoRC.webp" },
    { file: "images/app/LogoRC_TBlanco.webp" },
    { file: "images/app/LogoRC_TNegro.webp" },
    { file: "images/app/LogoRC_WBlanco.webp" },
    { file: "images/app/LogoRC_WNormal.webp" },

    // CSS
    { file: "vendor/mdboostrap/css/all.min6.0.0.css" },
    { file: "vendor/mdboostrap/css/mdb.min7.2.0.css" },
    { file: "vendor/select/select2.min.css" },
    { file: "vendor/sweetalert/animate.min.css" },
    { file: "vendor/sweetalert/default.css" },
    { file: "vendor/fontGoogle/fonts.css" },
    { file: "vendor/mdtp/mdtp.min.css" },

    // JS
    { file: "vendor/jquery/jquery.min.js" },
    { file: "vendor/mdboostrap/js/mdb.umd.min7.2.0.js" },
    { file: "vendor/dataTable/jquery.dataTables.min.js" },
    { file: "vendor/sweetalert/sweetalert2@11.js" },
    { file: "vendor/select/select2.min.js" },
    { file: "vendor/select/form_select2.js" },
    { file: "vendor/daterangepicker/moment.min.js" },
    { file: "vendor/daterangepicker/daterangepicker.min.js" },
    { file: "vendor/multiselect/bootstrap.bundle.min.js" },
    { file: "vendor/multiselect/bootstrap_multiselect.js" },
    { file: "vendor/multiselect/form_multiselect.js" },
    { file: "vendor/echartjs/echarts.min.js" },
    { file: "vendor/compression/compressor.min.js" },
    { file: "vendor/quill/quill.min.js" },
    { file: "vendor/exceljs/exceljs.min.js" },
    { file: "vendor/exceljs/FileSaver.min.js" },
    { file: "vendor/full-calendar/full-calendar.min.js" },
    { file: "vendor/inputmask/jquery.inputmask.bundle.min.js" },
    { file: "vendor/mdtp/mdtp.min.js" },
    { file: "front/vendor/pdfjs/pdf-js/pdf.min.js" },
    { file: "front/vendor/pdfjs/pdf-js/pdf.worker.min.js" },
];

// =======================================================
// INSTALACIÓN
// =======================================================
self.addEventListener("install", (event) => {
    console.log('Instalando service worker');
    self.skipWaiting();

    const cacheProm = caches.open(CACHE_STATIC_NAME).then(async cache => {
        let CACHE_STATIC_URLS = ['./'];

        PRECACHE_STATIC_FILES.forEach(b => CACHE_STATIC_URLS.push(`${RUTA_FRONT}${b.file}?v=${VERSION}`));
        await Promise.all(
            CACHE_STATIC_URLS.map(async url => {
                try {
                    await cache.add(url);
                } catch (e) {
                    console.warn('Static no cacheado:', url);
                }
            })
        );
    });

    const cacheInmutable = caches.open(CACHE_INMUTABLE_NAME).then(async cache => {
        let CACHE_INMUTABLE_URLS = PRECACHE_INMUTABLE_FILES.map(b => `${RUTA_FRONT}${b.file}?v=${VERSION}`);

        await Promise.all(
            CACHE_INMUTABLE_URLS.map(async url => {
                try {
                    await cache.add(url);
                } catch (e) {
                    console.warn('Inmutable no cacheado:', url);
                }
            })
        );
    });

    event.waitUntil(Promise.all([cacheProm, cacheInmutable]));
});

// =======================================================
// ACTIVACIÓN Y LIMPIEZA
// =======================================================
self.addEventListener("activate", event => {
    const cacheWhitelist = [CACHE_STATIC_NAME, CACHE_INMUTABLE_NAME];
    const clearOldCaches = caches.keys().then(cacheNames =>
        Promise.all(
            cacheNames.map(cacheName => {
                if (!cacheWhitelist.includes(cacheName)) {
                    console.log("🗑️ Eliminando caché antigua:", cacheName);
                    return caches.delete(cacheName);
                }
            })
        )
    ).then(() => {
        console.log("Clients claimed. Control total activo.");
        return self.clients.claim();
    });

    event.waitUntil(clearOldCaches);
});

// =======================================================
// ESTRATEGIAS DE FETCH
// =======================================================
self.addEventListener("fetch", event => {
    const req = event.request;
    const url = new URL(req.url);

    if (!url.protocol.startsWith("http")) {
        return;
    }

    if (
        req.method !== "GET" ||
        url.pathname.startsWith("/api") ||
        url.pathname.startsWith("/broadcasting") ||
        url.pathname.includes("socket.io") ||
        url.pathname.startsWith("/storage")
    ) {
        return;
    }

    const respuesta = caches.match(event.request)
        .then((res) => {
            // Si la url consultada esta en cache, lo retorna
            if (res) return res;

            return fetch(event.request)
                // .then(newResp => {
                //     // Clonar la respuesta antes de cachear
                //     const clonedResponse = newResp.clone();

                //     caches.delete(CACHE_DYNAMIC_NAME);
                //     caches.open(CACHE_DYNAMIC_NAME)
                //         .then(cache => {
                //             cache.put(event.request, clonedResponse);
                //         });

                //     return newResp;
                // })
                .catch(error => {
                    // Manejar diferentes tipos de errores
                    console.error('Error en fetch:', error);

                    // Si es una navegación (página), mostrar página offline
                    if (event.request.mode === 'navigate') {
                        // return caches.match(RUTA_FRONT + RUTA_OFFLINE)
                        return caches.match(`${RUTA_FRONT}${RUTA_OFFLINE}?v=${VERSION}`)
                            .then(offlinePage => {
                                console.log(offlinePage);

                                if (offlinePage) {
                                    return offlinePage;
                                }
                            });
                    }

                    // Respuesta genérica de error
                    return new Response('Sin conexión a internet', {
                        status: 503,
                        statusText: 'Service Unavailable'
                    });
                });
        });

    event.respondWith(respuesta);
});


// MANTENIMIENTO y NOTIFICACIONES (tu código IndexedDB y Push está bien, lo mantuve)
self.addEventListener("message", event => {
    if (event.data && event.data.action === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

// IndexedDB helper (simple y compacto)
const DB_NAME = "pushCountersDB";
const STORE_NAME = "countersStore";

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, 1);

        request.onupgradeneeded = event => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME);
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function getCount(tag) {
    const db = await openDB();
    return new Promise(resolve => {
        const tx = db.transaction(STORE_NAME, "readonly");
        const store = tx.objectStore(STORE_NAME);
        const req = store.get(tag);
        req.onsuccess = () => resolve(req.result || 0);
        req.onerror = () => resolve(0);
    });
}

async function setCount(tag, value) {
    const db = await openDB();
    return new Promise(resolve => {
        const tx = db.transaction(STORE_NAME, "readwrite");
        const store = tx.objectStore(STORE_NAME);
        store.put(value, tag);
        tx.oncomplete = () => resolve(true);
    });
}

// NOTIFICACIONES --------------------------------------------------
self.addEventListener("push", event => {
    console.log("push recibido", event.data.text());
    event.waitUntil(handlePush(event));
});

async function handlePush(event) {
    const data = event.data.json();
    const tag = data.tag || "default";

    // Obtener contador persistente
    let count = await getCount(tag);
    count++;

    // Guardar nuevo valor
    await setCount(tag, count);

    // Notificación combinada
    const title = count === 1
        ? data.title
        : `${data.title} (${count})`;

    const body = count === 1
        ? data.body
        : `Tienes ${count} notificaciones de ${tag} pendientes.`;

    return self.registration.showNotification(title, {
        body,
        icon: data.icon192,
        badge: data.badge,
        vibrate: [100, 50, 100],
        tag,
        renotify: true,   // combina notificaciones del mismo tag
        data: {
            url: data.url,
            tag,
            count
        }
    });
}

// Manejador de cierre ----------------------------------------------
self.addEventListener("notificationclose", event => {
    const tag = event.notification.data?.tag || "default";

    setCount(tag, 0);
});

// Manejador de notificaciones de click -----------------------------
self.addEventListener("notificationclick", event => {
    event.notification.close();

    const { url, tag } = event.notification.data || {};
    if (!url) return;

    setCount(tag || "default", 0);

    event.waitUntil((async () => {
        const urlToOpen = new URL(url);
        const clientsList = await clients.matchAll({ type: "window", includeUncontrolled: true });

        for (const client of clientsList) {
            const clientURL = new URL(client.url);

            // Coincidencia de dominio
            const sameOrigin = clientURL.origin === urlToOpen.origin;

            if (!sameOrigin) continue;

            // Si el path coincide exacto → lo enfocas
            if (clientURL.href === urlToOpen.href && "focus" in client) {
                return client.focus();
            }

            // Si es mismo host pero diferente path → lo rediriges dentro del mismo cliente
            if ("navigate" in client) {
                client.navigate(url);
                return client.focus();
            }
        }

        // No existe ventana → abre PWA o pestaña según corresponda
        return clients.openWindow(url);
    })()
    );
});