<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Ingreso | IMPULSE GO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#cc3024;      /* rojo de marca */
      --brand-700:#b02a21;  /* hover */
      --bg:#f7f8fc;         /* fondo súper claro */
      --surface:#ffffff;    /* tarjetas */
      --ink:#1f2328;        /* texto */
      --muted:#6d7b8a;      /* texto secundario */
      --border:#e9edf3;     /* bordes */
      --ring:0 0 0 4px rgba(204,48,36,.16);
    }

    html,body{height:100%}
    body{
      background:
        radial-gradient(900px 500px at -10% -10%, rgba(204,48,36,.05), transparent 60%),
        radial-gradient(700px 400px at 120% 0%, rgba(204,48,36,.04), transparent 60%),
        linear-gradient(#fbfcff,#f7f8fc);
      color:var(--ink);
      display:flex; align-items:center; justify-content:center;
      padding:24px;
    }

    .wrap{
      width:min(1120px, 100%);
      border-radius:22px;
      background:var(--surface);
      border:1px solid var(--border);
      box-shadow:0 18px 60px rgba(15,23,42,.06);
      overflow:hidden;
    }

    /* Lado ilustración, bien claro */
    .side{
      background:
        linear-gradient(180deg,#fff, #fff),
        radial-gradient(60% 60% at 40% 30%, rgba(204,48,36,.08), transparent 70%);
      padding:34px;
    }
    .brand img{height:36px}
    .side-hero{
      display:grid; place-items:center; text-align:center;
      margin:16px 0 8px;
    }
    .illus{
      max-height:300px; width:auto; border-radius:14px;
      filter: drop-shadow(0 14px 24px rgba(0,0,0,.06));
    }
    .side p{ color:var(--muted) }

    /* Panel de formulario */
    .panel{
      padding:34px;
      background:var(--surface);
    }
    .card-soft{
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:16px;
      box-shadow:0 8px 28px rgba(15,23,42,.05);
      padding:28px;
    }
    .form-control{
      background:#fff; color:var(--ink); border:1px solid var(--border);
    }
    .form-control:focus{ border-color:var(--brand); box-shadow:var(--ring); }
    .input-group-text{ background:#fff; border-left:none }
    .input-group .form-control{ border-right:none }

    .btn-brand{
      background:var(--brand); color:#fff; border:none; border-radius:999px;
      padding:.9rem 1rem; font-weight:600;
    }
    .btn-brand:hover{ background:var(--brand-700) }
    .link{ color:var(--brand); text-decoration:none }
    .link:hover{ text-decoration:underline }
    .help{ color:var(--muted) }
    .caps{ color:#b54708 }
  </style>
</head>
<body>

  <div class="wrap row g-0">
    <!-- LADO IZQUIERDO (claro) -->
    <div class="side col-12 col-lg-6 d-flex flex-column">
      <div class="d-flex align-items-center justify-content-between">
        <a href="/" class="brand d-inline-flex align-items-center gap-2">
          <img src="{{ asset('assets/img/logo.png') }}" alt="IMPULSE GO">
        </a>
      </div>

      <div class="side-hero flex-grow-1">
        <img class="illus img-fluid" src="{{ asset('assets/img/login-illustration.png') }}" alt="Ilustración">
        <p class="mt-3 mb-0">Bienvenido a Consorcio de Abogados del Perú.</p>
      </div>

      <div class="help small">&copy; {{ date('Y') }} Consorcio de Abogados del Perú</div>
    </div>

    <!-- LADO DERECHO (form) -->
    <div class="col-12 col-lg-6 panel d-flex align-items-center">
      <div class="card-soft w-100">
        <h1 class="h3 mb-1 fw-semibold">Iniciar sesión</h1>
        <p class="help mb-4">Ingresa tus credenciales para continuar.</p>

        @if ($errors->any())
          <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
        @endif

        <form id="login-form" method="POST" action="{{ route('login.post') }}" class="vstack gap-3" novalidate>
          @csrf

          <div>
            <label for="email" class="form-label">Correo</label>
            <input id="email" type="email" name="email" class="form-control" placeholder="tucorreo@empresa.com"
                   value="{{ old('email') }}" required autocomplete="username" inputmode="email">
            <div class="invalid-feedback">Ingresa un correo válido.</div>
          </div>

          <div>
            <label for="password" class="form-label d-flex justify-content-between align-items-center">
              <span>Contraseña</span>
              <span id="caps" class="small d-none"><i class="bi bi-exclamation-triangle-fill me-1"></i><span class="caps">Bloq Mayús activado</span></span>
            </label>
            <div class="input-group">
              <input id="password" type="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
              <button class="input-group-text" type="button" id="togglePwd" aria-label="Mostrar contraseña">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="invalid-feedback">Ingresa tu contraseña.</div>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="remember" name="remember">
              <label class="form-check-label" for="remember">Recordarme</label>
            </div>
            <a href="#" class="small link">¿Olvidaste tu contraseña?</a>
          </div>

          <button id="submitBtn" class="btn btn-brand w-100 mt-2">
            <span class="btn-text">Ingresar</span>
            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
          </button>
        </form>

        <div class="mt-4 d-flex justify-content-between small">
          <a class="link" href="mailto:impulse.conciliacion-cobranza@mgi-go.com">Soporte</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Validación + bloqueo doble envío
    (function(){
      const form = document.getElementById('login-form');
      const btn  = document.getElementById('submitBtn');
      const spn  = btn.querySelector('.spinner-border');
      const txt  = btn.querySelector('.btn-text');

      form.addEventListener('submit', function(e){
        if(!form.checkValidity()){
          e.preventDefault(); e.stopPropagation();
        }else{
          btn.disabled = true; spn.classList.remove('d-none'); txt.textContent = 'Ingresando...';
        }
        form.classList.add('was-validated');
      });
    })();

    // Toggle password
    (function(){
      const pwd = document.getElementById('password');
      const tgl = document.getElementById('togglePwd');
      tgl.addEventListener('click', ()=>{
        const show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        tgl.firstElementChild.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        tgl.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        pwd.focus();
      });
    })();

    // Aviso Caps Lock
    (function(){
      const pwd = document.getElementById('password');
      const caps = document.getElementById('caps');
      function setCaps(e){
        const on = e.getModifierState && e.getModifierState('CapsLock');
        caps.classList.toggle('d-none', !on);
      }
      pwd.addEventListener('keyup', setCaps);
      pwd.addEventListener('keydown', setCaps);
    })();
  </script>
</body>
</html>

