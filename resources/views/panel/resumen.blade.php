@extends('layouts.app')
@section('title','Panel')
@section('crumb','Resumen')
@push('styles')
<style>
  /* ===== Notifs v2 ===== */
  .notifs{border:1px solid var(--bs-border-color); border-radius:1rem; overflow:hidden; background:var(--bs-body-bg)}
  .notifs-header{
    background:linear-gradient(90deg,#0f172a,#1f2937);
    color:#fff; font-weight:600; letter-spacing:.2px;
    padding:.7rem .95rem; display:flex; align-items:center; gap:.55rem
  }
  .notifs-body{padding:.6rem .6rem 0; max-height:70vh; overflow:auto}
  .notif-group{background:transparent; padding:.35rem .35rem .6rem; margin-bottom:.35rem}
  .notif-title{
    display:flex; align-items:center; gap:.5rem;
    padding:.35rem .3rem .25rem; font-weight:600; color:#111827
  }
  .notif-title .icon{
    width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;
    background:var(--bs-secondary-bg); color:var(--bs-primary)
  }
  .notif-count{
    margin-left:auto; font-weight:700; font-size:.8rem;
    background:#eef2ff; color:#4338ca; padding:.15rem .55rem; border-radius:999px
  }

  .notif-list{list-style:none; padding-left:0; margin:0}
  .notif-item{
    display:flex; align-items:center; gap:.7rem;
    padding:.65rem .6rem; border-radius:.75rem; text-decoration:none; color:inherit;
    border:1px solid transparent; transition:.15s ease-in-out; background:transparent
  }
  .notif-item + .notif-item{margin-top:.25rem}
  .notif-item:hover{background:var(--bs-tertiary-bg); border-color:var(--bs-border-color); box-shadow:0 2px 10px rgba(15,23,42,.06)}
  .notif-dot{width:9px;height:9px;border-radius:50%}
  .notif-body{flex:1; min-width:0}
  .notif-main{white-space:nowrap; overflow:hidden; text-overflow:ellipsis}
  .notif-sub{font-size:.85rem; color:var(--bs-secondary-color); white-space:nowrap; overflow:hidden; text-overflow:ellipsis}
  .notif-cta{
    font-size:.75rem; border:1px solid var(--bs-border-color);
    background:var(--bs-body-bg); border-radius:999px; padding:.18rem .55rem; white-space:nowrap
  }
  .notif-empty{
    color:var(--bs-secondary-color); font-size:.9rem; padding:.2rem .5rem .7rem; margin-left:.25rem
  }

  /* pequeñas mejoras en el link “Ver bandeja…” */
  .notifs-footer{border-top:1px dashed var(--bs-border-color); padding:.5rem .6rem .6rem}
</style>
@endpush
@section('content')
<div class="container-fluid">
  <div class="row g-3">
    <!-- ===== Columna izquierda ===== -->
    <div class="col-lg-8">

      {{-- Bienvenida + buscador rápido --}}
      <div class="card pad">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5 class="mb-1">¡Bienvenido(a)!</h5>
            <div class="text-secondary small">Panel inicial.</div>
          </div>
          <form id="frmQuickDni" class="d-flex" role="search">
            <input id="inpQuickDni" class="form-control form-control-sm me-2" inputmode="numeric" autocomplete="off"
                   placeholder="Buscar cliente por DNI (8 dígitos)" aria-label="DNI">
            <button class="btn btn-primary btn-sm" type="submit">
              <i class="bi bi-search me-1"></i> Buscar
            </button>
          </form>
        </div>
      </div>

      {{-- KPIs del día --}}
      <div class="row g-3">
        <div class="col-sm-6">
          <div class="card pad d-flex flex-row align-items-center gap-3">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:44px;height:44px">
              <i class="bi bi-clipboard2-check"></i>
            </div>
            <div>
              <div class="text-secondary small">Promesas creadas hoy</div>
              <div class="h5 mb-0">{{ number_format($kpiPromHoy) }}</div>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="card pad d-flex flex-row align-items-center gap-3">
            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width:44px;height:44px">
              <i class="bi bi-cash-coin"></i>
            </div>
            <div>
              <div class="text-secondary small">Pagos registrados hoy</div>
              <div class="h5 mb-0">S/ {{ number_format($kpiPagosHoy,2) }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Accesos rápidos --}}
      <div class="card pad">
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('autorizacion') }}" class="btn btn-outline-primary">
            <i class="bi bi-inboxes me-1"></i> Autorización
          </a>
          <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-people me-1"></i> Clientes
          </a>
          <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-graph-up me-1"></i> Dashboard
          </a>
          <a href="{{ route('reportes.pdp') }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Reportes
          </a>
        </div>
      </div>

      {{-- Pagos recientes --}}
      <div class="card pad">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0 d-flex align-items-center gap-2"><i class="bi bi-receipt"></i> Pagos recientes</h6>
          <span class="text-secondary small">Últimos 10</span>
        </div>
        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th class="text-nowrap">Operación/Pagaré</th>
                <th class="text-nowrap">Fecha</th>
                <th class="text-end text-nowrap">Monto (S/)</th>
                <th class="text-nowrap">Gestor</th>
                <th class="text-nowrap">Estado</th>
              </tr>
            </thead>
            <tbody>
              @forelse($pagos as $p)
                <tr>
                  <td class="text-nowrap">{{ $p->oper }}</td>
                  <td class="text-nowrap">{{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                  <td class="text-end text-nowrap">{{ number_format((float)$p->monto,2) }}</td>
                  <td class="text-nowrap">{{ $p->gestor }}</td>
                  @php $st = strtoupper((string)$p->estado);
                       $cls = 'badge text-bg-secondary';
                       if (str_contains($st,'CANCEL')) $cls = 'badge text-bg-success';
                       elseif (str_contains($st,'PEND')) $cls = 'badge text-bg-warning';
                       elseif (preg_match('/CUOTA|ABONO|PARCIAL|RECHAZ|ANUL/',$st)) $cls='badge text-bg-danger';
                  @endphp
                  <td class="text-nowrap"><span class="{{ $cls }}">{{ $st }}</span></td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-3">Sin pagos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===== Columna derecha: Notificaciones estilo "UTP" ===== -->
  <div class="col-lg-4">
    <div class="notifs shadow-soft sticky-right">
      <div class="notifs-header">
        <i class="bi bi-list-task"></i> Actividades
      </div>

      <div class="notifs-body">
        {{-- Promesas por aprobar --}}
        <section class="notif-group">
          <div class="notif-title">
            <div class="icon"><i class="bi bi-clipboard-check"></i></div>
            <span>Promesas por aprobar</span>
            <span class="notif-count">{{ $ppPendCount }}</span>
          </div>
          <ul class="notif-list">
            @forelse($ppPend as $p)
              <li>
                <a href="{{ route('autorizacion') }}" class="notif-item">
                  <div class="notif-dot bg-primary"></div>
                  <div class="notif-body">
                    <div class="notif-main">
                      <span class="fw-semibold">{{ $p->dni }}</span>
                      <span class="text-secondary"> • {{ $p->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio' }}</span>
                    </div>
                    <div class="notif-sub">
                      {{ $p->operacion ?: '—' }}
                      — {{ \Carbon\Carbon::parse($p->fecha_promesa)->format('Y-m-d') }}
                      • <b>S/ {{ number_format($p->monto_mostrar,2) }}</b>
                    </div>
                  </div>
                  <span class="notif-cta">Revisar</span>
                </a>
              </li>
            @empty
              <div class="notif-empty">Nada pendiente aquí.</div>
            @endforelse
          </ul>
        </section>

        {{-- CNA por aprobar --}}
        <section class="notif-group">
          <div class="notif-title">
            <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
            <span>Solicitudes de CNA</span>
            <span class="notif-count">{{ $cnaPendCount }}</span>
          </div>
          <ul class="notif-list">
            @forelse($cnaPend as $c)
              @php $ops = collect((array)$c->operaciones)->filter()->implode(', '); @endphp
              <li>
                <a href="{{ route('autorizacion') }}#cna" class="notif-item">
                  <div class="notif-dot bg-info"></div>
                  <div class="notif-body">
                    <div class="notif-main">
                      <span class="fw-semibold">CNA #{{ $c->nro_carta }}</span>
                      <span class="text-secondary"> • DNI {{ $c->dni }}</span>
                    </div>
                    <div class="notif-sub">
                      {{ $ops ?: '—' }} — {{ optional($c->created_at)->format('Y-m-d') }}
                    </div>
                  </div>
                  <span class="notif-cta">Revisar</span>
                </a>
              </li>
            @empty
              <div class="notif-empty">Sin nuevas CNA.</div>
            @endforelse
          </ul>
        </section>

        {{-- Próximos vencimientos --}}
        <section class="notif-group">
          <div class="notif-title">
            <div class="icon"><i class="bi bi-calendar-event"></i></div>
            <span>Cuotas en los próximos 7 días</span>
            <span class="notif-count">{{ $vencCount }}</span>
          </div>
          <ul class="notif-list">
            @forelse($venc as $v)
              <li>
                <div class="notif-item">
                  <div class="notif-dot bg-warning"></div>
                  <div class="notif-body">
                    <div class="notif-main">
                      <span class="fw-semibold">{{ \Carbon\Carbon::parse($v->fecha)->format('d/m') }}</span>
                      <span class="text-secondary"> • DNI {{ $v->dni }}</span>
                    </div>
                    <div class="notif-sub">
                      {{ $v->operacion ?: '—' }}
                      — {{ $v->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio' }} #{{ $v->nro }}
                    </div>
                  </div>
                  <span class="notif-cta">S/ {{ number_format((float)$v->monto,2) }}</span>
                </div>
              </li>
            @empty
              <div class="notif-empty">No hay vencimientos próximos.</div>
            @endforelse
          </ul>
        </section>
      </div>

      <div class="notifs-footer text-end">
        <a class="small" href="{{ route('autorizacion') }}">Ver bandeja completa →</a>
      </div>
    </div>
  </div>
@endsection
@push('scripts')
<script>
  // Buscar por DNI -> /clientes/{dni}
  (() => {
    const frm   = document.getElementById('frmQuickDni');
    const input = document.getElementById('inpQuickDni');
    if (!frm || !input) return;

    const SHOW_URL = @json(route('clientes.show','__DNI__')); // '/clientes/__DNI__'

    frm.addEventListener('submit', (e) => {
      e.preventDefault();
      const dni = (input.value || '').replace(/\D/g,'').slice(0,12);
      if (!dni) { input.focus(); return; }
      window.location.assign(SHOW_URL.replace('__DNI__', encodeURIComponent(dni)));
    });
  })();
</script>
@endpush
