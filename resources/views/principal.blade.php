<!DOCTYPE html>

<html class="light" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Enterprise ERP - Module Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-auto-rows: 180px;
            gap: 1.25rem;
        }

        @media (max-width: 1024px) {
            .bento-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .bento-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body
    class="bg-background-light dark:bg-background-dark min-h-screen text-[#111418] dark:text-white transition-colors duration-200">
    <!-- Navigation Bar -->
    <header
        class="sticky top-0 z-50 w-full border-b border-[#f0f2f4] dark:border-[#2a3441] bg-white/80 dark:bg-background-dark/80 backdrop-blur-md px-6 lg:px-20 py-3">
        <div class="max-w-[1200px] mx-auto flex items-center justify-between">
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-3">
                    <div class="bg-primary text-white p-2 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined">dataset</span>
                    </div>
                    <h2 class="text-[#111418] dark:text-white text-xl font-bold tracking-tight">RC ERP</h2>
                </div>
                <nav class="hidden md:flex items-center gap-6">
                    <a class="text-[#111418] dark:text-gray-300 text-sm font-semibold hover:text-primary transition-colors"
                        href="#">Dashboard</a>
                    <a class="text-primary text-sm font-semibold" href="#">Modulos</a>
                    <a class="text-[#111418] dark:text-gray-300 text-sm font-semibold hover:text-primary transition-colors"
                        href="#">Analitica</a>
                </nav>
            </div>
            <div class="flex items-center gap-4 flex-1 justify-end">
                <div class="relative max-w-xs w-full hidden sm:block">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-gray-400 text-sm">search</span>
                    </div>
                    <input
                        class="w-full bg-[#f0f2f4] dark:bg-[#1e293b] border-none rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary/50 placeholder:text-gray-500"
                        placeholder="Buscar modulos..." type="text" />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        class="p-2 bg-[#f0f2f4] dark:bg-[#1e293b] rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                    <button
                        class="p-2 bg-[#f0f2f4] dark:bg-[#1e293b] rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <span class="material-symbols-outlined">help</span>
                    </button>
                </div>
                <div class="flex items-center gap-3 pl-4 border-l border-gray-200 dark:border-gray-700">
                    <div class="hidden lg:block text-right">
                        <p class="text-xs font-bold text-[#111418] dark:text-white leading-none">Alex Rivera</p>
                        <p class="text-[10px] text-gray-500 font-medium">Admin</p>
                    </div>
                    <div class="size-10 rounded-full bg-cover bg-center border-2 border-primary/20"
                        data-alt="User profile avatar of Alex Rivera"
                        style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDeuPFp26gByzheqtZ_f63pkOHCWt9Co8i0QF4FIBXwYm5V-SHJxjlJXWgKM12cIWvmWp0jMvNah1dbWofSOX9H2jm5d2qCzx6w6omF0jUD_BrqJ3ggthh6o49Ga0nOHGjgwAvzbwzxgxUk2nUDHavsU4VhxQ8v-farDDsYDBlz3EWyWkCt8eSYGvZ6AEFWd9Q-okdeQOtFcFJlO3OiU6H6aUE3RvPo_CwXzJcXPip1fGQelxjfn1Gy9AwzKjAwTq6FYLWWTbnRUFg");'>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main class="max-w-[1200px] mx-auto px-6 lg:px-20 py-10">
        <!-- Header Section -->
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-2">
                <h1 class="text-4xl lg:text-5xl font-black text-[#111418] dark:text-white tracking-tight">
                    Bienvenido de nuevo, <span class="text-primary">Alex</span>
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    Lunes, 26 de enero de 2026 — <span class="font-medium text-gray-700 dark:text-gray-200">El estado operativo es normal.</span>
                </p>
            </div>
            <div class="flex gap-3">
                <button
                    class="flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-gray-700 rounded-xl font-bold text-sm shadow-sm hover:bg-gray-50 transition-all">
                    <span class="material-symbols-outlined text-sm">filter_list</span>
                    Personalizar Grid
                </button>
                <button
                    class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl font-bold text-sm shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
                    <span class="material-symbols-outlined text-sm">add</span>
                    Acción rápida
                </button>
            </div>
        </div>
        <!-- Bento Box Grid -->
        <div class="bento-grid">
            <!-- Asistencias (Large 2x2) -->
            <div
                class="md:col-span-2 md:row-span-2 relative group overflow-hidden rounded-3xl bg-primary shadow-xl p-8 flex flex-col justify-between text-white">
                <div
                    class="absolute top-0 right-0 p-8 opacity-20 group-hover:scale-110 transition-transform duration-500">
                    <span class="material-symbols-outlined !text-[120px]">schedule</span>
                </div>
                <div class="relative z-10">
                    <div class="bg-white/20 w-fit p-3 rounded-2xl mb-4 backdrop-blur-sm">
                        <span class="material-symbols-outlined">fingerprint</span>
                    </div>
                    <h3 class="text-3xl font-black leading-none mb-2">Asistencias</h3>
                    <p class="text-white/80 max-w-[200px] font-medium">Control de tiempos y asistencia en tiempo real.
                    </p>
                </div>
                <div class="relative z-10 flex items-end justify-between">
                    <div>
                        <p class="text-4xl font-black">94%</p>
                        <p class="text-xs uppercase tracking-widest font-bold opacity-70">Asistencia de Hoy</p>
                    </div>
                    <button
                        class="bg-white text-primary px-4 py-2 rounded-xl font-bold text-sm flex items-center gap-2 group-hover:gap-3 transition-all">
                        Abrir <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </button>
                </div>
                <div class="absolute inset-0 bg-gradient-to-br from-primary to-blue-700 -z-10 opacity-50"></div>
            </div>
            <!-- Inventario Vehicular (Wide 2x1) -->
            <div
                class="md:col-span-2 relative group overflow-hidden rounded-3xl bg-white dark:bg-[#1e293b] border border-gray-100 dark:border-gray-700 shadow-md p-6 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-4">
                        <div
                            class="bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 p-3 rounded-2xl">
                            <span class="material-symbols-outlined">directions_car</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold dark:text-white">Inventario Vehicular</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">42 vehículos activos</p>
                        </div>
                    </div>
                    <div class="flex -space-x-3">
                        <div class="size-8 rounded-full border-2 border-white dark:border-[#1e293b] bg-gray-200"
                            data-alt="Avatar 1"
                            style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCsiNyDrEH0crhmtLxuXG-_5mJ3wAVjqswCXGGczdZfiQ7rOkACWyfVWzqI3OkfrQEuS_rFp16FJgMdPN6QMEWIuoCu8CVxT8I95R9QvKbOxund6ISDFXHo-ALSXjpf00jwpU8YuPuA1FOF58IDfaE0bNIqVsh-An9q2O-o-wGn6RiaBHMfrRoWdgMjlLp1PpTnXAS6VGzM92Ok0yIc3fJPGahcv_FaFx7OJwfQRZXqdlfpRdlPiiBD4TTbO7G5dmSHxuHggClz-8g');">
                        </div>
                        <div class="size-8 rounded-full border-2 border-white dark:border-[#1e293b] bg-gray-300"
                            data-alt="Avatar 2"
                            style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuBP2dg8krOwkzLneow_t68BFXY7lZ4oCELO3W24aXxT6PNi3oD4SJlfMru8EaVVNLymxwOB-d3vJ-Zzn6Cn0wL8W7eLeudpkzZFneImTUnI2k6h4ieGTbmxVdUOqgwLk1j5qTG9cI0pLy7CxwLWn57eACvGHpluDP2fW5siEP1RiNog5pjwDWxenWdgdAzQZhsf1WDE5jMLomSebCqfx3GNAYWZtmJDPG8I_vCLkGUYYKalRFZLGelpZ9VxNB0Ukv-pPVyVyylzCl4');">
                        </div>
                        <div
                            class="size-8 rounded-full border-2 border-white dark:border-[#1e293b] bg-primary text-[10px] text-white flex items-center justify-center font-bold">
                            +5</div>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex gap-4">
                        <div class="flex items-center gap-1">
                            <span class="size-2 rounded-full bg-green-500"></span>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-300">38 Disponibles</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="size-2 rounded-full bg-red-500"></span>
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-300">4 En mantenimiento</span>
                        </div>
                    </div>
                    <span
                        class="material-symbols-outlined text-gray-300 dark:text-gray-600 group-hover:text-primary transition-colors">open_in_new</span>
                </div>
            </div>
            <!-- Recursos Humanos (1x1) -->
            <div
                class="relative group overflow-hidden rounded-3xl bg-white dark:bg-[#1e293b] border border-gray-100 dark:border-gray-700 shadow-md p-6 flex flex-col items-center justify-center text-center">
                <div
                    class="bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 p-4 rounded-2xl mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined !text-3xl">groups</span>
                </div>
                <h3 class="text-lg font-bold dark:text-white leading-tight">Recursos<br />Humanos</h3>
                <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-primary">arrow_forward</span>
                </div>
            </div>
            <!-- Finanzas (1x1) -->
            <div
                class="relative group overflow-hidden rounded-3xl bg-white dark:bg-[#1e293b] border border-gray-100 dark:border-gray-700 shadow-md p-6 flex flex-col items-center justify-center text-center">
                <div
                    class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 p-4 rounded-2xl mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined !text-3xl">payments</span>
                </div>
                <h3 class="text-lg font-bold dark:text-white leading-tight">Finanzas</h3>
                <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-primary">arrow_forward</span>
                </div>
            </div>
            <!-- Logística (Wide 2x1) -->
            <div
                class="md:col-span-2 relative group overflow-hidden rounded-3xl bg-zinc-900 shadow-xl p-6 flex items-center justify-between text-white">
                <div class="flex items-center gap-5">
                    <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-md">
                        <span class="material-symbols-outlined !text-3xl">envío_local</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Logística</h3>
                        <p class="text-sm text-zinc-400">12 Envíos pendientes</p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span
                        class="px-3 py-1 bg-primary rounded-full text-[10px] font-black uppercase tracking-widest">Prioridad</span>
                    <button
                        class="text-sm font-bold flex items-center gap-1 text-zinc-300 hover:text-white transition-colors">
                        Seguimiento de todos
                        <span class="material-symbols-outlined !text-sm">chevron_right</span>
                    </button>
                </div>
                <!-- Abstract Pattern Background -->
                <div class="absolute inset-0 opacity-10 pointer-events-none"
                    style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 24px 24px;">
                </div>
            </div>
            <!-- Secondary Modules -->
            <div
                class="relative group overflow-hidden rounded-3xl bg-white dark:bg-[#1e293b] border border-gray-100 dark:border-gray-700 shadow-md p-6 flex flex-col items-center justify-center text-center">
                <div
                    class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 p-4 rounded-2xl mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined !text-3xl">inventory_2</span>
                </div>
                <h3 class="text-lg font-bold dark:text-white">Almacén</h3>
            </div>
            <div
                class="relative group overflow-hidden rounded-3xl bg-white dark:bg-[#1e293b] border border-gray-100 dark:border-gray-700 shadow-md p-6 flex flex-col items-center justify-center text-center">
                <div
                    class="bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 p-4 rounded-2xl mb-4 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined !text-3xl">contract</span>
                </div>
                <h3 class="text-lg font-bold dark:text-white">Contratos</h3>
            </div>
        </div>
        <!-- Quick Insights Section -->
        <div class="mt-16 border-t border-gray-200 dark:border-gray-800 pt-10">
            <h2 class="text-2xl font-bold text-[#111418] dark:text-white mb-6">System Insights</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    class="p-6 bg-white dark:bg-[#1e293b] rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-2">Fleet Utilization</p>
                    <div class="flex items-end gap-3">
                        <span class="text-3xl font-black text-[#111418] dark:text-white">82%</span>
                        <span class="text-green-500 text-sm font-bold flex items-center mb-1">
                            <span class="material-symbols-outlined !text-sm">trending_up</span> +4.5%
                        </span>
                    </div>
                </div>
                <div
                    class="p-6 bg-white dark:bg-[#1e293b] rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-2">Aprobaciones Pendientes</p>
                    <div class="flex items-end gap-3">
                        <span class="text-3xl font-black text-[#111418] dark:text-white">14</span>
                        <span class="text-gray-400 text-sm font-medium mb-1">Acciones necesarias</span>
                    </div>
                </div>
                <div
                    class="p-6 bg-white dark:bg-[#1e293b] rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-2">Carga del Sistema</p>
                    <div class="flex items-end gap-3">
                        <span class="text-3xl font-black text-[#111418] dark:text-white">Low</span>
                        <div class="flex gap-0.5 mb-2">
                            <div class="h-4 w-1.5 rounded-full bg-green-500"></div>
                            <div class="h-4 w-1.5 rounded-full bg-green-500"></div>
                            <div class="h-4 w-1.5 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Simple Footer -->
    <footer
        class="max-w-[1200px] mx-auto px-6 lg:px-20 py-10 border-t border-gray-100 dark:border-gray-800 text-center">
        <p class="text-gray-400 text-sm font-medium">RC ERP Modulo Dashboard © 2026. Todos los derechos reservados.</p>
    </footer>
</body>

</html>