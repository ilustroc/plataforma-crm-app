{{-- resources/views/panel/index.blade.php --}}
@extends('layouts.app')
@section('title','Panel')
@section('crumb','Resumen')

@push('head')
<style>
  /* ===== Ajustes base / densidad ===== */
  .panel-wrap{ --fz:.94rem }
  .panel-wrap .card.pad{ padding:1rem }
  .panel-wrap .table{ font-size:var(--fz) }
  .panel-wrap .table> :not(caption)>*>*{ padding:.45rem .6rem }
  .num { font-variant-numeric: tabular-nums; }

  /* ===== Bienvenida + buscador ===== */
  .welc h5{ font-size:1rem; margin:0 }
  .welc .small{ color:var(--bs-secondary-color) }

  /* ===== KPI mini cards ===== */
  .kpi{
    display:flex; align-items:center; gap:.7rem;
    border:1px solid var(--bs-border-color); border-radius:.75rem;
    background:var(--bs-body-bg); padding:.8rem .9rem
  }
  .kpi .ico{
    width:40px; height:40px; border-radius:999px;
    display:flex; align-items:center; justify-content:center;
  }
  .kpi .lbl{ color:var(--bs-secondary-color); font-size:.85rem }
  .kpi .val{ font-weight:800; font-size:1.1rem }

  /* ===== Accesos rápidos ===== */
  .quick .btn{ --bs-btn-padding-y:.35rem; --bs-btn-padding-x:.7rem; --bs-btn-font-size:.9rem }

  /* ===== Pagos recientes ===== */
  .panel-wrap thead th{
    background:color-mix(in oklab, var(--surface-2) 55%, transparent);
    border-bottom:1px solid var(--bs-border-color);
    text-transform:uppercase; letter-spacing:.25px; font-size:.78rem
  }

  /* ===== Actividades (notificaciones) ===== */
  .notifs{
    border:1px solid var(--bs-border-color); border-radius:.9rem; overflow:hidden;
    background:var(--bs-body-bg)
  }
  .notifs-header{
    display:flex; align-items:center; gap:.55rem;
    padding:.65rem .85rem; font-weight:700; letter-spacing:.2px;
    background:linear-gradient(90deg,#0f172a,#1f2937); color:#fff
  }
  .notifs-body{ padding:.6rem .6rem .2rem; max-height:70vh; overflow:auto }
  .notif-group{ padding:.35rem .25rem .65rem }
  .notif-title{
    display:flex; align-items:center; gap:.5rem; padding:.3rem .3rem .2rem; font-weight:700
  }
  .notif-title .icon{
    width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;
    background:var(--bs-secondary-bg); color:var(--bs-primary)
  }
  .notif-count{ margin-left:auto; font-weight:700; font-size:.78rem;
    background:#eef2ff; color:#4338ca; padding:.1rem .5rem; border-radius:999px
  }

  .notif-list{ list-style:none; margin:0; padding:0 }
  .notif-item{
    display:flex; align-items:center; gap:.6rem;
    padding:.55rem; border-radius:.6rem; color:inherit; text-decoration:none;
    border:1px solid transparent; transition:.12s ease-in-out
  }
  .notif-item + .notif-item{ margin-top:.25rem }
  .notif-item:hover{ background:var(--bs-tertiary-bg); border-color:var(--bs-border-color) }
  .notif-dot{ width:8px; height:8px; border-radius:50% }
  .notif-body{ flex:1; min-width:0 }
  .notif-main{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:600 }
  .notif-sub{ font-size:.84rem; color:var(--bs-secondary-color); white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
  .notif-cta{
    font-size:.75rem; border:1px solid var(--bs-border-color);
    background:var(--bs-body-bg); border-radius:999px; padding:.14rem .5rem; white-space:nowrap
  }
  .notif-empty{ color:var(--bs-secondary-color); font-size:.88rem; padding:.1rem .5rem .5rem; margin-left:.25rem }
  .notifs-footer{ border-top:1px dashed var(--bs-border-color); padding:.45rem .6rem .55rem }

  /* Sticky en desktops */
  @media (min-width: 992px){
    .sticky-right{ position:sticky; top:1rem }
  }
</style>
@endpush

@section('content')
<div class="panel-wrap container-fluid">
  <div class="row g-3">
    {{-- ===== Columna izquierda ===== --}}
    <div class="col-lg-8">

      {{-- Bienvenida + buscador --}}
      <div class="card pad welc">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5>¡Bienvenido(a)!</h5>
            <div class="small">Panel inicial.</div>
          </div>
          <form id="frmQuickDni" class="d-flex" role="search">
            <input id="inpQuickDni"
                   class="form-control form-control-sm me-2"
                   inputmode="numeric" autocomplete="off"
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
          <div class="kpi">
            <div class="ico bg-primary-subtle text-primary"><i class="bi bi-clipboard2-check"></i></div>
            <div>
              <div class="lbl">Promesas creadas hoy</div>
              <div class="val num">{{ number_format($kpiPromHoy) }}</div>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="kpi">
            <div class="ico bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="lbl">Pagos registrados hoy</div>
              <div class="val num">S/ {{ number_format($kpiPagosHoy,2) }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Accesos rápidos --}}
      <div class="card pad quick">
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
          <h6 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-receipt"></i> Pagos recientes
          </h6>
          <span class="text-secondary small">Últimos 10</span>
        </div>
        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle">
            <thead>
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
              @php
                $st = strtoupper((string)$p->estado);
                $cls = 'badge text-bg-secondary';
                if (str_contains($st,'CANCEL')) $cls = 'badge text-bg-success';
                elseif (str_contains($st,'PEND')) $cls = 'badge text-bg-warning';
                elseif (preg_match('/CUOTA|ABONO|PARCIAL|RECHAZ|ANUL/',$st)) $cls='badge text-bg-danger';
              @endphp
              <tr>
                <td class="text-nowrap">{{ $p->oper }}</td>
                <td class="text-nowrap">{{ \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') }}</td>
                <td class="text-end text-nowrap num">{{ number_format((float)$p->monto,2) }}</td>
                <td class="text-nowrap">{{ $p->gestor }}</td>
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

    {{-- ===== Columna derecha: Actividades ===== --}}
    <div class="col-lg-4">
      <div class="notifs shadow-sm sticky-right">
        <div class="notifs-header"><i class="bi bi-list-task"></i> Actividades</div>

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
                        DNI <b>{{ $p->dni }}</b>
                        <span class="text-secondary"> • {{ $p->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio' }}</span>
                      </div>
                      <div class="notif-sub">
                        {{ $p->operacion ?: '—' }} — {{ \Carbon\Carbon::parse($p->fecha_promesa)->format('Y-m-d') }}
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
                        CNA <b>#{{ $c->nro_carta }}</b> <span class="text-secondary">• DNI {{ $c->dni }}</span>
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
                        <b>{{ \Carbon\Carbon::parse($v->fecha)->format('d/m') }}</b>
                        <span class="text-secondary"> • DNI {{ $v->dni }}</span>
                      </div>
                      <div class="notif-sub">
                        {{ $v->operacion ?: '—' }} — {{ $v->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio' }} #{{ $v->nro }}
                      </div>
                    </div>
                    <span class="notif-cta num">S/ {{ number_format((float)$v->monto,2) }}</span>
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
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Buscar por DNI → /clientes/{dni}
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
