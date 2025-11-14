<!DOCTYPE html>
<html lang="es" data-mdb-theme="light">

<head>
    <script>
        // if (0 == <?= session('tipo_sistema') ?>) {
        //     (function() {
        //         let abierto = false;
        //         setInterval(() => {
        //             const threshold = 160;
        //             const ancho = window.outerWidth - window.innerWidth > threshold;
        //             const alto = window.outerHeight - window.innerHeight > threshold;

        //             if ((ancho || alto) && !abierto) {
        //                 console.warn('Para que quieres abrir, papu? 👀');
        //                 abierto = true;
        //                 fetch(__url + '/logout', {
        //                         method: 'GET',
        //                         mode: 'no-cors', // esto permite enviar la petición aunque no haya CORS
        //                         cache: 'no-store'
        //                     })
        //                     .then(() => {
        //                         location.href = __url + '/inicio';
        //                     })
        //                     .catch(err => console.error('Error al enviar la petición:', err));
        //             } else if (!ancho && !alto && abierto) {
        //                 abierto = false;
        //             }
        //         }, 1000);
        //     })();
        // }
    </script>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <link rel="shortcut icon" href="{{ secure_asset('front/images/app/LogoRC.png') }}" />
    <title>@yield('title')</title>
    <!-- Font Awesome -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/all.min6.0.0.css') }}" rel="stylesheet">
    <!-- MDB -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/mdb.min7.2.0.css') }}" rel="stylesheet">
    <!-- Iconos -->
    <!-- <link href="{{ secure_asset('front/vendor/simple-icon/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ secure_asset('front/vendor/simple-icon/styles.min.css') }}" rel="stylesheet"> -->
    <link href="{{ secure_asset('front/vendor/select/select2.min.css') }}" rel="stylesheet">

    <!-- <link rel="stylesheet" href="{{ secure_asset('front/vendor/flatpickr/flatpickr.min.css') }}"> -->

    <link rel="stylesheet" href="{{ secure_asset('front/vendor/sweetalert/animate.min.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('front/vendor/sweetalert/default.css') }}">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="{{ secure_asset('front/vendor/fontGoogle/fonts.css') }}" />
    <!-- Home -->
    <link rel="stylesheet" href="{{ secure_asset('front/css/app.css') }}">
    <script>
        const tipoSistema = {{ session('tipo_sistema') }};
        const nomUsuario = '{{ session()->get('config')->nombre_perfil }}';
        const txtAcceso = '{{ session()->get('config')->acceso }}';
        const acronimo = '{{ session()->get('config')->sigla }}';
        const acronimo_bg = '{{ session()->get('config')->siglaBg }}';
        const tipoUsuario = {{ session('tipo_usuario') }};
        const __url = "{{secure_url('') }}";
        const __asset = "{{ secure_asset('front/') }}";
        const __token = "{{ csrf_token() }}";;
    </script>
    <!-- JQuery -->
    <script src="{{ secure_asset('front/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ secure_asset('front/vendor/sweetalert/sweetalert2@11.js') }}"></script>
    @if (session('cambio'))
        <script id="cambioPass" src="{{ secure_asset('front/js/actualizarPassword.js') }}"></script>
    @endif
    <script src="{{ secure_asset('front/vendor/select/select2.min.js') }}"></script>
    <script src="{{ secure_asset('front/vendor/select/form_select2.js') }}"></script>
    <script src="{{ secure_asset('front/js/app/AlertMananger.js') }}"></script>
    <script src="{{ secure_asset('front/vendor/dataTable/jquery.dataTables.min.js') }}"></script>
    <script src="{{ secure_asset('front/js/app.js') }}"></script>

    <!-- DataTables Buttons -- >
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

  < !-- Excel export -- >
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script> -->

    <link rel="stylesheet" href="{{ secure_asset('front/css/tema.css') }}">
    <script src="{{ secure_asset('front/js/app/ToggleTema.js') }}"></script>

    @yield('cabecera')
</head>
<style>

</style>

<body class="with-welcome-text"> <!-- sidebar-icon-only -->
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top">
            <a class="navbar-brand" href="#">
                <div class="logo_rci"></div>
            </a>
            <div class="navbar-menu-wrapper d-flex align-items-top">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span id="tiempo_restante_head" class="me-3" style="font-size: small;"></span>
                    </li>
                    <div class="me-2">
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
                    @yield('navbar')
                    <!-- Avatar -->
                    <div class="dropdown">
                        <a data-mdb-dropdown-init
                            class="dropdown-toggle d-flex align-items-center hidden-arrow rounded-circle" href="#"
                            id="navbarDropdownMenuAvatar" role="button" aria-expanded="false" data-mdb-ripple-init>
                            <span class="img-xs rounded-circle text-white acronimo"
                                style="background-color: {{ session()->get('config')->siglaBg }};">{{ session()->get('config')->sigla }}</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuAvatar"
                            style="width: 22rem; min-width: 10rem;">
                            <li class="p-3">
                                <span class="border dropdown-header px-4 py-2 rounded text-center">
                                    <span class="img-md m-auto rounded-circle text-white acronimo fs-3"
                                        style="width: 65px;height: 65px;background-color: {{ session()->get('config')->siglaBg }}">{{ session()->get('config')->sigla }}</span>
                                    <p class="mb-1 mt-1 fw-semibold">
                                        {{ session()->get('config')->nombre_perfil }}
                                    </p>
                                    <p class="mb-0">{{ session()->get('config')->acceso }}</p>
                                </span>
                            </li>
                            <li>
                                <a class="dropdown-item py-3" href="{{secure_url('/logout') }}">
                                    <i class="dropdown-item-icon fas fa-power-off text-primary me-2"></i>
                                    Cerrar sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
                    data-bs-toggle="offcanvas">
                    <span class="fas fa-bars"></span>
                </button>
            </div>
            <script>
                var body = $('body');
                if (eval(localStorage.sidebarIconOnly) && window.innerWidth > 992) {
                    body.addClass('sidebar-icon-only');
                }

                $(document).ready(function () {
                    $('#expandir-menu i').on("click", function () {
                        localStorage.sidebarIconOnly = false;
                        if (window.innerWidth > 992) {
                            body.toggleClass('sidebar-icon-only');
                            localStorage.sidebarIconOnly = body.hasClass('sidebar-icon-only') ? true : false;
                        }
                    });
                })
            </script>
        </nav>
        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <div class="sidebar-content" role="button"></div>
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <li class="nav-item menu-item text-center pb-1 menu-bar" tittle-menu>
                        <a class="nav-link menu-lateral py-0" href="javascript:void(0)" role="button"
                            id="expandir-menu">
                            <i class="fas fa-bars"></i>
                            <span class="ms-2 menu-title">Menu</span>
                        </a>
                    </li>
                    <li class="nav-item menu-item text-center" tittle-menu>
                        <div class="nav-link menu-perfil pt-0">
                            <span class="acronimo rounded-circle text-white"
                                style="background-color: <?= session()->get('config')->siglaBg ?>;">{{ session()->get('config')->sigla }}</span>
                            <span class="ms-2 menu-title">
                                <p class="fw-bold mb-1 nombre-personal">
                                    {{ session()->get('config')->nombre_perfil }}
                                </p>
                                <p class="text-muted mb-0 tipo-personal">{{ session()->get('config')->acceso }}
                                </p>
                            </span>
                        </div>
                    </li>
                    @foreach (session('customModulos') as $menu)
                        <li class="nav-item menu-item">
                            <a class="nav-link menu-link" {{!empty($menu->submenu) ? (string) 'data-mdb-collapse-init role=button aria-expanded=false aria-controls=' . $menu->ruta : ''}} data-mdb-ripple-init
                                href={{!empty($menu->submenu) ? "#$menu->ruta" : url($menu->ruta)}}>
                                <i class="{{ $menu->icon }} menu-icon"></i>
                                <span class="menu-title">{{ $menu->descripcion }}</span>
                                @if (!empty($menu->submenu)) <i class="menu-arrow"></i> @endif
                            </a>
                            @if (!empty($menu->submenu))
                                <div class="collapse" id="{{$menu->ruta}}">
                                    <ul class="nav flex-column sub-menu">
                                        @foreach ($menu->submenu as $categoria => $submenus)
                                            @if ($categoria !== 'sin_categoria' || count($menu->submenu) > 1)
                                                <li class="nav-category-item">
                                                    {{ $categoria === 'sin_categoria' ? 'Otros' : $categoria }}
                                                </li>
                                            @endif
                                            @foreach ($submenus as $submenu)
                                                <li class="nav-item">
                                                    <a class="nav-link" href="{{url($submenu->ruta)}}">{{ $submenu->descripcion }}</a>
                                                </li>
                                            @endforeach
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    @yield('content')
                </div>
                <!-- content-wrapper ends -->

                <div class="modal fade" id="modal_pdf" aria-labelledby="modal_pdf" aria-hidden="true">
                    <div class="modal-dialog modal-fullscreen">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h6 class="modal-title">Visualización de PDF
                                    <span class="badge badge-success badge-lg" aria-item="codigo"></span>
                                    <span class="badge badge-info badge-lg" aria-item="codigo_orden"></span>
                                </h6>
                                <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init
                                    data-mdb-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0 position-relative">
                                <iframe id="contenedor_doc" class="w-100" frameborder="0"></iframe>
                            </div>
                            <div class="modal-footer border-top-0 pt-0 pb-1">
                                <button type="button" class="btn btn-link " data-mdb-ripple-init
                                    data-mdb-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->


    <script>
        const intervalToken = setInterval(() => {
            if (!document.cookie.includes('XSRF-TOKEN')) {
                clearInterval(intervalToken);
                location.reload();
            }
        }, 1000);
    </script>
    <!-- MDB -->
    <script type="text/javascript" src="{{ secure_asset('front/vendor/mdboostrap/js/mdb.umd.min7.2.0.js') }}"></script>
    <script src="{{ secure_asset('front/js/layout/template.js') }}"></script>
    <script src="{{ secure_asset('front/js/layout/hoverable-collapse.js') }}"></script>
    <script src="{{ secure_asset('front/js/layout/off-canvas.js') }}"></script>
    <!-- <script src="{{ secure_asset('front/vendor/inputmask/jquery.inputmask.bundle.min.js') }}"></script> -->
    <!-- <script src="{{ secure_asset('front/vendor/flatpickr/flatpickr.js') }}"></script> -->
    <!-- <script src="{{ secure_asset('front/js/app/TableManeger.js') }}"></script> -->
    <script src="{{ secure_asset('front/js/app/FormMananger.js') }}"></script>
    <!-- plugins:js -->
    @yield('scripts')


</body>

</html>