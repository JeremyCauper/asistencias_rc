const CACHE_NAME = "v1.1";
const OFFLINE_URL = "offline?v=1" ;

self.addEventListener("install", event => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then(async cache => {
            let ruta_principal = "front/";
            let version = 1.1;

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
