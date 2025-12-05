<!DOCTYPE html>
<html lang="es" class="h-100" data-mdb-theme="light">

<head>
    <!-- Requiredd meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="manifest" href="{{ secure_asset('manifest.json') }}?v=1.0.0">
    <meta name="theme-color" content="#000000">

    <link rel="shortcut icon" href="{{ secure_asset('front/images/app/icons/icon.png') }}?v=1.0.0" />
    <title>RC Asistencias | Inicio</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ secure_asset('front/vendor/mdboostrap/css/all.min6.0.0.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ secure_asset('front/vendor/mdboostrap/css/mdb.min7.2.0.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ secure_asset('front/vendor/sweetalert/default.css') }}?v=1.0.0">
    <link rel="stylesheet" href="{{ secure_asset('front/css/app/auth.css') }}?v=1.0.0">

    <script src="{{ secure_asset('front/vendor/jquery/jquery.min.js') }}?v=1.0.0"></script>

    <script>
        const intervalToken = setInterval(() => {
            if (!document.cookie.includes('XSRF-TOKEN')) {
                clearInterval(intervalToken);
                location.reload();
            }
        }, 1000);
    </script>
</head>

<body style="height: 100% !important;">

    <nav class="navbar bg-dark-subtle fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand me-0 p-0" href="{{ secure_url('/') }}">
                <div class="logo_rci"></div>
            </a>
            <div class="navbar-brand">
                <span class="text-white me-0">
                    ASISTENCIAS - RCI
                </span>
                <div class="ms-2">
                    <link href="{{ secure_asset('front/layout/swicth_layout.css') }}?v=1.0.0" rel="stylesheet">
                    <input id="check" type="checkbox">
                    <label for="check" class="check-trail">
                        <span class="check-handler"></span>
                    </label>
                    <script src="{{ secure_asset('front/layout/swicth_layout.js') }}?v=1.0.0"></script>
                </div>
            </div>
        </div>
    </nav>

    <div class="content-fluid h-100 d-flex justify-content-center align-items-center">
        <div style="width: 27rem;">
            <div class="card shadow-4-strong m-3">
                <div class="card-body">
                    <form id="form-login" class="m-2">
                        <div class="text-center title-login"></div>

                        <div class="alert alert-danger hidden" role="alert">
                            <i class="fas fa-triangle-exclamation"></i> Usuario incorrecto
                        </div>
                        <!-- Usuario input -->
                        <div class="form-icon icon-usuario my-4">
                            <input type="text" name="login_asist_usuario" id="login_asist_usuario"
                                class="form-control" placeholder="Usuario" autofocus autocomplete="username asistencias"
                                require="usuario">
                        </div>

                        <!-- Password input -->

                        <div class="form-icon icon-contrasena my-4">
                            <input type="password" name="login_asist_password" id="login_asist_password"
                                class="form-control" placeholder="Contraseña" autofocus
                                autocomplete="current-password asistencias" require="Contraseña">
                            <span class="icon-pass"><i class="fas fa-eye-slash"></i></span>
                        </div>

                        <!-- Submit button -->
                        <div class="text-end">
                            <button type="submit" id="btn-ingresar" data-mdb-ripple-init
                                class="btn btn-primary mb-4">Ingresar</button>
                        </div>
                    </form>
                    <div class="text-center border-top mt-2 pt-2">
                        <p class="text-secondary" style="font-size: small;">©{{ date('Y') }} Derechos Reservados.
                            Ricardo
                            Calderon
                            Ingenieros!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ secure_asset('front/vendor/mdboostrap/js/mdb.umd.min7.2.0.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/vendor/sweetalert/sweetalert2@11.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/js/app/AlertMananger.js') }}?v=1.0.0"></script>
    <script>
        const __url = "{{ secure_url('') }}";
        const __token = "{{ csrf_token() }}";
    </script>
    <script src="{{ secure_asset('front/js/auth/auth.js') }}?v=1.0.0"></script>

    <script>
        if ("serviceWorker" in navigator) {
            // 1. Registramos el Service Worker
            navigator.serviceWorker.register("{{ secure_asset('sw.js') }}?v=1.0.0");

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
</body>

</html>
