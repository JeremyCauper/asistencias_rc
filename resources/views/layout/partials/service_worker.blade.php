<script>
    if ("serviceWorker" in navigator) {

        navigator.serviceWorker.register("{{ secure_asset($ft_js->service_worker) }}").then(reg => {

            // Detecta si hay un nuevo SW esperando (instalado pero no activo)
            reg.addEventListener("updatefound", () => {
                const newWorker = reg.installing;

                Swal.fire({
                    html: `
                        <h4 style="margin: 0;margin-top:8px;">Actualización disponible</h4><p style="font-size: .8rem">v{{ $ft_version }}</p>
                        <div style="text-align:left">
                            <p>Hemos lanzado una nueva versión de nuestro sistema para mejorar tu experiencia.</p>
                            <p style="margin: 0;">Esta actualización incluye:</p>
                            <ul style="text-align:left; margin-left:20px;">
                                <li><b>Mejoras en la interfaz</b></li>
                                <li><b>Optimización del rendimiento</b></li>
                                <li><b>Correcciones internas</b></li>
                            </ul>
                            <p>La actualización se aplicará <b>automáticamente.</b></p>
                            <p>Por favor no cierre el sistema, <b>espera un momento</b>.</p>

                            <div style="margin:20px 0;display: flex;align-items: center;gap: 5px;">
                                <div id="progressContainer" style="width:100%; background:#eee; border-radius:6px; overflow:hidden;" data-load="reload">
                                    <div id="progressBar" style="width:40%; height:8px; background:#3085d6;border-radius:6px;"></div>
                                </div>
                                <div style="font-size:13px;white-space: nowrap;">
                                    <span id="progressText">0</span> / 100%
                                </div>
                            </div>
                        </div>
                        `,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        const barCon = document.getElementById("progressContainer");
                        const bar = document.getElementById("progressBar");
                        const text = document.getElementById("progressText");
                        let x = -300;

                        const animate = () => {
                            if (barCon.getAttribute("data-load") === "load") {
                                let width = 0;

                                bar.style.transform = 'none';
                                bar.style.width = "0%";

                                const interval = setInterval(() => {
                                    width += Math.random() * 8;

                                    if (width >= 100) width = 100;

                                    bar.style.width = width + "%";
                                    text.innerText = Math.floor(width) + "%";

                                    if (width >= 100) {
                                        clearInterval(interval);
                                        setTimeout(() => window.location.reload(), 300);
                                    }
                                }, 200);
                            } else {
                                x += 15;
                                if (x > 600) x = -300;

                                bar.style.transform = `translateX(${x}px)`;
                                requestAnimationFrame(animate);
                            }
                        };

                        animate();
                    }
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

            document.getElementById("progressContainer").setAttribute("data-load", "load");
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
                if (!("serviceWorker" in navigator)) return;
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
