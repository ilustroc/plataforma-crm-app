<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'IMPULSE GO')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased selection:bg-brand/10 selection:text-brand-700">

    {{-- 1. TOAST CONTAINER --}}
    <div id="toastContainer" class="fixed top-6 right-6 z-[60] flex flex-col gap-3 w-full max-w-sm pointer-events-none">
        @if (session('ok'))
        <div class="toast toast--ok animate-slide-in pointer-events-auto" data-autoclose="5000">
            <div class="toast-icon-bg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-slate-900">Éxito</h3>
                <p class="text-sm text-slate-600 mt-0.5">{{ session('ok') }}</p>
            </div>
            <button type="button" class="toast-close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        @endif

        @if ($errors->any())
        <div class="toast toast--error animate-slide-in pointer-events-auto">
            <div class="toast-icon-bg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-slate-900">Atención</h3>
                <p class="text-sm text-slate-600 mt-0.5">{{ $errors->first() }}</p>
            </div>
            <button type="button" class="toast-close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        @endif
    </div>

    <div class="flex min-h-screen">

        {{-- 2. BACKDROP MÓVIL (Oscurece el fondo cuando abres menú en celular) --}}
        <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-sm transition-opacity lg:hidden" onclick="toggleSidebar()"></div>

        {{-- 3. SIDEBAR (RAIL) --}}
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-300 lg:static lg:translate-x-0">
            
            {{-- Header Sidebar: Logo --}}
            <div class="flex h-16 shrink-0 items-center justify-center border-b border-slate-100 px-6">
                <img src="{{ asset('img/logo.png') }}" alt="IMPULSE GO" class="h-12 w-auto object-contain">
            </div>

            {{-- === NUEVO: BUSCADOR GLOBAL EN SIDEBAR === --}}
            <div class="px-4 pt-4 pb-2">
                <form id="globalSearchForm" data-url="{{ route('clientes.show','__DNI__') }}" class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input id="globalSearchInput" 
                           type="text" 
                           inputmode="numeric" 
                           autocomplete="off"
                           placeholder="Buscar DNI..." 
                           class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition-all placeholder:text-slate-400 focus:border-brand focus:bg-white focus:ring-1 focus:ring-brand">
                </form>
            </div>

            {{-- Navegación (Scrollable) --}}
            <nav class="flex-1 overflow-y-auto px-4 pb-4 space-y-1">
                
                {{-- Sección: GENERAL --}}
                <div class="mt-2 px-2 text-xs font-bold uppercase tracking-wider text-slate-400">General</div>
                <a href="{{ route('panel') }}" class="nav-item {{ request()->routeIs('panel') ? 'active' : '' }}">
                    <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                    Resumen
                </a>
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                    Estadísticas
                </a>

                @auth
                    {{-- Sección: SUPERVISOR --}}
                    @if(in_array(auth()->user()->role,['supervisor','administrador','sistemas']))
                        <div class="mt-6 px-2 text-xs font-bold uppercase tracking-wider text-slate-400">Gestión</div>
                        
                        <a href="{{ url('/reportes/pagos') }}" class="nav-item {{ request()->is('reportes/pagos') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Reporte de Pagos
                        </a>
                        <a href="{{ url('/reportes/gestiones') }}" class="nav-item {{ request()->is('reportes/gestiones') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>
                            Reporte de Gestiones
                        </a>
                        <a href="{{ url('/reportes/pdp') }}" class="nav-item {{ request()->is('reportes/pdp') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            Reporte de Promesas
                        </a>
                        <a href="{{ url('/autorizacion') }}" class="nav-item {{ request()->is('autorizacion') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Autorización
                        </a>
                    @endif

                    {{-- Sección: ADMIN --}}
                    @php($role = strtolower(trim(auth()->user()->role ?? '')))
                    @if(in_array($role, ['administrador','sistemas']))
                        <div class="mt-6 px-2 text-xs font-bold uppercase tracking-wider text-slate-400">Admin / Soporte</div>

                        <a href="{{ url('/integracion/pagos') }}" class="nav-item {{ request()->is('integracion/pagos') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                            Cargar Pagos
                        </a>
                        <a href="{{ url('/integracion/gestiones') }}" class="nav-item {{ request()->is('integracion/gestiones*') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                            Cargar Gestiones
                        </a>
                        <a href="{{ url('/integracion/carteras') }}" class="nav-item {{ request()->is('integracion/carteras') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
                            Cargar Carteras
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Administración
                        </a>
                    @endif
                @endauth
            </nav>

            {{-- Footer Sidebar: Salir --}}
            <div class="border-t border-slate-100 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- 4. CONTENIDO PRINCIPAL --}}
        <main class="flex flex-1 flex-col min-w-0 transition-all duration-300">
            
            {{-- Header Superior --}}
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    {{-- Botón Hamburger (Móvil) --}}
                    <button type="button" class="lg:hidden text-slate-500 hover:text-slate-700" onclick="toggleSidebar()">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    
                    {{-- Breadcrumb / Título --}}
                    <h1 class="text-lg font-bold text-slate-800">
                        @yield('crumb', 'Dashboard')
                    </h1>
                </div>

                {{-- Espacio derecho (opcional para avatar mini o notificaciones) --}}
                <div class="flex items-center gap-3"></div>
            </header>

            {{-- Contenido inyectado --}}
            <div class="flex-1 p-4 sm:p-6 lg:p-8">
                {{-- Contenedor ancho máximo para que no se estire demasiado en monitores gigantes --}}
                <div class="mx-auto max-w-7xl">
                    @yield('content')
                </div>
            </div>

            {{-- Footer --}}
            <footer class="py-6 text-center text-xs font-medium text-slate-400">
                © {{ date('Y') }} Impulse Go. Todos los derechos reservados.
            </footer>
        </main>
    </div>
    
    {{-- Script inline mínimo para el toggle del sidebar en móvil --}}
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const isClosed = sidebar.classList.contains('-translate-x-full');
            
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }
    </script>
</body>
</html>