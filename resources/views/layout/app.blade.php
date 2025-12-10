<!DOCTYPE html>
<html lang="es" data-mdb-theme="light">

<head>
    @if (env('APP_ENV') == 'produccion')
        <script>
            const url_base_logeo = '{{ secure_url('') }}';

            if (0 == {{ $tipo_sistema }}) {
                (async function() {
                    let abierto = false;
                    let consultando = false;

                    setInterval(async () => {
                        const threshold = 160;
                        const ancho = window.outerWidth - window.innerWidth > threshold;
                        const alto = window.outerHeight - window.innerHeight > threshold;

                        if ((ancho || alto) && !abierto && !consultando) {
                            console.warn('No hagas eso papu 👀');
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
    <link rel="manifest" href="{{ secure_asset($ft_json->manifest) }}">
    <meta name="theme-color" content="#000000">

    <link rel="shortcut icon" href="{{ secure_asset($ft_img->icon) }}" />
    <title>@yield('title')</title>
    <!-- Font Awesome -->
    <link href="{{ secure_asset($ft_css->mdb_all_min6_0_0) }}" rel="stylesheet">
    <!-- MDB -->
    <link href="{{ secure_asset($ft_css->mdb_min7_2_0) }}" rel="stylesheet">

    <link rel="stylesheet" href="{{ secure_asset($ft_css->select2) }}">

    <link rel="stylesheet" href="{{ secure_asset($ft_css->sweet_animate) }}">
    <link rel="stylesheet" href="{{ secure_asset($ft_css->sweet_default) }}">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="{{ secure_asset($ft_css->fonts) }}" />
    <!-- Home -->
    <link rel="stylesheet" href="{{ secure_asset($ft_css->layout) }}">
    <link rel="stylesheet" href="{{ secure_asset($ft_css->app) }}">
    <script>
        const __url = "{{ secure_url('') }}";
        const __asset = "{{ secure_asset('front/') }}";
        const __token = "{{ csrf_token() }}";;
    </script>
    <!-- JQuery -->
    <script src="{{ secure_asset($ft_js->jquery) }}"></script>
    @if (session('cambio') && env('APP_ENV') == 'produccion')
        <script id="cambioPass" src="{{ secure_asset($ft_js->actualizarPassword) }}"></script>
    @endif
    <script src="{{ secure_asset($ft_js->sweet_sweetalert2) }}"></script>
    <script src="{{ secure_asset($ft_js->select2) }}"></script>
    <script src="{{ secure_asset($ft_js->form_select2) }}"></script>
    <script src="{{ secure_asset($ft_js->AlertMananger) }}"></script>
    <script src="{{ secure_asset($ft_js->jquery_dataTables) }}"></script>
    <script src="{{ secure_asset($ft_js->app) }}"></script>

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

                @foreach ($customModulos as $menu)
                    <div class="sidebar__item" {{ !empty($menu['submenu']) ? 'data-collapse="false"' : '' }}>
                        <a class="sidebar__link{{ !empty($menu['submenu']) ? ' sidebar__link-menu' : '' }}"
                            {{ empty($menu['submenu']) ? 'data-mdb-ripple-init' : '' }}
                            href="{{ !empty($menu['submenu']) ? 'javascript:void(0)' : url($menu['ruta']) }}"
                            @if (!empty($menu['submenu'])) data-menu="{{ $menu['ruta'] }}" @endif>
                            <div class="sidebar__link-icon">
                                <i class="{{ $menu['icon'] }}"></i>
                            </div>
                            <div class="sidebar__link-text">
                                <div class="truncate">{{ $menu['descripcion'] }}</div>
                            </div>
                        </a>

                        @if (!empty($menu['submenu']))
                            <ul class="sidebar__submenu">
                                @foreach ($menu['submenu'] as $categoria => $submenus)
                                    @if ($categoria !== 'sin_categoria' || count($menu['submenu']) > 1)
                                        <li class="sidebar__submenu-title">
                                            {{ $categoria === 'sin_categoria' ? 'Otros' : $categoria }}
                                        </li>
                                    @endif
                                    @foreach ($submenus as $submenu)
                                        <li class="sidebar__submenu-item">
                                            <a class="sidebar__submenu-link" data-mdb-ripple-init
                                                href="{{ url($submenu['ruta']) }}" data-ruta="{{ $menu['ruta'] }}">
                                                {{ $submenu['descripcion'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach

            </div>

            <!-- Footer -->
            <div class="sidebar__footer dropup">
                <button class="sidebar__footer-user hover-layout" data-mdb-dropdown-init data-mdb-ripple-init
                    aria-expanded="false" data-mdb-dropdown-animation="off">
                    <div class="sidebar__footer-user-sigla"
                        style="background-color: {{ $config->siglaBg }};">
                        {{ $config->sigla }}
                    </div>
                    <div class="sidebar__footer-user-text">
                        <h5>{{ $config->nombre_perfil }}</h5>
                        <h6>{{ $config->acceso }}</h6>
                    </div>
                </button>
                <ul class="dropdown-menu py-2 px-1" style="width: 15.25rem !important;">
                    <li>
                        <div class="dropdown-header align-items-center d-flex" style="user-select: none">
                            <div class="align-items-center d-flex justify-content-center rounded-circle text-white"
                                style="width: 2rem; height: 2rem; background-color: {{ $config->siglaBg }};">
                                {{ $config->sigla }}
                            </div>
                            <div class="dropdown-header__text ms-2">
                                <span>{{ $config->nombre_perfil }}</span>
                                <p class="fw-bold mb-0 mt-2 text-secondary">{{ $config->acceso }}</p>
                            </div>
                        </div>
                        <hr class="mx-2 mt-0 mb-1">
                    </li>
                    <li><a class="dropdown-item rounded" href="{{ secure_url('/logout') }}" onclick="boxAlert.loading()">
                            <i class="fas fa-arrow-right-from-bracket me-2"></i> Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>
        </aside>
        <!-- MAIN CONTENT -->
        <main class="flex-grow-1">
            <!-- Navbar -->
            <nav class="navbar pe-3" style="padding-left: 16px;">
                <div class="navbar-brand mb-0 p-0">
                    <div class="logo_rci"></div>
                </div>
                <div class="navbar-brand mb-0 p-0">
                    {{-- Switch Layout --}}
                    @include('layout.partials.swicth_layout')
                    {{-- Notifications --}}
                    <div class="ms-1">
                        <div class="dropdown" id="contenedor-notificaciones">
                            <button data-mdb-dropdown-init class="btn-notification hover-layout" role="button"
                                data-mdb-auto-close="outside" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="badge rounded-pill badge-notification bg-danger"></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right py-0 px-1">
                                <div class="dropdown-header d-flex align-items-center justify-content-between px-2">
                                    <h6 class="mb-0" style="user-select: none">Notificaciones</h6>
                                    <button class="btn btn-light btn-sm px-2" noti-btn="reload"><i
                                            class="fas fa-rotate"></i></button>
                                </div>
                                <div class="dropdown-body">
                                    <div class="dropdown-text text-center text-muted py-3">
                                        Sin notificaciones
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script src="{{ secure_asset($ft_js->NotificacionesControl) }}"></script>
                    </div>
                    <div class="ms-1">
                        <div class="dropdown" id="contenedor-sigla">
                            <button class="btn-notification hover-layout" data-mdb-dropdown-init data-mdb-ripple-init
                                aria-expanded="false" data-mdb-dropdown-animation="off">
                                <div class="sigla"
                                    style="background-color: {{ $config->siglaBg }};">
                                    {{ $config->sigla }}
                                </div>
                            </button>
                            <ul class="dropdown-menu pt-1 pb-2 px-1">
                                <li class="p-2">
                                    <div class="dropdown-header p-0" style="user-select: none">
                                        <div class="text-center rounded py-3 px-2">
                                            <div class="align-items-center d-flex justify-content-center rounded-circle text-white mx-auto"
                                                style="width: 3.5rem; height: 3.5rem; font-size: 1.5rem; background-color: {{ $config->siglaBg }};">
                                                {{ $config->sigla }}
                                            </div>
                                            <p class="fw-bold mb-0 mt-2 text-secondary">{{ $config->nombre_perfil }}</p>
                                            <small>{{ $config->acceso }}</small>
                                        </div>
                                    </div>
                                </li>
                                <li class="px-2"><a class="dropdown-item rounded" href="{{ secure_url('/logout') }}" onclick="boxAlert.loading()">
                                        <i class="fas fa-arrow-right-from-bracket me-2"></i> Cerrar sesión
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="ms-1">
                        <button class="sidebar-close__navbar hover-layout" type="button"
                            aria-label="Cerrar barra lateral">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Content Wrapper -->
            <div class="content-wrapper p-3">
                @yield('content')
            </div>

        </main>
        <script src="{{ secure_asset($ft_js->toggle_template) }}"></script>
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
    <script type="text/javascript" src="{{ secure_asset($ft_js->mdb_umd_min7_2_0) }}"></script>
    <script src="{{ secure_asset($ft_js->template) }}"></script>
    <script src="{{ secure_asset($ft_js->FormMananger) }}"></script>

    @include('layout.partials.service_worker')
    @yield('scripts')
</body>

</html>
