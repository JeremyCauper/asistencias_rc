<script>
    if ("serviceWorker" in navigator) {
        // 1. Registramos el Service Worker
        navigator.serviceWorker.register("{{ secure_asset($ft_js->service_worker) }}");

        // 2. Variable para evitar que la página se recargue en bucle infinito
        let refreshing = false;

        // 3. Escuchamos el evento "controllerchange"
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) return;
            refreshing = true;

            // 4. Mostramos la Alerta de SweetAlert
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
