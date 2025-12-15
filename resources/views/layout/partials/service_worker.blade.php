<script>
    window.addEventListener('load', async () => {
        if ("serviceWorker" in navigator) {

            const alertaActualizacion = (finish = false) => {
                if (finish) {
                    const progressContainer = document.getElementById("progressContainer");
                    if (progressContainer)
                        progressContainer.setAttribute("data-load", "load");
                    return;
                }
                Swal.fire({
                    html: `
                    <h4 style="margin: 0;margin-top:8px;color: #e4a11b;">Actualización disponible</h4>
                    <p style="font-size: .8rem;font-weight: 600;">v{{ $ft_version }}</p>

                    <div style="text-align:left;font-size: .825rem;color: #878787;">
                        <p>Hemos lanzado una nueva versión de nuestro sistema para mejorar tu experiencia.</p>
                        <p style="margin: 0;">Esta actualización incluye:</p>
                        <ul style="margin-left:20px;">
                            <li><b>Mejoras en la interfaz</b></li>
                            <li><b>Optimización del rendimiento</b></li>
                            <li><b>Correcciones internas</b></li>
                        </ul>
                        <p>La actualización se aplicará <b>automáticamente.</b></p>
                        <p>Por favor no cierre el sistema, <b>espere un momento</b>.</p>

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
                        const barCon = document.getElementById(
                            "progressContainer");
                        const bar = document.getElementById("progressBar");
                        const text = document.getElementById("progressText");
                        let x = -300;

                        const animate = () => {
                            if (barCon.getAttribute("data-load") ===
                                "load") {
                                let width = 0;

                                bar.style.transform = 'none';
                                bar.style.width = "0%";

                                const interval = setInterval(() => {
                                    width += Math.random() * 8;

                                    if (width >= 100) width = 100;

                                    bar.style.width = width + "%";
                                    text.innerText = Math.floor(
                                        width) + "%";

                                    if (width >= 100) {
                                        clearInterval(interval);
                                        setTimeout(() => window
                                            .location
                                            .reload(), 300);
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
            }

            navigator.serviceWorker.register("{{ secure_asset($ft_js->service_worker) }}")
                .then(reg => {
                    console.log("[SW] Registrado correctamente");

                    // 1️⃣ CASO: PRIMERA INSTALACIÓN
                    if (!navigator.serviceWorker.controller) {
                        console.log("[SW] Primera vez instalado");
                    }

                    // 2️⃣ CASO: YA HAY UN SW NUEVO ESPERANDO => (app abierta mucho tiempo)
                    if (reg.waiting && navigator.serviceWorker.controller) {
                        alertaActualizacion();
                        console.log(
                            "[SW] Nueva actualización detectada (waiting), Actualización en proceso"
                        );
                    }

                    // 3️⃣ ESCUCHAR NUEVAS ACTUALIZACIONES
                    reg.addEventListener("updatefound", () => {
                        const newWorker = reg.installing;

                        console.log("[SW] Nuevo Service Worker encontrado");
                        alertaActualizacion();

                        newWorker.addEventListener("statechange", () => {

                            switch (newWorker.state) {

                                case "installing":
                                    if (navigator.serviceWorker.controller) {
                                        console.log("[SW] Actualización en proceso");
                                    }
                                    break;

                                case "installed":
                                    if (!navigator.serviceWorker.controller) {
                                        console.log(
                                            "[SW] Final de instalación inicial");
                                    } else {
                                        console.log(
                                            "[SW] Actualización finalizada (instalada, esperando activación)"
                                        );
                                        newWorker.postMessage({
                                            action: "SKIP_WAITING"
                                        });
                                    }
                                    break;

                                case "activated":
                                    console.log("[SW] Service Worker activado");
                                    alertaActualizacion(true);
                                    break;
                            }
                        });
                    });
                });

            // 4️⃣ CUANDO EL NUEVO SW TOMA CONTROL
            navigator.serviceWorker.addEventListener("controllerchange", () => {
                console.log("[SW] Nuevo Service Worker tomó el control, Recarga");
                alertaActualizacion(true);
            });

            @if (Auth::check())
                (async () => {
                    // Espera a que exista un service worker ACTIVO
                    const reg = await navigator.serviceWorker.ready;

                    // Pedir permiso
                    const permiso_notificacion = await Notification.requestPermission();
                    if (permiso_notificacion !== "granted") return;

                    // Convertir clave VAPID
                    function urlBase64ToUint8Array(base64String) {
                        const padding = '='.repeat((4 - base64String.length % 4) % 4);
                        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g,
                            '/');
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

                    // Solicitar permiso de camara
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

                    const permiso_camera = await navigator.permissions.query({
                        name: "camera"
                    });

                    if (permiso_camera.state === "prompt") {
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

                    if (permiso_camera.state === "denied") {
                        return boxAlert.box({
                            i: "warning",
                            h: "Acceso a la cámara denegado, debe desbloquearlo desde los ajustes del navegador."
                        });
                    }

                    // granted
                    console.log("Permiso otorgado");
                })();
            @endif
        }
    });
</script>
