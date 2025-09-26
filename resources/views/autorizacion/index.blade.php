{{-- resources/views/autorizacion/index.blade.php --}}
@extends('layouts.app')
@section('title','Autorización de Promesas')
@section('crumb','Autorización')

@push('head')
<style>
  .uxz{ --radius:14px; --pad:1rem; --shadow:0 8px 30px color-mix(in oklab,#000 14%,transparent); --shadow-soft:0 3px 18px color-mix(in oklab,#000 10%,transparent); --fz:.95rem }
  .uxz .panel{ border:1px solid var(--border); border-radius:var(--radius);
    background: radial-gradient(1200px 160px at 20% -20%, color-mix(in oklab,var(--brand) 10%, transparent), transparent 60%),
                color-mix(in oklab, var(--surface-1) 92%, transparent);
    box-shadow:var(--shadow); overflow:hidden }
  .uxz .panel-head{ display:flex; align-items:center; justify-content:space-between; gap:.8rem; padding:.95rem 1.05rem;
    background:linear-gradient(180deg, color-mix(in oklab, var(--surface-2) 70%, transparent) 0%,
                                      color-mix(in oklab, var(--surface-2) 45%, transparent) 100%);
    border-bottom:1px solid var(--border) }
  .uxz .title{ margin:0; font-weight:800; letter-spacing:.3px; display:flex; align-items:center; gap:.6rem }
  .uxz .title .mini{ font-size:.78rem; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.22px }
  .uxz .search .input-group-text{ border-top-left-radius:999px; border-bottom-left-radius:999px }
  .uxz .search .form-control{ border-left:0; border-top-right-radius:999px; border-bottom-right-radius:999px }

  .uxz .table-wrap{ padding:.6rem .8rem .9rem }
  .uxz .table{ font-size:var(--fz); margin:0 }
  .uxz .table> :not(caption)>*>*{ padding:.55rem .7rem; vertical-align:middle }
  .uxz thead th{ position:sticky; top:0; z-index:1; background: color-mix(in oklab, var(--surface-2) 70%, transparent);
    border-bottom:1px solid var(--border); text-transform:uppercase; font-size:.8rem; letter-spacing:.28px; font-weight:700 }
  [data-theme="dark"] .uxz thead th{ background: color-mix(in oklab, var(--surface-2) 48%, transparent) }
  .uxz tbody tr:nth-child(even){ background: color-mix(in oklab, var(--surface-2) 10%, transparent) }
  .uxz tbody tr:hover{ background: color-mix(in oklab, var(--brand) 10%, transparent) }
  .uxz .tc{ text-align:center }
  .uxz .money{ text-align:right; font-variant-numeric:tabular-nums }
  .uxz .nota{ max-width:36rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
  .btn-compact{ --bs-btn-padding-y:.25rem; --bs-btn-padding-x:.6rem; --bs-btn-font-size:.86rem }

  /* Modal + cronograma */
  .modal-compact .modal-dialog{ max-width:820px }
  .modal-compact .modal-content{ border-radius:14px; border:1px solid var(--border); overflow:hidden; box-shadow:var(--shadow) }
  .mc-head{ padding:.9rem 1rem; display:flex; align-items:center; justify-content:space-between; gap:.8rem;
    background: color-mix(in oklab, var(--surface-2) 60%, transparent); border-bottom:1px solid var(--border) }
  .mc-title{ margin:0; font-weight:800; letter-spacing:.25px }
  .pill-muted{ display:inline-flex; align-items:center; gap:.35rem; border:1px solid var(--border); border-radius:999px;
    padding:.12rem .6rem; font-size:.78rem; color:#64748b; background: color-mix(in oklab, var(--surface-1) 60%, transparent) }
  .mc-body{ padding:1rem; max-height:72vh; overflow:auto }
  .sheet{ border:1px solid var(--border); border-radius:12px; background:var(--surface-1); overflow:hidden; box-shadow:var(--shadow-soft) }
  .sheet .hd{ padding:.7rem 1rem; font-weight:800; text-transform:uppercase; letter-spacing:.35px;
    background: color-mix(in oklab, var(--brand) 12%, var(--surface-2)); color: color-mix(in oklab, var(--brand) 65%, #222) }
  .kv-list{ border-top:1px solid var(--border) }
  .kv-row{ display:grid; grid-template-columns:220px 1fr; gap:.7rem; padding:.5rem 1rem; border-bottom:1px solid var(--border); align-items:baseline }
  .kv-row:nth-child(even){ background: color-mix(in oklab, var(--surface-2) 10%, transparent) }
  .kv-k{ color:#374151; font-weight:700 } .kv-v{ color:#1f2937 } .kv-v.pre{ white-space:pre-wrap; line-height:1.35 }

  .tbl-crono{ font-size:.92rem }
  .tbl-crono> :not(caption)>*>*{ padding:.4rem .55rem; vertical-align:middle }
  .tbl-crono thead th{ position:sticky; top:0; z-index:1; background: color-mix(in oklab, var(--surface-2) 65%, transparent);
    text-transform:uppercase; font-size:.78rem; letter-spacing:.25px }
  .money-right{ text-align:right; font-variant-numeric:tabular-nums }
  .tag-balon{ display:inline-block; padding:.12rem .45rem; border-radius:999px; background:#fff3cd; border:1px solid #ffe082; color:#7a5a00; font-size:.75rem; font-weight:700 }
</style>
@endpush

@section('content')
@php use Illuminate\Support\Carbon; @endphp
<div class="uxz">
  <div class="panel">
    <div class="panel-head">
      <h5 class="title">
        <i class="bi bi-shield-check"></i>
        Autorización de Promesas
        <span class="mini">{{ $isSupervisor ? 'Bandeja del Supervisor' : 'Bandeja del Administrador' }}</span>
      </h5>

      <form class="d-flex align-items-center gap-2 search" method="GET" action="{{ route('autorizacion') }}">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input name="q" value="{{ $q }}" class="form-control" placeholder="DNI / Operación / Nota">
        </div>
        <button class="btn btn-primary btn-compact"><i class="bi bi-search me-1"></i>Buscar</button>
        @if($q)
          <a href="{{ route('autorizacion') }}" class="btn btn-outline-secondary btn-compact"><i class="bi bi-x-circle me-1"></i>Limpiar</a>
        @endif
      </form>
    </div>

    @if(session('ok'))   <div class="alert alert-success m-3 py-2"><i class="bi bi-check-circle me-1"></i>{{ session('ok') }}</div> @endif
    @if($errors->any())  <div class="alert alert-danger  m-3 py-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}</div> @endif

    <div class="table-wrap">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th class="tc">DNI</th>
              <th class="tc">Operación</th>
              <th class="tc">Fecha</th>
              <th class="money">Monto (S/)</th>
              <th>Nota</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $p)
            @php
              // Fecha solo YYYY-MM-DD
              $fechaYmd = $p->fecha_promesa ? Carbon::parse($p->fecha_promesa)->format('Y-m-d') : '—';
              $fechaDmy = $p->fecha_promesa ? Carbon::parse($p->fecha_promesa)->format('d/m/Y') : '';
              $crono    = $p->cuotas_json ?? [];
              $hasBalon = (bool)($p->has_balon ?? false);
              $montoMostrar = (float)($p->monto > 0 ? $p->monto : $p->monto_convenio);
            @endphp

            <tr>
              <td class="tc text-nowrap">{{ $p->dni }}</td>
              <td class="tc text-nowrap">{{ $p->operacion ?: '—' }}</td>
              <td class="tc text-nowrap">{{ $fechaYmd }}</td>
              <td class="money text-nowrap">{{ number_format($montoMostrar, 2) }}</td>
              <td class="nota" title="{{ $p->nota }}">{{ $p->nota }}</td>
              <td class="text-end">
                <button
                  class="btn btn-outline-secondary btn-compact js-ver-ficha"
                  data-tipo="{{ $p->tipo }}"
                  data-dni="{{ $p->dni }}"
                  data-operacion="{{ $p->operacion ?? '' }}"
                  data-fecha="{{ $fechaDmy }}"
                  data-cartera="{{ $p->cartera ?? '—' }}"
                  data-asesor="{{ $p->asesor_nombre ?? '—' }}"
                  data-agente="{{ $p->creador_nombre ?? '—' }}"
                  data-entidad="{{ $p->entidad ?? '—' }}"
                  data-titular="{{ $p->titular ?? '—' }}"
                  data-anio="{{ $p->anio_castigo ?? '—' }}"
                  data-prop="{{ $p->propiedades ?? '—' }}"
                  data-trabaja="{{ ($p->trabajo ?? '') === 'SI' ? 'SI' : 'NO' }}"
                  data-clasificacion="{{ $p->clasificacion ?? '—' }}"
                  data-deuda="{{ number_format((float)($p->deuda_total ?? 0),2) }}"
                  data-capital="{{ number_format((float)($p->saldo_capital ?? 0),2) }}"
                  data-campania="{{ number_format((float)($p->monto_campania ?? 0),2) }}"
                  data-desc="{{ $p->porc_descuento ?? '—' }}"
                  data-negociado="{{ number_format($montoMostrar, 2) }}"
                  data-cuotas="{{ $p->nro_cuotas ?? '' }}"
                  data-montocuota="{{ $p->monto_cuota ? number_format((float)$p->monto_cuota,2) : '' }}"
                  data-detalle="{{ $p->nota ?? '' }}"
                  data-crono='@json($crono)'
                  data-hasbalon="{{ $hasBalon ? 1 : 0 }}"
                  data-totalconvenio="{{ number_format((float)($p->monto_convenio ?? 0), 2, '.', '') }}"
                  data-bs-toggle="modal" data-bs-target="#modalFicha">
                  <i class="bi bi-eye me-1"></i> Ver ficha
                </button>

                @if($isSupervisor)
                  <button class="btn btn-primary btn-compact js-open-pre"
                          data-action="{{ route('autorizacion.preaprobar',$p) }}">
                    <i class="bi bi-check2-circle me-1"></i>Pre-aprobar
                  </button>
                  <button class="btn btn-outline-danger btn-compact js-open-rechazo"
                          data-action="{{ route('autorizacion.rechazar.sup',$p) }}"
                          data-bs-toggle="modal" data-bs-target="#modalRechazo">
                    <i class="bi bi-x-circle me-1"></i> Rechazar
                  </button>
                @else
                  <button class="btn btn-primary btn-compact js-open-apr"
                          data-action="{{ route('autorizacion.aprobar',$p) }}">
                    <i class="bi bi-check2-circle me-1"></i>Aprobar
                  </button>
                  <button class="btn btn-outline-danger btn-compact js-open-rechazo"
                          data-action="{{ route('autorizacion.rechazar.admin',$p) }}"
                          data-bs-toggle="modal" data-bs-target="#modalRechazo">
                    <i class="bi bi-x-circle me-1"></i> Rechazar
                  </button>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-secondary tc py-4">Sin pendientes.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

{{-- ===== Modales de DECISIÓN ===== --}}
{{-- Rechazar --}}
<div class="modal fade" id="modalRechazo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" id="formRechazo" action="#">
      @csrf
      <div class="modal-header">
        <h6 class="modal-title d-flex align-items-center gap-2"><i class="bi bi-x-octagon"></i> Motivo / Nota de rechazo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Detalle (requerido)</label>
        <textarea name="nota_estado" id="motivoTxt" class="form-control" rows="4" maxlength="500" required
                  placeholder="¿Por qué se rechaza? (máx. 500)"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-compact" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-danger btn-compact" type="submit"><i class="bi bi-x-circle me-1"></i> Rechazar</button>
      </div>
    </form>
  </div>
</div>

{{-- Pre-aprobar --}}
<div class="modal fade" id="modalPre" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" id="formPre" action="#">
      @csrf
      <div class="modal-header">
        <h6 class="modal-title d-flex align-items-center gap-2"><i class="bi bi-check2-square"></i> Nota de pre-aprobación</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Detalle (opcional)</label>
        <textarea name="nota_estado" class="form-control" rows="3" maxlength="500"
                  placeholder="¿Por qué se pre-aprueba? (máx. 500)"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-compact" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-primary btn-compact" type="submit"><i class="bi bi-check2-circle me-1"></i> Pre-aprobar</button>
      </div>
    </form>
  </div>
</div>

{{-- Aprobar --}}
<div class="modal fade" id="modalApr" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" id="formApr" action="#">
      @csrf
      <div class="modal-header">
        <h6 class="modal-title d-flex align-items-center gap-2"><i class="bi bi-check2-all"></i> Nota de aprobación</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Detalle (opcional)</label>
        <textarea name="nota_estado" class="form-control" rows="3" maxlength="500"
                  placeholder="¿Por qué se aprueba? (máx. 500)"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-compact" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-primary btn-compact" type="submit"><i class="bi bi-check2-circle me-1"></i> Aprobar</button>
      </div>
    </form>
  </div>
</div>

{{-- ===== Modal Ficha ===== --}}
<div class="modal fade modal-compact" id="modalFicha" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="mc-head">
        <div class="d-flex flex-column">
          <h6 class="mc-title">Detalle de Propuesta</h6>
          <div class="d-flex flex-wrap gap-2">
            <span class="pill-muted">DNI: <b id="f_dni">—</b></span>
            <span class="pill-muted">Operación: <b id="f_op">—</b></span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="mc-body">
        <div class="sheet">
          <div class="hd" id="sheet_title">Cancelación</div>
          <div class="kv-list">
            <div class="kv-row"><div class="kv-k">Cartera</div><div class="kv-v" id="t_cartera">—</div></div>
            <div class="kv-row"><div class="kv-k">Equipo</div><div class="kv-v" id="t_asesor">—</div></div>
            <div class="kv-row"><div class="kv-k">Asesor</div><div class="kv-v" id="t_agente">—</div></div>
            <div class="kv-row"><div class="kv-k">Entidad</div><div class="kv-v" id="t_entidad">—</div></div>
            <div class="kv-row"><div class="kv-k">Cliente</div><div class="kv-v" id="t_titular">—</div></div>
            <div class="kv-row"><div class="kv-k">DNI</div><div class="kv-v" id="t_dni">—</div></div>
            <div class="kv-row"><div class="kv-k">Nro. Operación</div><div class="kv-v" id="t_op">—</div></div>
            <div class="kv-row"><div class="kv-k">Año Castigo</div><div class="kv-v" id="t_anio">—</div></div>
            <div class="kv-row"><div class="kv-k">Propiedades</div><div class="kv-v" id="t_prop">—</div></div>
            <div class="kv-row"><div class="kv-k">Trabajo</div><div class="kv-v" id="t_trab">—</div></div>
            <div class="kv-row"><div class="kv-k">Calificación SBS</div><div class="kv-v" id="t_clasificacion">—</div></div>
            <div class="kv-row"><div class="kv-k">Deuda Total</div><div class="kv-v" id="t_deuda">—</div></div>
            <div class="kv-row"><div class="kv-k">Capital</div><div class="kv-v" id="t_capital">—</div></div>
            <div class="kv-row"><div class="kv-k">Monto Campaña</div><div class="kv-v" id="t_camp">—</div></div>
            <div class="kv-row"><div class="kv-k">% Descuento Campaña</div><div class="kv-v" id="t_desc">—</div></div>
            <div class="kv-row"><div class="kv-k">Monto Negociado</div><div class="kv-v" id="t_neg">—</div></div>
            <div class="kv-row"><div class="kv-k">Fecha de Pago</div><div class="kv-v" id="t_fecha">—</div></div>
            <div class="kv-row"><div class="kv-k">Detalle / Nota</div><div class="kv-v pre" id="t_detalle">—</div></div>
          </div>
        </div>

        {{-- Cronograma (solo para convenio) --}}
        <div class="sheet mt-3 d-none" id="crono_sheet">
          <div class="hd">Cronograma de cuotas</div>
          <div class="p-2">
            <div class="table-responsive">
              <table class="table table-sm tbl-crono align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width:70px" class="tc">#</th>
                    <th style="width:160px" class="tc">Fecha</th>
                    <th class="money-right">Importe (S/)</th>
                    <th style="width:120px" class="tc">Tipo</th>
                  </tr>
                </thead>
                <tbody id="crono_body"></tbody>
                <tfoot>
                  <tr>
                    <td colspan="2" class="text-end fw-bold">Total</td>
                    <td class="money-right fw-bold" id="crono_total">0.00</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        {{-- /Cronograma --}}
      </div>
      <div class="mc-foot"><button class="btn btn-outline-secondary btn-compact" data-bs-dismiss="modal" type="button">Cerrar</button></div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // ===== Modales de decisión =====
  const formRech = document.getElementById('formRechazo');
  const formPre  = document.getElementById('formPre');
  const formApr  = document.getElementById('formApr');
  const motivoTxt= document.getElementById('motivoTxt');

  document.querySelectorAll('.js-open-rechazo').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      formRech.setAttribute('action', btn.dataset.action);
      motivoTxt.value = '';
      setTimeout(()=> motivoTxt.focus(), 180);
    });
  });
  document.querySelectorAll('.js-open-pre').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      formPre.setAttribute('action', btn.dataset.action);
      new bootstrap.Modal(document.getElementById('modalPre')).show();
    });
  });
  document.querySelectorAll('.js-open-apr').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      formApr.setAttribute('action', btn.dataset.action);
      new bootstrap.Modal(document.getElementById('modalApr')).show();
    });
  });

  // ===== Modal Ficha (detalle + cronograma) =====
  const cronoSheet = document.getElementById('crono_sheet');
  const cronoBody  = document.getElementById('crono_body');
  const cronoTotal = document.getElementById('crono_total');
  function fmt(n){ return (Math.round((Number(n)||0)*100)/100).toFixed(2); }

  document.querySelectorAll('.js-ver-ficha').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const tipo = (btn.dataset.tipo || '').toLowerCase();
      const set = (id, val) => { const el = document.getElementById('t_'+id); if(el) el.textContent = val || '—'; };

      document.getElementById('f_dni').textContent = btn.dataset.dni || '—';
      document.getElementById('f_op').textContent  = btn.dataset.operacion || '—';
      document.getElementById('sheet_title').textContent = (tipo === 'convenio') ? 'Convenio' : 'Cancelación total';

      set('cartera', btn.dataset.cartera); set('asesor', btn.dataset.asesor); set('agente', btn.dataset.agente);
      set('entidad', btn.dataset.entidad); set('titular', btn.dataset.titular); set('dni', btn.dataset.dni);
      set('op', btn.dataset.operacion); set('anio', btn.dataset.anio); set('prop', btn.dataset.prop);
      set('trab', btn.dataset.trabaja); set('clasificacion', btn.dataset.clasificacion); set('deuda', btn.dataset.deuda);
      set('capital', btn.dataset.capital); set('camp', btn.dataset.campania);
      set('desc', (btn.dataset.desc ?? '').toString().trim() ? (btn.dataset.desc + '%') : '—');
      set('neg', btn.dataset.negociado); set('fecha', btn.dataset.fecha); set('detalle', btn.dataset.detalle);

      // Cronograma
      let crono = [];
      try { crono = JSON.parse(btn.dataset.crono || '[]'); } catch(_) { crono = []; }

      if (tipo === 'convenio' && crono.length) {
        cronoBody.innerHTML = '';
        let sum = 0;
        crono.forEach(r=>{
          sum += Number(r.monto) || 0;
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="tc">${String(r.nro ?? '').padStart(2,'0')}</td>
            <td class="tc">${r.fecha || '—'}</td>
            <td class="money-right">${fmt(r.monto)}</td>
            <td class="tc">${r.es_balon ? '<span class="tag-balon">BALÓN</span>' : ''}</td>`;
          cronoBody.appendChild(tr);
        });
        cronoTotal.textContent = fmt(sum);
        cronoSheet.classList.remove('d-none');
      } else {
        cronoBody.innerHTML = '';
        cronoTotal.textContent = '0.00';
        cronoSheet.classList.add('d-none');
      }
    });
  });
</script>
@endpush

