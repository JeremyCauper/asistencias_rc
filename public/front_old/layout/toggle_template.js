const sidebar = document.querySelector('.sidebar');
const layoutContainer = document.querySelector('.layout-container');
const sidebarOverlay = document.querySelector('.sidevar__overlay');

document.querySelectorAll('.sidebar__link-menu').forEach(link => {
    link.addEventListener('click', () => {
        if (layoutContainer.classList.contains('sidebar-only-icon')) return;

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
        submenu.style.maxHeight = isOpen ? null : (parseInt(submenu.scrollHeight) + 100) + 'px';
    });
});

if (sidebar) {
    const btn_close_sidebar = sidebar.querySelector('.sidebar__header .sidebar-close');
    const btn_open_navbar = document.querySelector('.navbar .navbar-brand .sidebar-close__navbar');

    const sidebarHeader = () => {
        sidebar.querySelector('.sidebar__header').classList.remove('sidebar__header-only-icon');
        if (layoutContainer.classList.contains('sidebar-only-icon')) {
            setTimeout(() => {
                sidebar.querySelector('.sidebar__header').classList.add('sidebar__header-only-icon');
            }, 100);
        }
    }

    if (btn_close_sidebar) {
        const toggleSidebar = (e) => {
            e.stopPropagation();
            const sidebarClass = window.innerWidth > 767 ? 'sidebar-only-icon' : 'sidebar-rtl';
            layoutContainer.classList.toggle(sidebarClass);
            localStorage.sidebarIconOnly_asistencias = window.innerWidth > 767 && layoutContainer.classList.contains(sidebarClass);
            sidebarHeader();
        }

        btn_close_sidebar.addEventListener('click', e => toggleSidebar(e));
        sidebarOverlay.addEventListener('click', e => toggleSidebar(e));
    }

    if (btn_open_navbar) {
        btn_open_navbar.addEventListener('click', (e) => {
            e.stopPropagation();
            layoutContainer.classList.remove('sidebar-only-icon');
            layoutContainer.classList.toggle('sidebar-rtl');
        });
    }

    sidebar.addEventListener('click', (e) => {
        if (e.target !== sidebar) return;

        if (!layoutContainer.classList.contains('sidebar-only-icon')) return;
        layoutContainer.classList.remove('sidebar-only-icon');
        localStorage.sidebarIconOnly_asistencias = false;
        sidebarHeader();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 767 && !layoutContainer.classList.contains('sidebar-only-icon')) {
            layoutContainer.classList.add('sidebar-only-icon');
            setTimeout(() => {
                sidebar.querySelector('.sidebar__header').classList.add('sidebar__header-only-icon');
            }, 100);
        }
    });
}