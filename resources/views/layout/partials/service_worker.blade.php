<script>
if ("serviceWorker" in navigator) {

    navigator.serviceWorker.register("{{ secure_asset($ft_js->service_worker) }}").then(reg => {

        // Detecta si hay un nuevo SW esperando (instalado pero no activo)
        reg.addEventListener("updatefound", () => {
            const newWorker = reg.installing;

            newWorker.addEventListener("statechange", () => {
                // Cuando el nuevo SW está instalado:
                if (newWorker.state === "installed" && navigator.serviceWorker.controller) {

                    // Enviamos mensaje para forzar activación inmediata
                    newWorker.postMessage({ action: "SKIP_WAITING" });
                }
            });
        });
    });

    // Cuando el nuevo SW toma el control:
    let refreshing = false;
    navigator.serviceWorker.addEventListener("controllerchange", () => {
        if (refreshing) return;
        refreshing = true;

        Swal.fire({
            title: '<h6>¡Actualización Disponible!</h6>',
            text: 'Hay una nueva versión del sistema. Es necesario recargar para aplicar los cambios.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, recargar',
            cancelButtonText: 'Más tarde',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload();
            }
        });
    });
}
</script>

<script>
    /*async function pedirPermisoNotificaciones() {
            // Estados posibles: "default", "granted", "denied"
            const estado = Notification.permission;

            if (estado === "granted") {
                return true; // ya tiene permisos
            }

            if (estado === "denied") {
                alert("Las notificaciones están bloqueadas. Actívalas desde los permisos del navegador.");
                return false;
            }

            // Si está en "default", recién pedimos permiso
            const resultado = await Notification.requestPermission();
            return resultado === "granted";
        }

        const solicitarPermisoNotificaciones = async () => {
            try {
                const permiso = await Notification.requestPermission();
                if (permiso !== "granted") {
                    throw new Error("Permiso otorgado");
                }
                return true;
            } catch (err) {
                return false;
            }
        }

        async function permisoNotificaciones() {
            const permiso = await navigator.permissions.query({
                name: "notifications"
            });

            if (permiso.state === "prompt") {
                const ok = await solicitarPermisoNotificaciones();
                if (!ok) {
                    return boxAlert.box({
                        i: "warning",
                        h: "Permiso de las notificaciones denegado."
                    });
                }
            }
        }
        permisoNotificaciones();

        setTimeout(() => {
            new Notification("Título de la notificación", {
                body: "Aquí va el mensaje que quieres mostrar.",
                icon: __asset + "/images/app/icons/icon-192.png"
            });
        }, 10000);*/
</script>