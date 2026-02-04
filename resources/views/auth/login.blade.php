<!DOCTYPE html>
<html lang="es" class="h-100" data-mdb-theme="light">

<head>
    <!-- Requiredd meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="shortcut icon" href="{{ secure_asset($ft_img->icon) }}" />

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="{{ secure_asset($ft_json->manifest) }}">
    <meta name="theme-color" content="#000000">

    <title>Asistencias RC | Inicio</title>

    <!-- Para iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Asistencias">
    <link rel="apple-touch-icon" href="{{ secure_asset($ft_img->icon_192) }}">
    
    <!-- Para Windows -->
    <meta name="msapplication-TileImage" content="{{ secure_asset($ft_img->icon_192) }}">
    <meta name="msapplication-TileColor" content="#000000">

    <!-- Font Awesome -->
    <link href="{{ secure_asset($ft_css->mdb_all_min6_0_0) }}" rel="stylesheet">
    <!-- MDB -->
    <link href="{{ secure_asset($ft_css->mdb_min7_2_0) }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ secure_asset($ft_css->sweet_default) }}">
    <link rel="stylesheet" href="{{ secure_asset('front/css/app/auth.css') }}?v={{ config('app.version') }}">

    <script src="{{ secure_asset($ft_js->jquery) }}"></script>

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

    <nav class="navbar fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand me-0 p-0" href="{{ secure_url('/') }}">
                <div class="logo_rci"></div>
            </a>
            <div class="navbar-brand">
                <div class="me-2">
                    <span class="me-0">
                        ASISTENCIAS - RCI
                    </span>
                </div>
                {{-- Switch Layout --}}
                @include('layout.partials.swicth_layout')
            </div>
        </div>
    </nav>

    <div class="content-fluid h-100 d-flex justify-content-center align-items-center">
        <div style="width: 27rem;">
            <div class="card m-3">
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

    <script type="text/javascript" src="{{ secure_asset($ft_js->mdb_umd_min7_2_0) }}"></script>
    <script src="{{ secure_asset($ft_js->sweet_sweetalert2) }}"></script>
    <script src="{{ secure_asset($ft_js->AlertMananger) }}"></script>
    <script>
        const __url = "{{ secure_url('') }}";
        const __token = "{{ csrf_token() }}";
    </script>
    <script src="{{ secure_asset('front/js/auth/auth.js') }}?v={{ config('app.version') }}"></script>

    @include('layout.partials.service_worker')
</body>

</html>
