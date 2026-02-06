<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Ingreso | IMPULSE GO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="{{ asset('img/logotipo.png') }}">
  @vite(['resources/css/login.css', 'resources/js/login.js'])
</head>

<body class="min-h-screen bg-slate-50 font-sans selection:bg-brand/10 selection:text-brand-700">
  
  <div class="fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(900px_450px_at_10%_10%,rgba(111,38,97,.08),transparent_60%),radial-gradient(700px_420px_at_95%_0%,rgba(111,38,97,.05),transparent_60%)]"></div>
    <div class="absolute inset-0 bg-gradient-to-b from-white to-slate-50/80"></div>
  </div>

  <main class="min-h-screen flex items-center justify-center px-4 py-8 relative">
    
    {{-- SISTEMA DE NOTIFICACIONES --}}
    <div id="toastContainer" class="fixed top-6 right-6 z-50 flex flex-col gap-3 w-full max-w-sm pointer-events-none">
        
        @if ($errors->any())
        <div class="toast toast--error animate-slide-in pointer-events-auto">
            <div class="toast-icon-bg">
                <img src="{{ asset('img/logotipo.png') }}" alt="Logo" class="h-5 w-5 object-contain opacity-90">
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-slate-900">Acceso denegado</h3>
                <p class="text-sm text-slate-600 mt-0.5">{{ $errors->first() }}</p>
            </div>
            <button type="button" class="toast-close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        @endif

        @if (session('ok'))
        <div class="toast toast--ok animate-slide-in pointer-events-auto" data-autoclose="5000">
            <div class="toast-icon-bg">
                 <img src="{{ asset('img/logotipo.png') }}" alt="Logo" class="h-5 w-5 object-contain opacity-90">
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-semibold text-slate-900">¡Listo!</h3>
                <p class="text-sm text-slate-600 mt-0.5">{{ session('ok') }}</p>
            </div>
            <button type="button" class="toast-close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        @endif

    </div>

    {{-- CARD LOGIN --}}
    <div class="w-full max-w-[420px]">
      <section class="glass-card">
        {{-- LOGO PRINCIPAL --}}
        <div class="flex flex-col items-center justify-center">
          <img src="{{ asset('img/logo.png') }}" alt="IMPULSE GO"
               class="h-16 w-auto object-contain drop-shadow-sm transition-transform hover:scale-105 duration-500">
        </div>

        <div class="mt-8 text-center">
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Bienvenido de nuevo</h1>
          <p class="mt-2 text-sm text-slate-500 font-medium">Ingresa a tu cuenta Impulse Go</p>
        </div>

        <form id="login-form" method="POST" action="{{ route('login.post') }}" class="mt-8 space-y-5" novalidate>
          @csrf

          <div class="space-y-1.5">
            <label for="email" class="label">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   required autocomplete="username" inputmode="email"
                   class="input" placeholder="nombre@impulse.local">
          </div>

          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label for="password" class="label">Contraseña</label>
              <span id="capsHint" class="hidden text-[10px] font-bold tracking-wider text-amber-600 uppercase bg-amber-50 px-2 py-0.5 rounded">Mayúsculas activas</span>
            </div>

            <div class="relative group">
              <input id="password" type="password" name="password" required autocomplete="current-password"
                     class="input pr-12" placeholder="••••••••">

              <button type="button" id="togglePwd"
                      class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-brand transition-colors cursor-pointer"
                      aria-label="Mostrar contraseña">
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="pt-2">
            <button id="submitBtn" type="submit" class="btn-brand shadow-lg shadow-brand/20">
              <span class="btn-text">Iniciar sesión</span>
              <svg class="spinner hidden h-5 w-5 animate-spin ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
              </svg>
            </button>
          </div>
        </form>
      </section>
      
      <p class="text-center text-xs text-slate-400 mt-6 font-medium opacity-60">
        &copy; {{ date('Y') }} Impulse Group. Todos los derechos reservados.
      </p>

    </div>

</body>
</html>