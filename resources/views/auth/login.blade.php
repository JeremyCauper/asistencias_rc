<!DOCTYPE html>
<html lang="es" class="h-100" data-mdb-theme="light">

<head>
    <!-- Requiredd meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="{{secure_asset('front/images/app/LogoRC.png')}}" />
    <title>RC Asistencias | Inicio</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{secure_asset('front/vendor/mdboostrap/css/all.min6.0.0.css')}}">
    <link rel="stylesheet" href="{{secure_asset('front/vendor/mdboostrap/css/mdb.min7.2.0.css')}}">
    <link rel="stylesheet" href="{{secure_asset('front/vendor/sweetalert/default.css')}}">
    <link rel="stylesheet" href="{{secure_asset('front/css/app/auth.css')}}">

    <script src="{{secure_asset('front/vendor/jquery/jquery.min.js')}}"></script>

    <link rel="stylesheet" href="{{secure_asset('front/css/tema.css')}}">
    <script src="{{secure_asset('front/js/app/ToggleTema.js')}}"></script>
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
            <a class="navbar-brand me-0 p-0" href="{{secure_url('/soporte')}}">
                <div class="logo_rci"></div>
            </a>
            <div class="navbar-brand">
                <span class="text-white me-0">
                    ASISTENCIAS - RCI
                </span>
                <div class="ms-2">
                    <input id="check" type="checkbox">
                    <label for="check" class="check-trail">
                        <span class="check-handler"></span>
                    </label>
                    <script>
                        if (!localStorage.hasOwnProperty('data_mdb_theme') || !localStorage.data_mdb_theme) {
                            localStorage.setItem('data_mdb_theme', 'light');
                        }
                        $('html').attr('data-mdb-theme', localStorage.data_mdb_theme);

                        $('#check').prop('checked', localStorage.data_mdb_theme == 'light' ? true : false);
                        if (!esCelularTema()) {
                            $('.check-trail').append(`<span class="badge badge-secondary toltip-theme">
                                <b class="fw-bold">Shift</b><i class="fas fa-plus fa-2xs text-white"></i> <b class="fw-bold">D</b>
                            </span>`);
                        }
                    </script>
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
                            <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Usuario"
                                autofocus require="usuario">
                        </div>

                        <!-- Password input -->
                        <!-- <div class="input-group form-outline my-4" style="padding: .1152rem;" data-mdb-input-init aria-cinput="contraseña">
                            <span class="input-group-text border-0 px-2" for="contrasena"><i class="fas fa-key"></i></span>
                            <input type="password" name="password" id="contrasena" class="form-control border-start-0 ps-1" placeholder="Contraseña" autofocus require="contraseña">
                            <span class="input-group-text border-0 px-2" style="padding-top: 7px;"><i class="fas fa-eye-slash" id="icon-pass"></i></span>
                        </div> -->
                        <div class="form-icon icon-contrasena my-4">
                            <input type="password" name="clave" id="clave" class="form-control"
                                placeholder="Contraseña" autofocus require="contraseña">
                            <span class="icon-pass"><i class="fas fa-eye-slash"></i></span>
                        </div>

                        <!-- Submit button -->
                        <div class="text-end">
                            <button type="submit" id="btn-ingresar" data-mdb-ripple-init
                                class="btn btn-primary mb-4">Ingresar</button>
                        </div>
                    </form>
                    <div class="text-center border-top mt-2 pt-2">
                        <p class="text-secondary" style="font-size: small;">©{{date("Y")}} Derechos Reservados. Ricardo
                            Calderon
                            Ingenieros!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{secure_asset('front/vendor/mdboostrap/js/mdb.umd.min7.2.0.js')}}"></script>
    <script src="{{secure_asset('front/vendor/sweetalert/sweetalert2@11.js')}}"></script>
    <script src="{{secure_asset('front/js/app/AlertMananger.js')}}"></script>
    <script>
        const __url = "{{secure_url('')}}";
        const __token = "{{ csrf_token() }}";
    </script>
    <script src="{{secure_asset('front/js/auth/auth.js')}}"></script>
</body>

</html>