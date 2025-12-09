<script>
    if ("serviceWorker" in navigator) {

        navigator.serviceWorker.register("{{ secure_asset($ft_js->service_worker) }}").then(reg => {

            // Detecta si hay un nuevo SW esperando (instalado pero no activo)
            reg.addEventListener("updatefound", () => {
                const newWorker = reg.installing;

                Swal.fire({
                    title: '<h4>Actualizando la aplicación…</h4>',
                    html: '<p>Descargando la última versión. Esto tomará solo un momento.</p>',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false
                });

                newWorker.addEventListener("statechange", () => {
                    if (newWorker.state === "installed" && navigator.serviceWorker.controller) {
                        newWorker.postMessage({
                            action: "SKIP_WAITING"
                        });
                    }
                });
            });
        });

        // Cuando el nuevo SW toma el control:
        let refreshing = false;
        navigator.serviceWorker.addEventListener("controllerchange", () => {
            if (refreshing) return;
            refreshing = true;

            let fakeProgress = 0;

            Swal.fire({
                title: '<h4>Actualizando…</h4>',
                html: `
            <p>Aplicando la nueva versión del sistema.</p>
            <div id="progressContainer" style="margin-top:10px; width:100%; background:#eee; border-radius:6px; overflow:hidden;">
                <div id="progressBar" style="width:0%; height:8px; background:#3085d6"></div>
            </div>
            <p id="progressText" style="margin-top:8px; font-size:13px;">0%</p>
        `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    const bar = document.getElementById("progressBar");
                    const text = document.getElementById("progressText");

                    const interval = setInterval(() => {
                        fakeProgress += Math.random() * 8;

                        if (fakeProgress >= 100) fakeProgress = 100;

                        bar.style.width = fakeProgress + "%";
                        text.innerText = Math.floor(fakeProgress) + "%";

                        if (fakeProgress >= 100) {
                            clearInterval(interval);
                            setTimeout(() => window.location.reload(), 300);
                        }
                    }, 200);
                }
            });
        });
    }
</script>
@if (Auth::check())
    <script>
        (async () => {
            if (!("serviceWorker" in navigator)) return;
            // Espera a que exista un service worker ACTIVO
            const reg = await navigator.serviceWorker.ready;

            // Pedir permiso
            const permiso = await Notification.requestPermission();
            if (permiso !== "granted") return;

            // Convertir clave VAPID
            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                const rawData = atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }

            const clave = urlBase64ToUint8Array("{{ env('VAPID_PUBLIC_KEY') }}");

            // Crear suscripción
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: clave
            });

            // Enviar al backend
            await fetch(__url + "/push/subscribe", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    'X-CSRF-TOKEN': __token,
                },
                body: JSON.stringify({
                    ...sub.toJSON(),
                    origin: window.location.origin
                })
            });

            console.log("Push registrado ✔");
        })();

        (async () => {
            const solicitarPermisoCamara = async () => {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: true
                    });
                    stream.getTracks().forEach(t => t.stop());
                    return true;
                } catch (err) {
                    return false;
                }
            }

            const permiso = await navigator.permissions.query({
                name: "camera"
            });

            if (permiso.state === "prompt") {
                const ok = await solicitarPermisoCamara();

                // Revisar nuevamente el estado después de pedir permiso  
                const post = await navigator.permissions.query({
                    name: "camera"
                });

                if (!ok || post.state === "denied") {
                    return boxAlert.box({
                        i: "warning",
                        h: "Se denegó el acceso a la cámara."
                    });
                }
            }

            if (permiso.state === "denied") {
                return boxAlert.box({
                    i: "warning",
                    h: "Acceso a la cámara denegado, debe desbloquearlo desde los ajustes del navegador."
                });
            }

            // granted
            console.log("Permiso otorgado");
        })();
    </script>
@endif
