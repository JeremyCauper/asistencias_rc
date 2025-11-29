<!DOCTYPE html>
<html lang="es" data-mdb-theme="light">

<head>
    @if (env('APP_ENV') == 'produccion')
        <script>
            const url_base_logeo = '{{ secure_url('') }}';

            if (0 == {{ session('tipo_sistema') }}) {
                (async function() {
                    let abierto = false;
                    let consultando = false;

                    setInterval(async () => {
                        const threshold = 160;
                        const ancho = window.outerWidth - window.innerWidth > threshold;
                        const alto = window.outerHeight - window.innerHeight > threshold;

                        if ((ancho || alto) && !abierto && !consultando) {
                            console.warn('Para que quieres abrir, papu? 👀');
                            abierto = true;

                            try {
                                consultando = true;
                                const response = await fetch(url_base_logeo + '/logout', {
                                    method: 'GET'
                                });

                                if (!response.ok) {
                                    throw new Error('Respuesta no OK del servidor');
                                    location.reload();
                                }

                                // location.href = url_base_logeo + '/inicio';
                                location.reload();

                            } catch (error) {
                                console.error('Error en fetch:', error);
                                console.warn('Probable Mixed Content o redirect no seguro.');
                                location.reload();
                                // Aquí decides si quieres recargar igual o dejarlo pasar.
                            }

                        } else if (!ancho && !alto && abierto) {
                            abierto = false;
                        }

                    }, 1000);
                })();
            }
        </script>
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    {{-- <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0"> --}}
    <link rel="shortcut icon" href="{{ secure_asset('front/images/app/LogoRC.png') }}" />
    <title>@yield('title')</title>
    <!-- Font Awesome -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/all.min6.0.0.css') }}" rel="stylesheet">
    <!-- MDB -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/mdb.min7.2.0.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ secure_asset('front/vendor/sweetalert/animate.min.css') }}">
    <link rel="stylesheet" href="{{ secure_asset('front/vendor/sweetalert/default.css') }}">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="{{ secure_asset('front/vendor/fontGoogle/fonts.css') }}" />
    <!-- Home -->
    <link href="{{ secure_asset('front/layout/layout.css') }}" rel="stylesheet">
    <link href="{{ secure_asset('front/css/app.css') }}" rel="stylesheet">
    <script>
        const tipoSistema = {{ session('tipo_sistema') }};
        const nomUsuario = '{{ session()->get('config')->nombre_perfil }}';
        const txtAcceso = '{{ session()->get('config')->acceso }}';
        const acronimo = '{{ session()->get('config')->sigla }}';
        const acronimo_bg = '{{ session()->get('config')->siglaBg }}';
        const tipoUsuario = {{ session('tipo_usuario') }};
        const __url = "{{ secure_url('') }}";
        const __asset = "{{ secure_asset('front/') }}";
        const __token = "{{ csrf_token() }}";;
    </script>
    <!-- JQuery -->
    <script src="{{ secure_asset('front/vendor/jquery/jquery.min.js') }}"></script>
    @if (session('cambio') && env('APP_ENV') == 'produccion')
        <script id="cambioPass" src="{{ secure_asset('front/js/actualizarPassword.js') }}"></script>
    @endif
    <script src="{{ secure_asset('front/vendor/sweetalert/sweetalert2@11.js') }}"></script>
    <link href="{{ secure_asset('front/vendor/select/select2.min.css') }}" rel="stylesheet">
    <script src="{{ secure_asset('front/vendor/select/select2.min.js') }}"></script>
    <script src="{{ secure_asset('front/vendor/select/form_select2.js') }}"></script>
    <script src="{{ secure_asset('front/js/app/AlertMananger.js') }}"></script>
    <script src="{{ secure_asset('front/vendor/dataTable/jquery.dataTables.min.js') }}"></script>
    <script src="{{ secure_asset('front/js/app.js') }}"></script>

    @yield('cabecera')
</head>

<body>
    <div class="layout-container">
        <script>
            const layout_Container = document.querySelector('.layout-container');
            if (eval(localStorage.sidebarIconOnly_asistencias) && window.innerWidth > 767) {
                layout_Container.classList.add('sidebar-only-icon');
            } else {
                layout_Container.classList.remove('sidebar-only-icon');
            }
        </script>
        <div class="sidevar__overlay"></div>

        <!-- SIDEBAR sidebar-only-icon-->
        <aside class="sidebar">

            <!-- Header -->
            <nav class="sidebar__header sidebar__header-only-icon">
                <button class="sidebar-close hover-layout" type="button" aria-label="Cerrar barra lateral">
                    <i class="fas fa-bars" style="color: #8f8f8f;"></i>
                </button>

                <a class="sidebar-icon-logo hover-layout" href="/">
                    <div></div>
                </a>
            </nav>

            <!-- Body -->
            <div class="sidebar__body">

                @foreach (session('customModulos') as $menu)
                    <div class="sidebar__item" {{ !empty($menu->submenu) ? 'data-collapse="false"' : '' }}>
                        <a class="sidebar__link{{ !empty($menu->submenu) ? ' sidebar__link-menu' : '' }}"
                            {{ empty($menu->submenu) ? 'data-mdb-ripple-init' : '' }}
                            href="{{ !empty($menu->submenu) ? 'javascript:void(0)' : url($menu->ruta) }}"
                            @if (!empty($menu->submenu)) data-menu="{{ $menu->ruta }}" @endif>
                            <div class="sidebar__link-icon">
                                <i class="{{ $menu->icon }}"></i>
                            </div>
                            <div class="sidebar__link-text">
                                <div class="truncate">{{ $menu->descripcion }}</div>
                            </div>
                        </a>

                        @if (!empty($menu->submenu))
                            <ul class="sidebar__submenu">
                                @foreach ($menu->submenu as $categoria => $submenus)
                                    @if ($categoria !== 'sin_categoria' || count($menu->submenu) > 1)
                                        <li class="sidebar__submenu-title">
                                            {{ $categoria === 'sin_categoria' ? 'Otros' : $categoria }}
                                        </li>
                                    @endif
                                    @foreach ($submenus as $submenu)
                                        <li class="sidebar__submenu-item">
                                            <a class="sidebar__submenu-link" data-mdb-ripple-init
                                                href="{{ url($submenu->ruta) }}" data-ruta="{{ $menu->ruta }}">
                                                {{ $submenu->descripcion }}
                                            </a>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach

                {{-- <div class="sidebar__item">
                    <a class="sidebar__link" data-mdb-ripple-init href="javascript:void(0)">
                        <div class="sidebar__link-icon">
                            <i class="fas fa-magnifying-glass"></i>
                        </div>
                        <div class="sidebar__link-text">
                            <div class="truncate">Buscar chats</div>
                        </div>
                    </a>
                </div>

                <div class="sidebar__item" data-collapse="false">
                    <a class="sidebar__link sidebar__link-menu is-active" href="javascript:void(0)">
                        <div class="sidebar__link-icon">
                            <i class="far fa-images"></i>
                        </div>
                        <div class="sidebar__link-text">
                            <div class="truncate">Biblioteca</div>
                        </div>
                    </a>
                    <ul class="sidebar__submenu">
                        <li class="sidebar__submenu-title">Biblioteca Titulo</li>
                        <li class="sidebar__submenu-item"><a class="sidebar__submenu-link is-active"
                                data-mdb-ripple-init>Biblioteca 1</a>
                        </li>
                        <li class="sidebar__submenu-item"><a class="sidebar__submenu-link"
                                data-mdb-ripple-init>Biblioteca 2</a></li>
                    </ul>
                </div> --}}

            </div>

            <!-- Footer -->
            <div class="sidebar__footer dropup">
                <button class="sidebar__footer-user hover-layout" data-mdb-dropdown-init data-mdb-ripple-init
                    aria-expanded="false" data-mdb-dropdown-animation="off">
                    <div class="sidebar__footer-user-sigla"
                        style="background-color: {{ session()->get('config')->siglaBg }};">
                        {{ session()->get('config')->sigla }}
                    </div>
                    <div class="sidebar__footer-user-text">
                        <h5>{{ session()->get('config')->nombre_perfil }}</h5>
                        <h6>{{ session()->get('config')->acceso }}</h6>
                    </div>
                </button>
                <ul class="dropdown-menu py-2 px-1">
                    <li>
                        <div class="dropdown-header align-items-center d-flex" style="user-select: none">
                            <div class="align-items-center d-flex justify-content-center rounded-circle text-white"
                                style="width: 2rem; height: 2rem; background-color: {{ session()->get('config')->siglaBg }};">
                                {{ session()->get('config')->sigla }}
                            </div>
                            <div class="dropdown-header__text ms-2">
                                <span>{{ session()->get('config')->nombre_perfil }}</span>
                                <p class="fw-bold mb-0 mt-2 text-secondary">{{ session()->get('config')->acceso }}</p>
                            </div>
                        </div>
                        <hr class="mx-2 mt-0 mb-1">
                    </li>
                    <li><a class="dropdown-item rounded" href="{{ secure_url('/logout') }}">
                            <i class="fas fa-arrow-right-from-bracket me-2"></i> Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        <!-- MAIN CONTENT -->
        <main class="flex-grow-1">
            <!-- Navbar -->
            <nav class="navbar pe-3 ps-1">
                <div class="navbar-brand mb-0 p-0">
                    <div class="logo_rci"></div>
                </div>
                <div class="navbar-brand mb-0 p-0">
                    <div>
                        <link href="{{ secure_asset('front/layout/swicth_layout.css') }}" rel="stylesheet">
                        <input id="check" type="checkbox">
                        <label for="check" class="check-trail">
                            <span class="check-handler"></span>
                        </label>
                        <script src="{{ secure_asset('front/js/layout/swicth_layout.js') }}"></script>
                    </div>
                    <!-- Notifications -->
                    <div> 
                        <div class="dropdown">
                            <a data-mdb-dropdown-init class="text-reset ms-3 dropdown-toggle hidden-arrow"
                                href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="badge rounded-pill badge-notification bg-danger">1</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-right py-2 px-1"
                                aria-labelledby="navbarDropdownMenuLink" id="contenedor-notificaciones">
                                <li>
                                    <div class="dropdown-item rounded" role="button">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <span class="img-xs rounded-circle text-white acronimo"
                                                    style="background-color:#7367F0;">
                                                    JB
                                                </span>

                                                <div class="mx-2">
                                                    <p class="fw-bold mb-1">Justificación de derivación pendiente.</p>
                                                    <p class="text-muted mb-0">Jair Buitron C.</p>
                                                </div>
                                            </div>

                                            <span class="badge rounded-pill" style="background-color:#7367F0;">
                                                Técnico
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <script src="{{ secure_asset('front/js/Notificaciones.js') }}"></script>
                    </div>
                    <div>
                        <button class="sidebar-close__navbar hover-layout ms-2" type="button"
                            aria-label="Cerrar barra lateral">
                            <i class="fas fa-bars" style="color: #8f8f8f;"></i>
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Content Wrapper -->
            <div class="content-wrapper p-3">
                @yield('content')
            </div>

        </main>
        <script src="{{ secure_asset('front/js/layout/toggle_template.js') }}"></script>
    </div>

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
    {{-- <script src="{{ secure_asset('front/js/layout/hoverable-collapse.js') }}"></script> --}}
    {{-- <script src="{{ secure_asset('front/js/layout/off-canvas.js') }}"></script> --}}
    <script src="{{ secure_asset('front/js/app/FormMananger.js') }}"></script>
    <!-- plugins:js -->
    @yield('scripts')
</body>

</html>
