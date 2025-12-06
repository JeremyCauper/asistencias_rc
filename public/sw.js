const CACHE_NAME = "v1.8";
const OFFLINE_URL = "offline?v=1" ;

self.addEventListener("install", event => {
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then(async cache => {
            let ruta_principal = "front/";

            let biblioteca_front = [
                // IMG
                { file: 'images/app/icons/icon.png', v: "1.2" },
                { file: "images/app/icons/icon-192.png", v: "1.2" },
                { file: "images/app/icons/icon-512.png", v: "1.2" },

                // CSS
                { file: 'vendor/mdboostrap/css/all.min6.0.0.css', v: "1" },
                { file: 'vendor/mdboostrap/css/mdb.min7.2.0.css', v: "1" },
                { file: 'vendor/select/select2.min.css', v: "1" },
                { file: 'vendor/sweetalert/animate.min.css', v: "1" },
                { file: 'vendor/sweetalert/default.css', v: "1" },
                { file: 'vendor/fontGoogle/fonts.css', v: "1" },
                { file: 'layout/layout.css', v: "1.2" },
                { file: 'css/app.css', v: "1" },
                { file: 'layout/swicth_layout.css', v: "1" },
                { file: 'vendor/quill/quill.snow.css', v: "1" },
                { file: 'vendor/daterangepicker/daterangepicker.css', v: "1" },

                // JS
                { file: 'js/app.js', v: "1" },
                { file: 'js/app/AlertMananger.js', v: "1" },
                { file: 'js/app/NotificacionesControl.js', v: "1" },
                { file: 'js/app/FormMananger.js', v: "1" },
                { file: 'js/app/ChartMananger.js', v: "1" },
                { file: 'js/app/MediaViewerControl.js', v: "1" },
                { file: 'js/app/QuillControl.js', v: "1" },
                { file: 'layout/swicth_layout.js', v: "1" },
                { file: 'layout/toggle_template.js', v: "1" },
                { file: 'layout/template.js', v: "1" },
                { file: 'vendor/jquery/jquery.min.js', v: "1" },
                { file: 'vendor/mdboostrap/js/mdb.umd.min7.2.0.js', v: "1" },
                { file: 'vendor/dataTable/jquery.dataTables.min.js', v: "1" },
                { file: 'vendor/sweetalert/sweetalert2@11.js', v: "1" },
                { file: 'vendor/select/select2.min.js', v: "1" },
                { file: 'vendor/select/form_select2.js', v: "1" },
                { file: 'vendor/daterangepicker/moment.min.js', v: "1" },
                { file: 'vendor/daterangepicker/daterangepicker.min.js', v: "1" },
                { file: 'vendor/multiselect/bootstrap.bundle.min.js', v: "1" },
                { file: 'vendor/multiselect/bootstrap_multiselect.js', v: "1" },
                { file: 'vendor/multiselect/form_multiselect.js', v: "1" },
                { file: 'vendor/echartjs/echarts.min.js', v: "1" },
                { file: 'vendor/compression/compressor.min.js', v: "1" },
                { file: 'vendor/quill/quill.min.js', v: "1" },
                { file: 'vendor/exceljs/exceljs.min.js', v: "1" },
                { file: 'vendor/exceljs/FileSaver.min.js', v: "1" },
                { file: 'vendor/full-calendar/full-calendar.min.js', v: "1" },
                { file: 'vendor/inputmask/jquery.inputmask.bundle.min.js', v: "1" },
            ];

            // Construir URLs con versión
            let archivos_finales = biblioteca_front.map(b =>
                `${ruta_principal}${b.file}?v=${b.v}`
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
