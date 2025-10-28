<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="utf-8">
  <title>@yield('title','CONSORCIO DE ABOGADOS DEL PERU')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    /* ======= TOKENS (marca rojo sobrio) ======= */
    :root{
      --brand:#cc3024;            /* principal */
      --brand-ink:#a32820;        /* hover/ink */
      --brand-tint:#fde6e3;       /* tint suave */
      --radius:14px; --radius-sm:10px;
      --shadow:0 12px 36px rgba(15,23,42,.08);
      --shadow-sm:0 8px 22px rgba(15,23,42,.06);
      --font:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
      --content-max:1220px; /* un poco más compacto */
    }

    /* ======= ESCALA GLOBAL (similar a 90-94% de zoom) ======= */
    html{ font-size:15px; }           /* 15px ≈ 93.75% del 16px default */
    @media (min-width: 1600px){ html{ font-size:14.5px; } } /* pantallas grandes ≈ 90% */

    [data-theme="light"]{
      --bg:#f7f8fc; --surface:#ffffff; --surface-2:#f3f6fb; --border:#e8ecf3;
      --ink:#151a23; --muted:#6d7b8a;

      --bs-body-bg:var(--bg); --bs-body-color:var(--ink); --bs-heading-color:var(--ink);
      --bs-secondary-color:var(--muted); --bs-border-color:var(--border);
      --bs-card-bg:var(--surface); --bs-card-border-color:var(--border);
    }
    [data-theme="dark"]{
      --bg:#0b1016; --surface:#0f141b; --surface-2:#0c1218; --border:#1a232e;
      --ink:#eaf0f6; --muted:#97a6b7;

      --bs-body-bg:var(--bg); --bs-body-color:var(--ink); --bs-heading-color:var(--ink);
      --bs-secondary-color:var(--muted); --bs-border-color:var(--border);
      --bs-card-bg:var(--surface); --bs-card-border-color:var(--border);
    }

    html,body{height:100%}
    body{margin:0; background:var(--bg); color:var(--ink); font-family:var(--font)}

    /* ======= SHELL ======= */
    .shell{display:flex; min-height:100vh;}

    /* Sidebar */
    .rail{
      width:240px; flex:0 0 240px; height:100vh; position:sticky; top:0;
      background:
        radial-gradient(900px 500px at -10% -10%, color-mix(in oklab, var(--brand) 6%, transparent), transparent 60%),
        linear-gradient(180deg, var(--surface-2), var(--surface));
      border-right:1px solid var(--border);
      display:flex; flex-direction:column;
    }
    [data-theme="dark"] .rail{
      background:
        radial-gradient(900px 500px at -10% -10%, color-mix(in oklab, var(--brand) 10%, transparent), transparent 60%),
        linear-gradient(180deg, var(--surface-2), var(--surface));
    }

    .brand{ display:flex; gap:12px; align-items:center; padding:14px 16px; border-bottom:1px solid var(--border) }
    .brand .mark{
      width:40px; height:40px; border-radius:12px; display:grid; place-items:center;
      background: color-mix(in oklab, var(--brand) 12%, transparent);
      color:var(--brand);
      flex:0 0 40px;
      font-size:1.05rem;
    }
    /* LOGO: más compacto y swap por tema */
    .logo { display:flex; align-items:center; gap:10px }
    .logo img{ height:36px; width:auto; display:block }
    .logo .logo-light{ display:inline-block }
    .logo .logo-dark{ display:none }
    [data-theme="dark"] .logo .logo-light{ display:none }
    [data-theme="dark"] .logo .logo-dark{ display:inline-block }

    .who{padding:10px 16px; border-bottom:1px solid var(--border)}
    .who .n{font-weight:700}
    .who .r{font-size:.78rem; color:var(--muted); text-transform:uppercase; letter-spacing:.5px}

    .navy{padding:8px; overflow:auto}
    .navy .lab{font-size:.72rem; color:var(--muted); padding:8px 10px 6px}
    .navy a{
      position:relative;
      display:flex; align-items:center; gap:.6rem;
      padding:8px 10px; border-radius:12px;
      color:inherit; text-decoration:none; border:1px solid transparent; font-size:.98rem;
    }
    .navy a i{
      color:var(--brand);
      background: color-mix(in oklab, var(--brand) 14%, transparent);
      width:32px; height:32px; border-radius:10px; display:grid; place-items:center;
      font-size:1rem;
    }
    .navy a:hover{
      background: color-mix(in oklab, var(--brand) 8%, transparent);
      border-color: color-mix(in oklab, var(--brand) 20%, transparent);
    }
    .navy a.active{
      background: color-mix(in oklab, var(--brand) 12%, transparent);
      border-color: color-mix(in oklab, var(--brand) 32%, transparent);
      font-weight:600;
    }
    .navy a.active::before{
      content:""; position:absolute; left:-10px; top:8px; bottom:8px; width:4px;
      background: linear-gradient(180deg, var(--brand), var(--brand-ink)); border-radius:8px;
    }
    [data-theme="dark"] .navy a:hover{
      background: color-mix(in oklab, var(--brand) 14%, transparent);
      border-color: color-mix(in oklab, var(--brand) 38%, transparent);
    }

    .rail-foot{margin-top:auto; padding:12px; border-top:1px solid var(--border)}
    .theme-btn{
      width:100%; height:40px; border-radius:12px; border:1px solid var(--border);
      background:var(--surface); display:flex; align-items:center; justify-content:center; gap:8px;
      color:var(--ink); font-weight:500; font-size:.96rem;
    }
    .theme-btn .sun{display:inline} .theme-btn .moon{display:none}
    [data-theme="dark"] .theme-btn .sun{display:none} [data-theme="dark"] .theme-btn .moon{display:inline}

    /* ======= MAIN ======= */
    .main{flex:1; min-width:0; display:flex; flex-direction:column;}
    .appbar{
      position:sticky; top:0; z-index:10;
      background:var(--surface); border-bottom:1px solid var(--border);
      box-shadow:0 4px 16px rgba(15,23,42,.03);
    }
    .appbar-in{
      margin:0 auto; max-width:var(--content-max);
      padding:10px 20px; display:flex; align-items:center; gap:12px;
    }
    .appbar .crumb{ font-weight:700; letter-spacing:.2px; font-size:1.05rem }

    .content{flex:1}
    .content-in{
      margin:0 auto; max-width:var(--content-max);
      padding:18px 20px 16px; display:flex; flex-direction:column; gap:16px;
    }

    .card{background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow-sm)}
    .card.pad{padding:12px 14px}

    .chip{
      background:var(--surface); border:1px solid var(--border); border-radius:12px;
      padding:12px 14px; height:100%; transition:transform .06s, box-shadow .12s;
      display:flex; flex-direction:column; gap:6px;
    }
    .chip:hover{transform:translateY(-1px); box-shadow:var(--shadow)}
    .chip .t{display:flex; align-items:center; gap:10px; font-weight:600}
    .chip .t i{
      color:var(--brand); background:color-mix(in oklab, var(--brand) 16%, transparent);
      width:32px; height:32px; border-radius:10px; display:grid; place-items:center;
      font-size:1rem;
    }
    .chip .s{color:var(--muted); font-size:.9rem}

    .kpi{position:relative; background:var(--surface); border:1px solid var(--border);
      border-radius:12px; padding:14px; height:100%; display:flex; flex-direction:column; justify-content:center; gap:6px;}
    .kpi::before{
      content:""; position:absolute; left:0; top:0; bottom:0; width:4px;
      background:linear-gradient(180deg, var(--brand), var(--brand-ink)); opacity:.95;
      border-top-left-radius:12px; border-bottom-left-radius:12px;
    }
    .kpi .label{color:var(--muted); font-size:.88rem}
    .kpi .value{font-weight:800; font-size:1.7rem; line-height:1}

    .footer{margin-top:auto; padding:12px 0; color:var(--muted)}

    :focus-visible{
      outline:3px solid color-mix(in oklab, var(--brand) 50%, transparent);
      outline-offset:2px; border-radius:10px;
    }

    /* ======= Tablas / formularios más compactos ======= */
    .table> :not(caption)>*>*{ padding:.55rem .75rem; }
    .form-control,.form-select{ background:var(--surface); border-color:var(--border); }
    .form-control::placeholder{ color:var(--muted) }
    .form-control:focus,.form-select:focus{
      background:var(--surface);
      border-color: color-mix(in oklab, var(--brand) 52%, var(--border));
      box-shadow: 0 0 0 .25rem color-mix(in oklab, var(--brand) 22%, transparent);
    }
    [data-theme="dark"] .form-control,[data-theme="dark"] .form-select{ color:var(--ink) }

    /* ======= Botones globales en rojo marca (sustituye azul bootstrap) ======= */
    .btn-primary{ background:var(--brand); border-color:var(--brand); }
    .btn-primary:hover{ background:var(--brand-ink); border-color:var(--brand-ink); }
    .btn-primary:focus{ box-shadow:0 0 0 .25rem color-mix(in oklab, var(--brand) 28%, transparent) }

    .btn-outline-primary{ color:var(--brand); border-color:var(--brand); }
    .btn-outline-primary:hover{ color:var(--brand-ink); border-color:var(--brand-ink); background:color-mix(in oklab, var(--brand) 12%, transparent) }
    .btn-outline-primary:focus{ box-shadow:0 0 0 .25rem color-mix(in oklab, var(--brand) 20%, transparent) }

    /* ======= Scrollbar ======= */
    ::-webkit-scrollbar{ width:10px; height:10px }
    ::-webkit-scrollbar-thumb{ background: color-mix(in oklab, var(--brand) 22%, transparent); border-radius:10px }
    ::-webkit-scrollbar-track{ background: transparent }

    @media (max-width: 992px){
      .rail{position:fixed; left:-240px; z-index:1050; transition:left .2s}
      .rail.show{left:0}
      .backdrop{position:fixed; inset:0; background:rgba(0,0,0,.35); display:none; z-index:1040}
      .backdrop.show{display:block}
      .appbar-in,.content-in{padding-left:16px; padding-right:16px}
    }
  </style>
  @stack('head')
</head>
<body>
  <div class="shell">
    <!-- Sidebar -->
    <aside id="rail" class="rail">
      <div class="brand">
        <div class="mark"><i class="bi bi-building"></i></div>
        <div class="logo">
          {{-- Usa estos dos assets: negro para claro, blanco para oscuro --}}
          <img class="logo-light" src="{{ asset('assets/img/logo.png') }}" alt="Logo">
          <img class="logo-dark"  src="{{ asset('assets/img/logo-blanco.png') }}" alt="Logo">
        </div>
      </div>

      <div class="who">
        <div class="n">{{ auth()->user()->name ?? 'Usuario' }}</div>
        <div class="r">{{ auth()->user()->role ?? '' }}</div>
      </div>

      <nav class="navy">
        <div class="lab">GENERAL</div>
        <a href="{{ route('panel') }}" class="{{ request()->routeIs('panel') ? 'active' : '' }}"><i class="bi bi-grid"></i><span>Resumen</span></a>
        <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.index') ? 'active' : '' }}"><i class="bi bi-search"></i><span>Buscar Cliente</span></a>
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-graph-up"></i><span>Estadísticas</span></a>

        @auth
          @if(in_array(auth()->user()->role,['supervisor','administrador','sistemas']))
            <div class="lab">SUPERVISOR</div>
            <a class="{{ request()->is('reportes/pagos') ? 'active' : '' }}" href="{{ url('/reportes/pagos') }}"><i class="bi bi-cash-coin"></i><span>Reporte de Pagos</span></a>
            <a class="{{ request()->is('reportes/gestiones') ? 'active' : '' }}" href="{{ url('/reportes/gestiones') }}"><i class="bi bi-chat-dots"></i><span>Reporte de Gestiones</span></a>
            <a class="{{ request()->is('reportes/pdp') ? 'active' : '' }}" href="{{ url('/reportes/pdp') }}"><i class="bi bi-flag"></i><span>Reporte de Promesas</span></a>
            <a class="{{ request()->is('autorizacion') ? 'active' : '' }}" href="{{ url('/autorizacion') }}"><i class="bi bi-check2-square"></i><span>Autorización</span></a>
          @endif

          @php($role = strtolower(trim(auth()->user()->role ?? '')))
          @if(in_array($role, ['administrador','sistemas']))
            <div class="lab">ADMIN / SOPORTE</div>

            <a class="{{ request()->is('integracion/pagos') ? 'active' : '' }}"
              href="{{ url('/integracion/pagos') }}">
              <i class="bi bi-upload"></i><span>Integración ▸ Subir Pagos</span>
            </a>

            {{-- NUEVO: Integración ▸ Subir Gestiones (PROPIA) --}}
            <a class="{{ request()->is('integracion/gestiones*') ? 'active' : '' }}"
              href="{{ url('/integracion/gestiones') }}">
              <i class="bi bi-clipboard2-data"></i><span>Integración ▸ Subir Gestiones</span>
            </a>

            <a class="{{ request()->is('integracion/data') ? 'active' : '' }}"
              href="{{ url('/integracion/data') }}">
              <i class="bi bi-cloud-upload"></i><span>Integración ▸ Subir Data</span>
            </a>

            <a class="{{ request()->is('administracion') ? 'active' : '' }}"
              href="{{ url('/administracion') }}">
              <i class="bi bi-gear"></i><span>Administración</span>
            </a>
          @endif

          <div class="lab">CUENTA</div>
          <form method="POST" action="{{ route('logout') }}" class="px-2">
            @csrf
            <button class="btn btn-outline-secondary w-100"><i class="bi bi-box-arrow-right me-1"></i> Salir</button>
          </form>
        @endauth
      </nav>

      <div class="rail-foot">
        <button class="theme-btn js-theme">
          <i class="bi bi-sun sun"></i><i class="bi bi-moon moon"></i> Cambiar tema
        </button>
      </div>
    </aside>

    <!-- Backdrop móvil -->
    <div id="backdrop" class="backdrop" onclick="toggleRail()"></div>

    <!-- Main -->
    <main class="main">
      <div class="appbar">
        <div class="appbar-in">
          <button class="btn btn-outline-secondary d-lg-none" onclick="toggleRail()"><i class="bi bi-list"></i></button>
          <div class="crumb">@yield('crumb','')</div>
        </div>
      </div>

      <div class="content">
        <div class="content-in">
          @yield('content')
          <div class="footer small">© {{ date('Y') }} CONSORCIO DE ABOGADOS DEL PERU</div>
        </div>
      </div>
    </main>
  </div>

  <script>
    function toggleRail(){
      document.getElementById('rail').classList.toggle('show');
      document.getElementById('backdrop').classList.toggle('show');
    }

    // Tema: guarda preferencia y realiza swap CSS-only (logos cambian solos)
    const THEME_KEY='impulse.theme';
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial = localStorage.getItem(THEME_KEY) || (prefersDark ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', initial);

    document.querySelector('.js-theme')?.addEventListener('click',()=>{
      const current = document.documentElement.getAttribute('data-theme');
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem(THEME_KEY, next);
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>