{{-- resources/views/panel/resumen.blade.php --}}
@extends('layouts.app')
@section('title','Panel')
@section('crumb','Resumen')

@push('head')
<style>
  /* ===== Tarjetas base ===== */
  .card.pad{ background:#fff } /* fondo blanco uniforme */
  .shadow-soft{ box-shadow:0 6px 20px rgba(15,23,42,.06) }

  /* ===== KPIs ===== */
  .kpi{
    display:flex; align-items:center; gap:.75rem;
    border:1px solid var(--bs-border-color); border-radius:14px; padding:.9rem 1rem;
    background:#fff;
  }
  .kpi .ico{
    width:44px;height:44px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    background: color-mix(in oklab, var(--bs-danger) 12%, #fff);
    color: var(--bs-danger);
  }
  .kpi .lbl{ font-size:.86rem; color:var(--bs-secondary-color) }
  .kpi .val{ font-weight:800; font-size:1.15rem; line-height:1 }

  /* ===== Accesos rápidos ===== */
  .quick a{ border-radius:12px }

  /* ===== Gráfica del mes ===== */
  .chart-card .toolbar{ display:flex; align-items:center; gap:.5rem }
  .chart-wrap{
    position:relative; width:100%;
    height: 280px; /* para evitar que “caiga” */
  }

  /* ===== Notificaciones (Actividades) ===== */
  .notifs{ border:1px solid var(--bs-border-color); border-radius:16px; overflow:hidden; background:#fff }
  .notifs-header{
    background: var(--bs-danger); /* rojo sólido */
    color:#fff; font-weight:700; letter-spacing:.2px;
    padding:.7rem .95rem; display:flex; align-items:center; gap:.55rem
  }
  .notifs-body{ padding:.6rem .6rem .2rem; max-height:70vh; overflow:auto; background:#fff }
  .notif-group{ padding:.25rem .25rem .6rem }
  .notif-title{ display:flex; align-items:center; gap:.5rem; padding:.25rem .15rem; font-weight:600 }
  .notif-title .icon{
    width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;
    background: color-mix(in oklab, var(--bs-danger) 12%, #fff); color: var(--bs-danger)
  }
  .notif-count{ margin-left:auto; font-weight:700; font-size:.8rem; background:#fff; color:var(--bs-danger);
    border:1px solid color-mix(in oklab, var(--bs-danger) 35%, #fff); border-radius:999px; padding:.15rem .55rem }

  .notif-list{ list-style:none; padding-left:0; margin:0 }
  .notif-item{
    display:flex; align-items:center; gap:.7rem;
    padding:.6rem; border-radius:12px; text-decoration:none; color:inherit;
    border:1px solid transparent; background:#fff; transition:.15s
  }
  .notif-item:hover{ background:var(--bs-tertiary-bg); border-color:var(--bs-border-color) }
  .notif-dot{ width:9px;height:9px;border-radius:50%; background:var(--bs-danger) }
  .notif-body .notif-main{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
  .notif-body .notif-sub{ font-size:.85rem; color:var(--bs-secondary-color); white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
  .notif-cta{ font-size:.75rem; border:1px solid var(--bs-border-color); background:#fff; border-radius:999px; padding:.18rem .55rem; white-space:nowrap }
  .notifs-footer{ border-top:1px dashed var(--bs-border-color); padding:.5rem .6rem .6rem; background:#fff }
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="row g-3">
    {{-- ===== Columna izquierda ===== --}}
    <div class="col-lg-8">

      {{-- Bienvenida + buscador --}}
      <div class="card pad shadow-soft">
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

      {{-- KPIs --}}
      <div class="row g-3">
        <div class="col-sm-6">
          <div class="kpi shadow-soft">
            <div class="ico"><i class="bi bi-clipboard2-check"></i></div>
            <div>
              <div class="lbl">Promesas creadas hoy</div>
              <div class="val">{{ number_format($kpiPromHoy) }}</div>
            </div>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="kpi shadow-soft">
            <div class="ico"><i class="bi bi-cash-coin"></i></div>
            <div>
              <div class="lbl">Pagos registrados hoy</div>
              <div class="val">S/ {{ number_format($kpiPagosHoy,2) }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Accesos rápidos --}}
      <div class="card pad shadow-soft quick">
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('autorizacion') }}" class="btn btn-outline-danger"><i class="bi bi-inboxes me-1"></i> Autorización</a>
          <a href="{{ route('clientes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-people me-1"></i> Clientes</a>
          <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-graph-up me-1"></i> Dashboard</a>
          <a href="{{ route('reportes.pdp') }}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Reportes</a>
        </div>
      </div>

      {{-- Gráfica: Pagos del mes --}}
      <div class="card pad shadow-soft chart-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-bar-chart-steps text-danger"></i> Pagos del mes
          </h6>
          <div class="toolbar">
            @php $curr = \Carbon\Carbon::createFromFormat('Y-m',$mes); @endphp
            <a class="btn btn-outline-secondary btn-sm"
               href="{{ url()->current().'?mes='.$curr->copy()->subMonth()->format('Y-m') }}"><i class="bi bi-chevron-left"></i></a>
            <input type="month" id="mesPicker" class="form-control form-control-sm"
                   value="{{ $curr->format('Y-m') }}">
            <a class="btn btn-outline-secondary btn-sm"
               href="{{ url()->current().'?mes='.$curr->copy()->addMonth()->format('Y-m') }}"><i class="bi bi-chevron-right"></i></a>
          </div>
        </div>

        <div class="chart-wrap">
          <canvas id="chartPagos"
                  data-chart='@json(["labels"=>$chartLabels,"data"=>$chartData])'></canvas>
        </div>
      </div>

    {{-- ===== Columna derecha: Actividades ===== --}}
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
                    <div class="notif-dot"></div>
                    <div class="notif-body">
                      <div class="notif-main">
                        <span class="fw-semibold">{{ $p->dni }}</span>
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
                <div class="text-secondary small px-2 pb-2">Nada pendiente aquí.</div>
              @endforelse
            </ul>
          </section>

          {{-- CNA --}}
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
                    <div class="notif-dot" style="background:var(--bs-info)"></div>
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
                <div class="text-secondary small px-2 pb-2">Sin nuevas CNA.</div>
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
                    <div class="notif-dot" style="background:var(--bs-warning)"></div>
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
                <div class="text-secondary small px-2 pb-2">No hay vencimientos próximos.</div>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
  // Buscar por DNI -> /clientes/{dni}
  (() => {
    const frm   = document.getElementById('frmQuickDni');
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

  // Selector de mes (sin perder búsqueda actual)
  document.getElementById('mesPicker')?.addEventListener('change', (e)=>{
    const ym = e.target.value || '';
    const url = new URL(window.location.href);
    url.searchParams.set('mes', ym);
    window.location.assign(url.toString());
  });

  // Gráfica de pagos del mes
  (()=>{
    const el = document.getElementById('chartPagos');
    if(!el) return;
    const payload = (()=>{ try{ return JSON.parse(el.dataset.chart||'{}'); }catch(_){ return {}; }})();

    const labels = payload.labels || [];
    const data   = payload.data   || [];

    const ctx = el.getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'S/ por día',
          data,
          borderWidth: 2,
          borderColor: getComputedStyle(document.documentElement)
                          .getPropertyValue('--bs-danger') || '#c62828',
          backgroundColor: 'rgba(220, 53, 69, .15)', // rojo suave
          hoverBackgroundColor: 'rgba(220, 53, 69, .25)',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 250 },
        scales: {
          x: { grid: { display:false } },
          y: {
            beginAtZero:true,
            ticks: { callback:(v)=>'S/ '+Number(v).toLocaleString() }
          }
        },
        plugins: {
          legend: { display:false },
          tooltip: {
            callbacks: {
              label: (ctx)=> 'S/ ' + Number(ctx.parsed.y ?? 0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})
            }
          }
        }
      }
    });
  })();
</script>
@endpush
