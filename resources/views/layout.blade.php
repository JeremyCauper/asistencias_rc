<!DOCTYPE html>
<html lang="es" data-mdb-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <link rel="shortcut icon" href="{{ secure_asset('front/images/app/LogoRC.png') }}" />
    <title>Layout</title>
    <!-- Font Awesome -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/all.min6.0.0.css') }}" rel="stylesheet">
    <!-- MDB -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/mdb.min7.2.0.css') }}" rel="stylesheet">
    <!-- JQuery -->
    <script src="{{ secure_asset('front/vendor/jquery/jquery.min.js') }}"></script>
    <style>
        :root,
        [data-mdb-theme=light] {
            --root-height-navbar: 50px;
            --root-layout-bg: #f9f9f9;
            --root-layout-color: #424242;
            --root-container-bg: #ffffff;

            --bg-trail: #bed9ff;
            --bg-handler: #ddebff;
            --color-before: #e4a11b;
            --transition: all .2s ease;
        }

        [data-mdb-theme=dark] {
            --root-layout-bg: #424242;
            --root-layout-color: #ffffff;
            --root-container-bg: #303030;

            --bg-trail: #303030;
            --bg-handler: #424242;
            --color-before: #ffffff;
        }

        body {
            background-color: var(--root-layout-bg);
        }

        .layout {
            display: flex;
            height: 100vh;
        }

        .navbar {
            background-color: var(--root-layout-bg);
            box-shadow: none;
            height: var(--root-height-navbar);
        }

        .navbar .navbar-brand {
            margin-right: 0;
        }

        /* Hide the input */
        #check[type="checkbox"] {
            position: absolute;
            opacity: 0;
            z-index: -1;
        }

        .check-trail {
            position: relative;
            display: flex;
            align-items: center;
            width: 3rem;
            height: 1.76rem;
            padding: .13rem .18rem;
            background: var(--bg-trail);
            border-radius: 2rem;
            transition: var(--transition);
            cursor: pointer;
            border: var(--mdb-border-width) solid var(--mdb-border-color);
        }

        .check-trail .toltip-theme {
            position: absolute;
            display: none;
            top: 190%;
            left: 50%;
            font-size: 1rem !important;
            transform: translate(-50%, -50%);
            box-shadow: 0px 0px 2px rgba(var(--mdb-surface-color-rgb), 1), 0px 0px 2px rgba(var(--mdb-surface-color-rgb), 1);
        }

        .check-trail:hover .toltip-theme {
            display: flex;
            align-items: center;
        }

        .check-handler {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 1.3rem;
            height: 1.3rem;
            position: relative;
            background: var(--bg-handler);
            border-radius: 50%;
            margin-left: 4%;
            transition: var(--transition);
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.3);
            /* border: var(--mdb-border-width) solid var(--mdb-border-color); */

            &::before {
                content: "\f186";
                position: absolute;
                font-family: "Font Awesome 6 Free";
                transition: var(--transition);
                font-size: .72rem;
                color: var(--color-before);
            }
        }

        #check[type="checkbox"]:checked+.check-trail {

            .check-handler {
                margin-left: 45%;

                &::before {
                    content: "\f185";
                }
            }
        }

        /* Sidebar a la derecha */
        .sidebar {
            width: 260px;
            height: 100vh;
            background-color: var(--root-layout-bg);
            overflow-y: auto;
            transition: width 0.3s ease-in-out;
        }

        .sidebar .sidebar__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: calc(.25rem * 9);
            margin: 8px 8px;
            cursor: default;
        }

        .sidebar .sidebar__header .sidebar-icon-logo,
        .sidebar .sidebar__header .sidebar-close {
            --spacing: .25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0d0d0d;
            width: calc(var(--spacing) * 9);
            min-width: calc(var(--spacing) * 9);
            height: calc(var(--spacing) * 9);
            background-color: transparent;
            border-radius: .5rem;
            border: none;
        }

        .sidebar .sidebar__header .sidebar-icon-logo div {
            background-size: contain !important;
            width: 1.75rem;
            height: 1.75rem;
            background: url(../public/front/images/app/LogoRC.png) no-repeat center center;
        }

        .sidebar .sidebar__body {
            padding: .5rem 0 0 0;
            cursor: default;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__link {
            --text-sm--line-height: 1.42857;
            --menu-item-height: calc(.25rem*9);
            position: relative;
            display: flex;
            align-items: center;
            padding: 6px 10px;
            margin: 2px 6px;
            color: var(--root-layout-color);
            line-height: var(--text-sm--line-height);
            min-height: var(--menu-item-height);
            text-decoration: none;
            gap: .5rem;
            border-radius: 10px;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__link.sidebar__link-menu::before {
            position: absolute;
            content: "\f104";
            font-family: "Font Awesome 6 Free";
            font-style: normal;
            font-size: .8rem;
            right: 18px;
            top: 10px;
            -webkit-transition: all 0.2s ease-in;
            -moz-transition: all 0.2s ease-in;
            -ms-transition: all 0.2s ease-in;
            -o-transition: all 0.2s ease-in;
            font-weight: normal;
            transition: transform 0.2s ease-in;
        }

        .sidebar .sidebar__body .sidebar__item[data-collapse="true"] .sidebar__link.sidebar__link-menu::before {
            transform: rotate(-90deg);
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__link .sidebar__link-icon {
            --spacing: .125rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(var(--spacing) * 10);
            min-width: calc(var(--spacing) * 10);
            height: calc(var(--spacing) * 10);
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__link .sidebar__link-text {
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__link .sidebar__link-text .truncate {
            font-size: .875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.2;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__link.is-active {
            --rgb-primary: 48, 125, 251;
            background-color: rgb(var(--rgb-primary), 15%);
            color: rgb(var(--rgb-primary));
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__submenu {
            list-style: none;
            max-height: 0;
            margin: 0;
            padding: 0;
            overflow: hidden;
            transition: max-height .3s ease, padding .6s ease, margin .6s ease;
        }

        .sidebar .sidebar__body .sidebar__item[data-collapse="true"] .sidebar__submenu {
            max-height: 1000px;
            padding: 0px 10px 6px 10px;
            margin: 2px 6px;
            transition: max-height .3s ease;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__submenu .sidebar__submenu-title {
            font-size: .75rem;
            cursor: default;
            color: #8f8f8f;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__submenu .sidebar__submenu-link {
            --menu-item-height: calc(.25rem * 7);
            position: relative;
            display: flex;
            align-items: center;
            padding: 3px 17px;
            margin: 2px 0;
            color: var(--root-layout-color);
            min-height: var(--menu-item-height);
            text-decoration: none;
            gap: .5rem;
            border-radius: 10px;
            font-size: .8rem;
            cursor: pointer;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__submenu .sidebar__submenu-link::before {
            position: absolute;
            content: "";
            width: 5px;
            height: 5px;
            left: 7px;
            border-radius: 50%;
            background: #b2b2b2;
        }

        .sidebar .sidebar__header .sidebar-icon-logo:hover,
        .sidebar .sidebar__header .sidebar-close:hover,
        .sidebar .sidebar__body .sidebar__item .sidebar__link:not(.is-active):hover,
        .sidebar .sidebar__body .sidebar__item .sidebar__submenu .sidebar__submenu-link:hover {
            background-color: rgb(var(--mdb-surface-color-rgb), 6%);
        }

        /* Sidebar a la derecha encongido solo iconos */
        .sidebar-only-icon.sidebar {
            width: 52px;
            overflow: unset;
            cursor: e-resize;
        }

        .sidebar-only-icon.sidebar .sidebar__header.sidebar__header-only-icon .sidebar-close,
        .sidebar-only-icon.sidebar .sidebar__header.sidebar__header-only-icon:hover .sidebar-icon-logo {
            display: none;
        }

        .sidebar-only-icon.sidebar .sidebar__header.sidebar__header-only-icon:hover .sidebar-close {
            display: flex;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item .sidebar__link.sidebar__link-menu::before {
            display: none;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item[data-collapse] {
            position: relative;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item[data-collapse]:hover::before {
            content: "";
            position: absolute;
            width: 20px;
            height: 55px;
            left: 50px;
            top: -1px;
            background: transparent;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item .sidebar__link:not(.sidebar__link-menu):hover .sidebar__link-text .truncate,
        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item[data-collapse]:hover .sidebar__link .sidebar__link-text .truncate,
        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item[data-collapse]:hover .sidebar__submenu {
            display: block;
            position: absolute;
            z-index: 9999;
            background-color: var(--root-layout-bg);
            color: var(--mdb-surface-color);
            transition: none;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item[data-collapse] .sidebar__submenu {
            display: none;
            transition: none;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item .sidebar__link:not(.sidebar__link-menu):hover .sidebar__link-text .truncate {
            padding: 5px 9px;
            font-size: .75rem;
            border-radius: .5rem;
            border: 1px solid #ccc;
            left: 50px;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item[data-collapse]:hover .sidebar__link .sidebar__link-text .truncate {
            padding: 10px 9px;
            width: 10rem;
            font-size: .9rem;
            border-radius: .75rem .75rem 0 0;
            border: 1px solid #ccc;
            left: 50px;
        }

        .sidebar-only-icon.sidebar .sidebar__body .sidebar__item[data-collapse]:hover .sidebar__submenu {
            max-height: 1000px;
            padding: 6px 9px;
            margin: 0;
            width: 10rem;
            font-size: .7rem;
            border-radius: 0 0 .75rem .75rem;
            border: 1px solid #ccc;
            top: 35px;
            left: 56px;
        }

        .sidebar-only-icon.sidebar.sidebar .sidebar__body .sidebar__item .sidebar__submenu .sidebar__submenu-link:not(.is-active) {
            color: var(--root-layout-color);
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__submenu .sidebar__submenu-link.is-active {
            color: rgb(48, 125, 251);
            font-weight: bold;
        }

        .sidebar .sidebar__body .sidebar__item .sidebar__submenu .sidebar__submenu-link.is-active::before {
            background-color: rgb(48, 125, 251);
        }


        .sidebar__footer {
            position: absolute;
            bottom: 0;
            padding: .5rem;
        }

        .content-wrapper {
            height: calc(100vh - var(--root-height-navbar));
            background: var(--root-container-bg);
            border-radius: .75rem 0 0 0;
            border-top: 1px solid #dcdcdc;
            border-left: 1px solid #dcdcdc;
        }
    </style>
</head>

<body>
    <div class="d-flex">

        <!-- SIDEBAR sidebar-only-icon-->
        <aside class="sidebar">

            <!-- Header -->
            <nav class="sidebar__header sidebar__header-only-icon">
                <a class="sidebar-icon-logo" href="/">
                    <div></div>
                </a>

                <button class="sidebar-close" type="button" aria-label="Cerrar barra lateral">
                    <i class="fas fa-bars" style="color: #8f8f8f;"></i>
                </button>
            </nav>

            <!-- Body -->
            <div class="sidebar__body">

                <div class="sidebar__item">
                    <a class="sidebar__link" href="/">
                        <div class="sidebar__link-icon">
                            <i class="fas fa-pen-to-square"></i>
                        </div>
                        <div class="sidebar__link-text">
                            <div class="truncate">Nuevo chat</div>
                        </div>
                    </a>
                </div>

                <div class="sidebar__item">
                    <a class="sidebar__link" href="/">
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
                        <li class="sidebar__submenu-item"><a class="sidebar__submenu-link is-active">Biblioteca 1</a>
                        </li>
                        <li class="sidebar__submenu-item"><a class="sidebar__submenu-link">Biblioteca 2</a></li>
                    </ul>
                </div>

                <div class="sidebar__item" data-collapse="false">
                    <a class="sidebar__link sidebar__link-menu" href="javascript:void(0)">
                        <div class="sidebar__link-icon">
                            <i class="far fa-gears"></i>
                        </div>
                        <div class="sidebar__link-text">
                            <div class="truncate">Opciones</div>
                        </div>
                    </a>
                    <ul class="sidebar__submenu">
                        <li class="sidebar__submenu-title">Opciones Titulo</li>
                        <li class="sidebar__submenu-item"><a class="sidebar__submenu-link">Opciones 1</a></li>
                        <li class="sidebar__submenu-item"><a class="sidebar__submenu-link">Opciones 2</a></li>
                    </ul>
                </div>

            </div>

            <!-- Footer -->
            <footer class="sidebar__footer">
                <a class="sidebar-icon-logo" href="/">
                    <div></div>
                </a>

                <button class="sidebar-close" type="button" aria-label="Cerrar barra lateral">
                    <i class="fas fa-bars" style="color: #8f8f8f;"></i>
                </button>
            </footer>
        </aside>
        <script>
            const sidebar = document.querySelector('.sidebar');

            document.querySelectorAll('.sidebar__link-menu').forEach(link => {
                link.addEventListener('click', () => {
                    if (document.querySelector('.sidebar').classList.contains('sidebar-only-icon')) return;

                    const parent = link.closest('.sidebar__item');
                    const submenu = parent.querySelector('.sidebar__submenu');
                    const isOpen = parent.getAttribute('data-collapse') === 'true';

                    // 1. Cerrar todos los demás
                    document.querySelectorAll('.sidebar__item[data-collapse="true"]').forEach(openItem => {
                        if (openItem !== parent) {
                            openItem.setAttribute('data-collapse', 'false');
                            const openSubmenu = openItem.querySelector('.sidebar__submenu');
                            if (openSubmenu) {
                                openSubmenu.style.maxHeight = null;
                            }
                        }
                    });

                    // 2. Abrir/cerrar el seleccionado
                    parent.setAttribute('data-collapse', isOpen ? 'false' : 'true');
                    submenu.style.maxHeight = isOpen ? null : submenu.scrollHeight + 'px';
                });
            });

            if (eval(localStorage.sidebarIconOnly_asistencias) || window.innerWidth < 900) {
                sidebar.classList.add('sidebar-only-icon');
            }

            if (sidebar) {
                const btn_close = sidebar.querySelector('.sidebar__header .sidebar-close');

                const sidebarHeader = () => {
                    sidebar.querySelector('.sidebar__header').classList.remove('sidebar__header-only-icon');
                    if (sidebar.classList.contains('sidebar-only-icon')) {
                        setTimeout(() => {
                            sidebar.querySelector('.sidebar__header').classList.add('sidebar__header-only-icon');
                        }, 290);
                    }
                }

                if (btn_close) {
                    btn_close.addEventListener('click', (e) => {
                        e.stopPropagation();
                        sidebar.classList.toggle('sidebar-only-icon');
                        localStorage.sidebarIconOnly_asistencias = sidebar.classList.contains('sidebar-only-icon');
                        sidebarHeader();
                    });
                }

                sidebar.addEventListener('click', (e) => {
                    // Solo cerrar si el click fue en el contenedor principal
                    if (e.target !== sidebar) return;

                    if (!sidebar.classList.contains('sidebar-only-icon')) return;
                    sidebar.classList.remove('sidebar-only-icon');
                    localStorage.sidebarIconOnly_asistencias = false;
                    sidebarHeader();
                });
            }
        </script>

        <!-- MAIN CONTENT -->
        <main class="flex-grow-1">

            <!-- Navbar -->
            <nav class="navbar px-4">
                <span class="navbar-brand mb-0 h4">Panel</span>
                <div class="navbar-brand mb-0 h4">
                    <div class="">
                        <input id="check" type="checkbox">
                        <label for="check" class="check-trail">
                            <span class="check-handler"></span>
                            <span class="badge badge-secondary toltip-theme">
                                <b class="fw-bold">Shift</b><i class="fas fa-plus fa-2xs text-white"></i> <b
                                    class="fw-bold">D</b>
                            </span>
                        </label>
                        <script>
                            function esCelularTema() {
                                return /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
                            }

                            if (!localStorage.hasOwnProperty('data_mdb_theme') || !localStorage.data_mdb_theme) {
                                localStorage.setItem('data_mdb_theme', 'light');
                            }
                            $('html').attr('data-mdb-theme', localStorage.data_mdb_theme);

                            $('#check').prop('checked', localStorage.data_mdb_theme == 'light' ? true : false);
                            if (!esCelularTema()) {
                                $('.check-trail').append(`<span class="badge badge-secondary toltip-theme">
                                <b class="fw-bold">Ctrl</b><i class="fas fa-plus fa-2xs text-white"></i> Alt</b><i class="fas fa-plus fa-2xs text-white"></i> <b class="fw-bold">D</b>
                            </span>`);
                            }

                            $(document).ready(function (tema = null) {
                                const toggleTheme = (checked = null) => {
                                    const checkbox = $('#check');
                                    if (checked) {
                                        checkbox.prop('checked', !checkbox.prop('checked'));
                                    }
                                    tema = checkbox.prop('checked') ? 'light' : 'dark';
                                    localStorage.data_mdb_theme = tema;
                                    $('html').attr('data-mdb-theme', tema);
                                };

                                $('#check').on('click', () => {
                                    toggleTheme();
                                });

                                $(window).on('keydown', ({ key, shiftKey, ctrlKey, altKey  }) => {
                                    if (ctrlKey && altKey && key.toLowerCase() === 'd') {
                                        toggleTheme(true);
                                    }
                                });

                            });
                        </script>
                    </div>
                </div>
            </nav>

            <!-- Content Wrapper -->
            <div class="content-wrapper p-4">
                <!-- Tu contenido irá aquí -->
            </div>

        </main>

    </div>

    <!--MDB -->
    <script type="text/javascript" src="{{ secure_asset('front/vendor/mdboostrap/js/mdb.umd.min7.2.0.js') }}"></script>
</body>

</html>