const VERSION_CACHE = 1;
const CACHE_NAME = "v" + VERSION_CACHE;
const OFFLINE_URL = "offline?v=1";

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

// INSTALACIÓN -----------------------------------------------------
self.addEventListener("install", event => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then(async cache => {
            let ruta_principal = "front/";
            let version = CACHE_NAME;

            let biblioteca_front = [
                // IMG
                { file: 'images/app/icons/icon.png' },
                { file: "images/app/icons/icon-192.png" },
                { file: "images/app/icons/icon-512.png" },

                // CSS
                { file: 'vendor/mdboostrap/css/all.min6.0.0.css' },
                { file: 'vendor/mdboostrap/css/mdb.min7.2.0.css' },
                { file: 'vendor/select/select2.min.css' },
                { file: 'vendor/sweetalert/animate.min.css' },
                { file: 'vendor/sweetalert/default.css' },
                { file: 'vendor/fontGoogle/fonts.css' },
                { file: 'layout/layout.css' },
                { file: 'css/app.css' },
                { file: 'layout/swicth_layout.css' },
                { file: 'vendor/quill/quill.snow.css' },
                { file: 'vendor/daterangepicker/daterangepicker.css' },

                // JS
                { file: 'js/app.js' },
                { file: 'js/app/AlertMananger.js' },
                { file: 'js/app/NotificacionesControl.js' },
                { file: 'js/app/FormMananger.js' },
                { file: 'js/app/ChartMananger.js' },
                { file: 'js/app/MediaViewerControl.js' },
                { file: 'js/app/QuillControl.js' },
                { file: 'layout/swicth_layout.js' },
                { file: 'layout/toggle_template.js' },
                { file: 'layout/template.js' },
                { file: 'vendor/jquery/jquery.min.js' },
                { file: 'vendor/mdboostrap/js/mdb.umd.min7.2.0.js' },
                { file: 'vendor/dataTable/jquery.dataTables.min.js' },
                { file: 'vendor/sweetalert/sweetalert2@11.js' },
                { file: 'vendor/select/select2.min.js' },
                { file: 'vendor/select/form_select2.js' },
                { file: 'vendor/daterangepicker/moment.min.js' },
                { file: 'vendor/daterangepicker/daterangepicker.min.js' },
                { file: 'vendor/multiselect/bootstrap.bundle.min.js' },
                { file: 'vendor/multiselect/bootstrap_multiselect.js' },
                { file: 'vendor/multiselect/form_multiselect.js' },
                { file: 'vendor/echartjs/echarts.min.js' },
                { file: 'vendor/compression/compressor.min.js' },
                { file: 'vendor/quill/quill.min.js' },
                { file: 'vendor/exceljs/exceljs.min.js' },
                { file: 'vendor/exceljs/FileSaver.min.js' },
                { file: 'vendor/full-calendar/full-calendar.min.js' },
                { file: 'vendor/inputmask/jquery.inputmask.bundle.min.js' },
            ];

            // Construir URLs con versión
            let archivos_finales = biblioteca_front.map(b =>
                `${ruta_principal}${b.file}?v=${version}`
            );

            // Página offline
            archivos_finales.push(OFFLINE_URL);

            console.log("⏳ Intentando cachear archivos...");

            // Mejorado: evita que un archivo que falla tumbe todo addAll
            await Promise.all(
                archivos_finales.map(url =>
                    cache.add(url).catch(err =>
                        console.warn("No se pudo cachear:", url)
                    )
                )
            );

            console.log("✅ Instalación completada");
        })
    );
});

// ACTIVACIÓN Y LIMPIEZA -------------------------------------------
self.addEventListener("activate", event => {
    const cacheWhitelist = [CACHE_NAME];

    event.waitUntil(
        caches.keys().then(cacheNames =>
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
        })
    );
});

// FETCH -----------------------------------------------------------
self.addEventListener("fetch", event => {
    event.respondWith(
        caches.match(event.request).then(cached => {
            if (cached) return cached;

            return fetch(event.request).catch(() => {
                const accept = event.request.headers.get("accept") || "";

                const esHTML =
                    event.request.mode === "navigate" ||
                    (event.request.method === "GET" &&
                        accept.includes("text/html"));

                if (esHTML) {
                    return caches.match(OFFLINE_URL);
                }
            });
        })
    );
});

// MANTENIMIENTO ---------------------------------------------------
self.addEventListener("message", event => {
    if (event.data && event.data.action === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

// NOTIFICACIONES --------------------------------------------------
self.addEventListener("push", event => {
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