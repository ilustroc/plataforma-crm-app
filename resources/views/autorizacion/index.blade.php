{{-- resources/views/autorizacion/index.blade.php --}}
@extends('layouts.app')

@section('title','Autorización de Promesas')
@section('crumb','Autorización')

@section('content')
@php use Illuminate\Support\Carbon; @endphp

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">Autorización de Promesas · <small class="text-muted">{{ $isSupervisor ? 'Bandeja del Supervisor' : 'Bandeja del Administrador' }}</small></h5>

    <form class="d-flex gap-2" method="GET" action="{{ route('autorizacion') }}">
      <input name="q" value="{{ $q }}" class="form-control" placeholder="DNI / Operación / Nota" />
      <button class="btn btn-primary" type="submit">Buscar</button>
      @if($q)
        <a href="{{ route('autorizacion') }}" class="btn btn-outline-secondary">Limpiar</a>
      @endif
    </form>
  </div>

  @if(session('ok'))
    <div class="alert alert-success py-2">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
  @endif

  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead class="table-light">
        <tr>
          <th style="width: 120px;">DNI</th>
          <th style="width: 140px;">Operación</th>
          <th style="width: 140px;">Fecha</th>
          <th style="width: 140px;" class="text-end">Monto (S/)</th>
          <th>Nota</th>
          <th style="width: 260px;" class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $p)
        @php
          $fechaYmd = $p->fecha_promesa ? Carbon::parse($p->fecha_promesa)->format('Y-m-d') : '—';
          $fechaDmy = $p->fecha_promesa ? Carbon::parse($p->fecha_promesa)->format('d/m/Y') : '';
          $crono    = $p->cuotas_json ?? [];
          $hasBalon = (bool)($p->has_balon ?? false);
          $montoMostrar = (float)($p->monto > 0 ? $p->monto : $p->monto_convenio);
        @endphp
        <tr>
          <td class="text-nowrap">{{ $p->dni }}</td>
          <td class="text-nowrap">{{ $p->operacion ?: '—' }}</td>
          <td class="text-nowrap">{{ $fechaYmd }}</td>
          <td class="text-end text-nowrap">{{ number_format($montoMostrar, 2) }}</td>
          <td title="{{ $p->nota }}">{{ $p->nota }}</td>
          <td class="text-end">
            <button
              type="button"
              class="btn btn-outline-secondary btn-sm js-ver-ficha"
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
              data-bs-toggle="modal"
              data-bs-target="#modalFicha">
              Ver ficha
            </button>

            @if($isSupervisor)
              <form class="d-inline" method="POST" action="{{ route('autorizacion.preaprobar',$p) }}">
                @csrf
                <button class="btn btn-primary btn-sm" type="submit">Pre-aprobar</button>
              </form>
              <button
                type="button"
                class="btn btn-outline-danger btn-sm js-open-rechazo"
                data-action="{{ route('autorizacion.rechazar.sup',$p) }}"
                data-bs-toggle="modal" data-bs-target="#modalRechazo">
                Rechazar
              </button>
            @else
              <form class="d-inline" method="POST" action="{{ route('autorizacion.aprobar',$p) }}">
                @csrf
                <button class="btn btn-primary btn-sm" type="submit">Aprobar</button>
              </form>
              <button
                type="button"
                class="btn btn-outline-danger btn-sm js-open-rechazo"
                data-action="{{ route('autorizacion.rechazar.admin',$p) }}"
                data-bs-toggle="modal" data-bs-target="#modalRechazo">
                Rechazar
              </button>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Sin pendientes.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ===== Modal Ficha ===== --}}
<div class="modal fade" id="modalFicha" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Detalle de Propuesta</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <div><strong>DNI:</strong> <span id="f_dni">—</span></div>
          <div><strong>Operación:</strong> <span id="f_op">—</span></div>
          <div><strong>Fecha:</strong> <span id="t_fecha">—</span></div>
          <div><strong>Tipo:</strong> <span id="t_tipo">—</span></div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <table class="table table-sm">
              <tbody>
                <tr><th style="width:40%">Cartera</th><td id="t_cartera">—</td></tr>
                <tr><th>Equipo</th><td id="t_asesor">—</td></tr>
                <tr><th>Asesor</th><td id="t_agente">—</td></tr>
                <tr><th>Entidad</th><td id="t_entidad">—</td></tr>
                <tr><th>Cliente</th><td id="t_titular">—</td></tr>
                <tr><th>Año Castigo</th><td id="t_anio">—</td></tr>
              </tbody>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm">
              <tbody>
                <tr><th style="width:40%">Propiedades</th><td id="t_prop">—</td></tr>
                <tr><th>Trabajo</th><td id="t_trab">—</td></tr>
                <tr><th>Clasificación SBS</th><td id="t_clasificacion">—</td></tr>
                <tr><th>Deuda Total</th><td id="t_deuda">—</td></tr>
                <tr><th>Capital</th><td id="t_capital">—</td></tr>
                <tr><th>Monto Campaña</th><td id="t_camp">—</td></tr>
                <tr><th>% Descuento</th><td id="t_desc">—</td></tr>
                <tr><th>Monto Negociado</th><td id="t_neg">—</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Detalle / Nota</label>
          <div id="t_detalle" class="form-control" style="min-height:60px; white-space:pre-wrap"></div>
        </div>

        {{-- Cronograma: solo para convenio --}}
        <div id="crono_wrap" class="d-none">
          <h6 class="mb-2">Cronograma de cuotas</h6>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width:70px" class="text-center">#</th>
                  <th style="width:160px" class="text-center">Fecha</th>
                  <th class="text-end">Importe (S/)</th>
                  <th style="width:120px" class="text-center">Tipo</th>
                </tr>
              </thead>
              <tbody id="crono_body"></tbody>
              <tfoot>
                <tr>
                  <td colspan="2" class="text-end fw-semibold">Total</td>
                  <td class="text-end fw-semibold" id="crono_total">0.00</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal" type="button">Cerrar</button>
      </div>
    </div>
  </div>
</div>

{{-- ===== Modal Rechazo ===== --}}
<div class="modal fade" id="modalRechazo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" id="formRechazo" action="#">
      @csrf
      <div class="modal-header">
        <h6 class="modal-title">Motivo / Nota de rechazo</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Detalle (requerido)</label>
        <textarea name="nota_estado" id="motivoTxt" class="form-control" rows="4" maxlength="500" required
                  placeholder="¿Por qué se rechaza? (máx. 500)"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-danger btn-sm" type="submit">Rechazar</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // ===== Rechazo =====
  const formRech = document.getElementById('formRechazo');
  const motivoTxt= document.getElementById('motivoTxt');
  document.querySelectorAll('.js-open-rechazo').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      formRech.setAttribute('action', btn.dataset.action);
      motivoTxt.value = '';
      setTimeout(()=> motivoTxt.focus(), 150);
    });
  });

  // ===== Ficha + cronograma =====
  const cronoWrap  = document.getElementById('crono_wrap');
  const cronoBody  = document.getElementById('crono_body');
  const cronoTotal = document.getElementById('crono_total');
  const fmt = (n)=> (Math.round((Number(n)||0)*100)/100).toFixed(2);

  document.querySelectorAll('.js-ver-ficha').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val || '—'; };

      set('f_dni', btn.dataset.dni || '—');
      set('f_op',  btn.dataset.operacion || '—');
      set('t_fecha', btn.dataset.fecha || '—');

      const tipo = (btn.dataset.tipo || '').toLowerCase();
      const hasBalon = (btn.dataset.hasbalon === '1');
      set('t_tipo', tipo === 'cancelacion' ? 'Cancelación' : (hasBalon ? 'Convenio (cuota balón)' : 'Convenio'));

      set('t_cartera', btn.dataset.cartera);
      set('t_asesor',  btn.dataset.asesor);
      set('t_agente',  btn.dataset.agente);
      set('t_entidad', btn.dataset.entidad);
      set('t_titular', btn.dataset.titular);
      set('t_anio',    btn.dataset.anio);
      set('t_prop',    btn.dataset.prop);
      set('t_trab',    btn.dataset.trabaja);
      set('t_clasificacion', btn.dataset.clasificacion);
      set('t_deuda',   btn.dataset.deuda);
      set('t_capital', btn.dataset.capital);
      set('t_camp',    btn.dataset.campania);
      set('t_desc',    (btn.dataset.desc ?? '').toString().trim() ? (btn.dataset.desc + '%') : '—');
      set('t_neg',     btn.dataset.negociado);
      set('t_detalle', btn.dataset.detalle);

      // Cronograma: solo si NO es cancelación
      let crono = [];
      try { crono = JSON.parse(btn.dataset.crono || '[]'); } catch(_) { crono = []; }

      if (tipo !== 'cancelacion' && crono.length) {
        cronoBody.innerHTML = '';
        let total = 0;
        crono.forEach(r=>{
          total += Number(r.monto) || 0;
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="text-center">${String(r.nro ?? '').padStart(2,'0')}</td>
            <td class="text-center">${r.fecha || '—'}</td>
            <td class="text-end">${fmt(r.monto)}</td>
            <td class="text-center">${r.es_balon ? 'BALÓN' : ''}</td>`;
          cronoBody.appendChild(tr);
        });
        cronoTotal.textContent = fmt(total);
        cronoWrap.classList.remove('d-none');
      } else {
        cronoBody.innerHTML = '';
        cronoTotal.textContent = '0.00';
        cronoWrap.classList.add('d-none');
      }
    });
  });
</script>
@endpush