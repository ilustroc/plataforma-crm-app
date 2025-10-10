{{-- resources/views/panel/resumen.blade.php --}}
@extends('layouts.app')
@section('title','Panel')
@section('crumb','Resumen')

@push('head')
<style>
  /* ====== Tema rojo (usa tu --brand si existe) ====== */
  :root{
    --brand: var(--brand, #dc3545);
    --brand-ink: #a61e2a;
  }

  /* Tarjetas/kpis */
  .kpi{display:flex;align-items:center;gap:.75rem;border:1px solid var(--bs-border-color);
       border-radius:14px;padding:.8rem 1rem;background:var(--bs-body-bg)}
  .kpi .ico{width:44px;height:44px;border-radius:999px;
           display:flex;align-items:center;justify-content:center;
           background:color-mix(in oklab, var(--brand) 12%, transparent); color:var(--brand)}
  .kpi .lbl{color:var(--bs-secondary-color);font-size:.85rem}
  .kpi .val{font-weight:800;font-size:1.15rem}

  /* Accesos rápidos */
  .quick a{border-radius:999px}

  /* Tabla compacta */
  .tbl-sm.table> :not(caption)>*>*{padding:.5rem .65rem}
  .tbl-sm thead th{
    position:sticky;top:0;z-index:1;
    background:color-mix(in oklab, var(--bs-body-bg) 70%, transparent);
    text-transform:uppercase;font-size:.8rem;letter-spacing:.3px;border-bottom:1px solid var(--bs-border-color)
  }

  /* ===== Notificaciones (col derecha) ===== */
  .notifs{border:1px solid var(--bs-border-color);border-radius:14px;overflow:hidden;background:var(--bs-body-bg)}
  .notifs-header{
    background:linear-gradient(90deg,#7f1d1d,#b91c1c,#dc2626);
    color:#fff;font-weight:700;letter-spacing:.2px;
    padding:.7rem .95rem;display:flex;align-items:center;gap:.55rem
  }
  .notifs-body{padding:.6rem .6rem 0;max-height:70vh;overflow:auto}
  .notif-title{display:flex;align-items:center;gap:.5rem;padding:.35rem .3rem .25rem;font-weight:600}
  .notif-title .icon{
    width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;
    background:var(--bs-secondary-bg);color:var(--brand)
  }
  .notif-count{margin-left:auto;font-weight:700;font-size:.8rem;background:#fee2e2;color:#991b1b;
    padding:.15rem .55rem;border-radius:999px}
  .notif-list{list-style:none;margin:0;padding:0}
  .notif-item{display:flex;align-items:center;gap:.7rem;padding:.6rem;border-radius:10px;text-decoration:none;color:inherit;
    border:1px solid transparent;background:transparent}
  .notif-item:hover{background:var(--bs-tertiary-bg);border-color:var(--bs-border-color);box-shadow:0 2px 10px rgba(15,23,42,.06)}
  .notif-dot{width:9px;height:9px;border-radius:50%}
  .notif-body{flex:1;min-width:0}
  .notif-main{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .notif-sub{font-size:.85rem;color:var(--bs-secondary-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .notif-cta{font-size:.75rem;border:1px solid var(--bs-border-color);background:var(--bs-body-bg);
    border-radius:999px;padding:.18rem .55rem;white-space:nowrap}
  .notif-empty{color:var(--bs-secondary-color);font-size:.9rem;padding:.2rem .5rem .7rem;margin-left:.25rem}
  .notifs-footer{border-top:1px dashed var(--bs-border-color);padding:.5rem .6rem .6rem}

  /* Bloque gráfico */
  .chart-card{border:1px solid var(--bs-border-color);border-radius:14px;padding:1rem;background:var(--bs-body-bg)}
  .chart-actions{display:flex;align-items:center;gap:.5rem}
  .btn-brand-outline{color:var(--brand);border-color:var(--brand)}
  .btn-brand-outline:hover{color:#fff;background:var(--brand);border-color:var(--brand)}
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="row g-3">
    <!-- ===== Izquierda ===== -->
    <div class="col-lg-8">

      {{-- Bienvenida + buscador --}}
      <div class="card pad">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h5 class="mb-1">¡Bienvenido(a)!</h5>
            <div class="text-secondary small">Panel inicial.</div>
          </div>
          <form id="frmQuickDni" class="d-flex" role="search">
            <input id="inpQuickDni" class="form-control form-control-sm me-2" inputmode="numeric" autocomplete="off"
                   placeholder="Buscar cliente por DNI (8 dígitos)" aria-label="DNI">
            <button class="btn btn-danger btn-sm" type="submit">
              <i class="bi bi-search me-1"></i> Buscar
            </button>
          </form>
        </div>
      </div>

      {{-- KPIs del día --}}
      <div class="row g-3">
        <div class="col-sm-6">
          <div class="kpi">
            <div class="ico"><i class="bi bi-clipboard2-check"></i></div>
            <div>
              <div class="lbl">Promesas creadas hoy</div>
              <div class="val">{{ number_format($kpiPromHoy) }}</div>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="kpi">
            <div class="ico"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="lbl">Pagos registrados hoy</div>
              <div class="val">S/ {{ number_format($kpiPagosHoy,2) }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Accesos rápidos --}}
      <div class="card pad quick">
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('autorizacion') }}" class="btn btn-danger">
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

      {{-- Pagos del mes (gráfico) --}}
      <div class="chart-card">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <h6 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-line text-danger"></i>
            <span>Pagos del mes</span>
            <span class="badge rounded-pill text-bg-light border">Total S/ {{ number_format($sumMes,2) }}</span>
          </h6>
          <form method="GET" class="chart-actions">
            <input type="month" name="mes" value="{{ $mes }}" class="form-control form-control-sm">
            <button class="btn btn-brand-outline btn-sm">Aplicar</button>
            @if(request('mes'))
              <a href="{{ route('panel') }}" class="btn btn-outline-secondary btn-sm">Mes actual</a>
            @endif
          </form>
        </div>
        <canvas id="pagosChart" height="80"></canvas>
      </div>

      {{-- Pagos recientes --}}
      <div class="card pad">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-receipt text-danger"></i> Pagos recientes
          </h6>
          <span class="text-secondary small">Últimos 10</span>
        </div>
        <div class="table-responsive mt-2">
          <table class="table table-sm align-middle tbl-sm">
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

    <!-- ===== Derecha: Notificaciones ===== -->
    <div class="col-lg-4">
      <div class="notifs shadow-sm">
        <div class="notifs-header">
          <i class="bi bi-list-task"></i> Actividades
        </div>

        <div class="notifs-body">
          {{-- Promesas por aprobar --}}
          <section class="mb-2">
            <div class="notif-title">
              <div class="icon"><i class="bi bi-clipboard-check"></i></div>
              <span>Promesas por aprobar</span>
              <span class="notif-count">{{ $ppPendCount }}</span>
            </div>
            <ul class="notif-list">
              @forelse($ppPend as $p)
                <li>
                  <a href="{{ route('autorizacion') }}" class="notif-item">
                    <div class="notif-dot bg-danger"></div>
                    <div class="notif-body">
                      <div class="notif-main">
                        <span class="fw-semibold">{{ $p->dni }}</span>
                        <span class="text-secondary"> • {{ $p->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio' }}</span>
                      </div>
                      <div class="notif-sub">
                        {{ $p->operacion ?: '—' }} — {{ \Carbon\Carbon::parse($p->fecha_promesa)->format('Y-m-d') }} •
                        <b>S/ {{ number_format($p->monto_mostrar,2) }}</b>
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

          {{-- CNA --}}
          <section class="mb-2">
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
                    <div class="notif-dot bg-danger"></div>
                    <div class="notif-body">
                      <div class="notif-main">
                        <span class="fw-semibold">CNA #{{ $c->nro_carta }}</span>
                        <span class="text-secondary"> • DNI {{ $c->dni }}</span>
                      </div>
                      <div class="notif-sub">{{ $ops ?: '—' }} — {{ optional($c->created_at)->format('Y-m-d') }}</div>
                    </div>
                    <span class="notif-cta">Revisar</span>
                  </a>
                </li>
              @empty
                <div class="notif-empty">Sin nuevas CNA.</div>
              @endforelse
            </ul>
          </section>

          {{-- Vencimientos próximos --}}
          <section class="mb-2">
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
                        {{ $v->operacion ?: '—' }} — {{ $v->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio' }} #{{ $v->nro }}
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
  </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js (CDN) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Buscar por DNI -> /clientes/{dni}
  (() => {
    const frm = document.getElementById('frmQuickDni');
    const input = document.getElementById('inpQuickDni');
    if (!frm || !input) return;
    const SHOW_URL = @json(route('clientes.show','__DNI__'));
    frm.addEventListener('submit', (e) => {
      e.preventDefault();
      const dni = (input.value || '').replace(/\D/g,'').slice(0,12);
      if (!dni) { input.focus(); return; }
      window.location.assign(SHOW_URL.replace('__DNI__', encodeURIComponent(dni)));
    });
  })();

  // ===== Gráfico de pagos por día del mes =====
  const labels = @json($chartLabels);
  const data    = @json($chartData);
  const ctx = document.getElementById('pagosChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'S/ por día',
          data,
          borderColor: '#dc2626',
          backgroundColor: 'rgba(220,38,38,.15)',
          borderWidth: 2,
          borderRadius: 6
        }]
      },
      options: {
        maintainAspectRatio: false,
        scales: {
          x: { grid: { display:false }},
          y: { beginAtZero:true, ticks:{ callback:(v)=>'S/ '+v.toLocaleString() } }
        },
        plugins: {
          legend: { display:false },
          tooltip: { callbacks:{ label:(ctx)=>'S/ '+Number(ctx.parsed.y||0).toLocaleString(undefined,{minimumFractionDigits:2}) } }
        }
      }
    });
  }
</script>
@endpush

