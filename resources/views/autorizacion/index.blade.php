{{-- resources/views/autorizacion/index.blade.php --}}
@extends('layouts.app')
@section('title','Autorización de Promesas')
@section('crumb','Autorización')

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-body d-flex align-items-center justify-content-between">
      <h5 class="mb-0">
        Autorización de Promesas
        <small class="text-muted ms-2">{{ $isSupervisor ? 'Bandeja del Supervisor' : 'Bandeja del Administrador' }}</small>
      </h5>
      <form class="d-flex" method="GET" action="{{ route('autorizacion') }}">
        <input name="q" value="{{ $q }}" class="form-control form-control-sm me-2" placeholder="DNI / Operación / Nota">
        <button class="btn btn-primary btn-sm">Buscar</button>
        @if($q)
          <a class="btn btn-outline-secondary btn-sm ms-2" href="{{ route('autorizacion') }}">Limpiar</a>
        @endif
      </form>
    </div>
  </div>

  @if(session('ok'))
    <div class="alert alert-success mt-3 py-2">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger mt-3 py-2">{{ $errors->first() }}</div>
  @endif

  {{-- ====== Promesas ====== --}}
  <div class="card mt-3">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center">DNI</th>
              <th class="text-center">Operación</th>
              <th class="text-center">Fecha</th>
              <th class="text-end">Monto (S/)</th>
              <th>Nota</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $p)
            @php
              $fechaYmd = $p->fecha_promesa ? substr((string)$p->fecha_promesa,0,10) : '—';
              $fechaDmy = $p->fecha_promesa ? \Carbon\Carbon::parse($p->fecha_promesa)->format('d/m/Y') : '';
              $crono    = $p->cuotas_json ?? [];
              $hasBalon = (bool)($p->has_balon ?? false);
              $montoMostrar = (float)($p->monto > 0 ? $p->monto : $p->monto_convenio);
            @endphp
            <tr>
              <td class="text-center text-nowrap">{{ $p->dni }}</td>
              <td class="text-center text-nowrap">{{ $p->operacion ?: '—' }}</td>
              <td class="text-center text-nowrap">{{ $fechaYmd }}</td>
              <td class="text-end text-nowrap">{{ number_format($montoMostrar, 2) }}</td>
              <td class="text-truncate" style="max-width:420px" title="{{ $p->nota }}">{{ $p->nota }}</td>
              <td class="text-end">
                <button
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
                  data-capital-raw="{{ (float)($p->saldo_capital ?? 0) }}"
                  data-campania="{{ number_format((float)($p->monto_campania ?? 0),2) }}"
                  data-desc="{{ $p->porc_descuento ?? '—' }}"
                  data-negociado="{{ number_format($montoMostrar, 2) }}"
                  data-totalconvenio-raw="{{ (float)($p->monto_convenio ?? 0) }}"
                  data-detalle="{{ $p->nota ?? '' }}"
                  data-crono='@json($crono)'
                  data-hasbalon="{{ $hasBalon ? 1 : 0 }}"
                  data-bs-toggle="modal" data-bs-target="#modalFicha">
                  Ver ficha
                </button>

                @if($isSupervisor)
                  <form class="d-inline" method="POST" action="{{ route('autorizacion.preaprobar',$p) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm">Pre-aprobar</button>
                  </form>
                  <button class="btn btn-outline-danger btn-sm js-open-rechazo"
                          data-action="{{ route('autorizacion.rechazar.sup',$p) }}"
                          data-bs-toggle="modal" data-bs-target="#modalRechazo">
                    Rechazar
                  </button>
                @else
                  <form class="d-inline" method="POST" action="{{ route('autorizacion.aprobar',$p) }}">
                    @csrf
                    <button class="btn btn-primary btn-sm">Aprobar</button>
                  </form>
                  <button class="btn btn-outline-danger btn-sm js-open-rechazo"
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
  </div>
  {{-- ====== /Promesas ====== --}}

  {{-- ====== Solicitudes de CNA ====== --}}
  <div class="card mt-4">
    <div class="card-header fw-bold">Solicitudes de CNA</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center">DNI</th>
              <th class="text-center">No. Carta</th>
              <th>Producto</th>
              <th class="text-center">Operación</th>
              <th class="text-center">Fecha</th>
              <th>Nota</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
          @forelse($cnaRows as $cna)
            @php $ops = array_values(array_filter((array)($cna->operaciones ?? []))); @endphp

            @if(empty($ops))
              <tr>
                <td class="text-center text-nowrap">{{ $cna->dni }}</td>
                <td class="text-center text-nowrap">{{ $cna->nro_carta }}</td>
                <td>—</td>
                <td class="text-center">—</td>
                <td class="text-center text-nowrap">{{ optional($cna->created_at)->format('Y-m-d') }}</td>
                <td class="text-truncate" style="max-width:420px" title="{{ $cna->nota }}">{{ $cna->nota }}</td>
                <td class="text-end">
                  @if($isSupervisor)
                    <form class="d-inline" method="POST" action="{{ route('cna.preaprobar',$cna) }}">@csrf
                      <button class="btn btn-primary btn-sm">Pre-aprobar</button>
                    </form>
                    <form class="d-inline" method="POST" action="{{ route('cna.rechazar.sup',$cna) }}">@csrf
                      <button class="btn btn-outline-danger btn-sm">Rechazar</button>
                    </form>
                  @else
                    <form class="d-inline" method="POST" action="{{ route('cna.aprobar',$cna) }}">@csrf
                      <button class="btn btn-primary btn-sm">Aprobar</button>
                    </form>
                    <form class="d-inline" method="POST" action="{{ route('cna.rechazar.admin',$cna) }}">@csrf
                      <button class="btn btn-outline-danger btn-sm">Rechazar</button>
                    </form>
                  @endif
                </td>
              </tr>
            @else
              @foreach($ops as $op)
                @php $prod = $prodByOp[(string)$op] ?? '—'; @endphp
                <tr>
                  <td class="text-center text-nowrap">{{ $cna->dni }}</td>
                  <td class="text-center text-nowrap">{{ $cna->nro_carta }}</td>
                  <td class="text-nowrap">{{ $prod ?: '—' }}</td>
                  <td class="text-center text-nowrap">
                    <span class="badge rounded-pill text-bg-light border">{{ $op }}</span>
                  </td>
                  <td class="text-center text-nowrap">{{ optional($cna->created_at)->format('Y-m-d') }}</td>
                  <td class="text-truncate" style="max-width:420px" title="{{ $cna->nota }}">{{ $cna->nota }}</td>
                  <td class="text-end">
                    @if($isSupervisor)
                      <form class="d-inline" method="POST" action="{{ route('cna.preaprobar',$cna) }}">@csrf
                        <button class="btn btn-primary btn-sm">Pre-aprobar</button>
                      </form>
                      <form class="d-inline" method="POST" action="{{ route('cna.rechazar.sup',$cna) }}">@csrf
                        <button class="btn btn-outline-danger btn-sm">Rechazar</button>
                      </form>
                    @else
                      <form class="d-inline" method="POST" action="{{ route('cna.aprobar',$cna) }}">@csrf
                        <button class="btn btn-primary btn-sm">Aprobar</button>
                      </form>
                      <form class="d-inline" method="POST" action="{{ route('cna.rechazar.admin',$cna) }}">@csrf
                        <button class="btn btn-outline-danger btn-sm">Rechazar</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @endforeach
            @endif

          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Sin solicitudes de CNA.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
      {{-- paginación de CNA (propia) --}}
      <div class="p-2">
        {{ $cnaRows->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
  {{-- ====== /Solicitudes de CNA ====== --}}


</div>

  {{-- Rechazo --}}
  <div class="modal fade" id="modalRechazo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form class="modal-content" id="formRechazo" method="POST" action="#">
        @csrf
        <div class="modal-header"><h6 class="modal-title">Motivo / Nota de rechazo</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <textarea name="nota_estado" id="motivoTxt" class="form-control" rows="5" maxlength="500" required></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-danger" type="submit">Rechazar</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Ficha --}}
  <div class="modal fade" id="modalFicha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Detalle de Propuesta</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <strong>DNI:</strong> <span id="f_dni">—</span> &nbsp;&nbsp;
            <strong>Operación:</strong> <span id="f_op">—</span> &nbsp;&nbsp;
            <strong>Fecha:</strong> <span id="t_fecha">—</span>
          </div>

          <table class="table table-sm">
            <tbody>
              <tr><th style="width:220px">Tipo</th><td id="t_tipo">—</td></tr>
              <tr><th>Cartera</th><td id="t_cartera">—</td></tr>
              <tr><th>Equipo</th><td id="t_asesor">—</td></tr>
              <tr><th>Asesor (quien creó)</th><td id="t_agente">—</td></tr>
              <tr><th>Entidad</th><td id="t_entidad">—</td></tr>
              <tr><th>Cliente</th><td id="t_titular">—</td></tr>
              <tr><th>Año Castigo</th><td id="t_anio">—</td></tr>
              <tr><th>Propiedades</th><td id="t_prop">—</td></tr>
              <tr><th>Trabajo</th><td id="t_trab">—</td></tr>
              <tr><th>Calificación SBS</th><td id="t_clasificacion">—</td></tr>
              <tr><th>Deuda Total</th><td id="t_deuda">—</td></tr>
              <tr><th>Capital</th><td id="t_capital">—</td></tr>
              <tr><th>Monto Campaña</th><td id="t_camp">—</td></tr>
              <tr><th>% Descuento</th><td id="t_desc">—</td></tr>
              <tr><th>Monto Negociado</th><td id="t_neg">—</td></tr>
            </tbody>
          </table>

          <div class="mb-2">
            <strong>Detalle / Nota</strong>
            <div class="form-control-plaintext" id="t_detalle"></div>
          </div>

          {{-- Cronograma (si aplica) --}}
          <div id="crono_wrap" class="d-none">
            <h6 class="mt-3 mb-2" id="crono_titulo">Cronograma de cuotas</h6>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width:60px" class="text-center">#</th>
                    <th style="width:160px" class="text-center">Fecha</th>
                    <th class="text-end">Importe (S/)</th>
                  </tr>
                </thead>
                <tbody id="crono_body"></tbody>
                <tfoot>
                  <tr>
                    <th colspan="2" class="text-end">TOTAL CONVENIO</th>
                    <th class="text-end" id="crono_total">0.00</th>
                    <th></th>
                  </tr>
                  <tr id="fila_balon" class="d-none">
                    <th colspan="2" class="text-end">Cuota balón (Capital – Convenio)</th>
                    <th class="text-end" id="crono_balon">0.00</th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          {{-- /Cronograma --}}
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  // Rechazo (abre modal con action correcto)
  document.querySelectorAll('.js-open-rechazo').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.getElementById('formRechazo').setAttribute('action', btn.dataset.action);
      setTimeout(()=> document.getElementById('motivoTxt').focus(), 150);
    });
  });

  // Helpers
  const fmt = (n)=> (Math.round((Number(n)||0)*100)/100).toFixed(2);

  // Ver ficha
  document.querySelectorAll('.js-ver-ficha').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const tipo = (btn.dataset.tipo || '').toLowerCase();

      // Encabezado simple
      document.getElementById('f_dni').textContent = btn.dataset.dni || '—';
      document.getElementById('f_op').textContent  = btn.dataset.operacion || '—';
      document.getElementById('t_fecha').textContent= btn.dataset.fecha || '—';

      // Datos clave
      const set = (id, v)=> (document.getElementById('t_'+id).textContent = (v||'—'));
      set('tipo', tipo ? (tipo==='cancelacion' ? 'Cancelación' : 'Convenio') : '—');
      set('cartera', btn.dataset.cartera);
      set('asesor',  btn.dataset.asesor);
      set('agente',  btn.dataset.agente);
      set('entidad', btn.dataset.entidad);
      set('titular', btn.dataset.titular);
      set('anio',    btn.dataset.anio);
      set('prop',    btn.dataset.prop);
      set('trab',    btn.dataset.trabaja);
      set('clasificacion', btn.dataset.clasificacion);
      set('deuda',   btn.dataset.deuda);
      set('capital', btn.dataset.capital);
      set('camp',    btn.dataset.campania);
      set('desc',    (btn.dataset.desc ?? '').toString().trim() ? (btn.dataset.desc + '%') : '—');
      set('neg',     btn.dataset.negociado);
      set('detalle', btn.dataset.detalle);

      // Cronograma
      const cronoWrap  = document.getElementById('crono_wrap');
      const cronoBody  = document.getElementById('crono_body');
      const cronoTotal = document.getElementById('crono_total');
      const filaBalon  = document.getElementById('fila_balon');
      const cronoBalon = document.getElementById('crono_balon');
      const titulo     = document.getElementById('crono_titulo');

      let crono = [];
      try { crono = JSON.parse(btn.dataset.crono || '[]'); } catch(_) { crono = []; }

      // Si es cancelación, no mostramos cronograma
      if (tipo === 'cancelacion') {
        cronoWrap.classList.add('d-none');
        return;
      }

      // Título según si hay cuota balón
      const hasBalon = (btn.dataset.hasbalon === '1') || crono.some(r => !!r.es_balon);
      titulo.textContent = hasBalon ? 'Cronograma de cuotas (con balón)' : 'Cronograma de cuotas';

      // Render
      cronoBody.innerHTML = '';
      let sum = 0;
      crono.forEach(r=>{
        sum += Number(r.monto) || 0;
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td class="text-center">${String(r.nro ?? '').padStart(2,'0')}</td>
          <td class="text-center">${r.fecha || '—'}</td>
          <td class="text-end">${fmt(r.monto)}</td>
          <td class="text-center">${r.es_balon ? 'BALÓN' : ''}</td>
        `;
        cronoBody.appendChild(tr);
      });
      cronoTotal.textContent = fmt(sum);
      cronoWrap.classList.remove('d-none');

      // Cuota balón (Capital – Convenio)
      const capitalRaw  = parseFloat(btn.dataset.capitalRaw || '0');
      const convenioRaw = parseFloat(btn.dataset.totalconvenioRaw || String(sum));
      const balon = Math.max(capitalRaw - convenioRaw, 0);
      if (hasBalon || balon > 0.009) {
        filaBalon.classList.remove('d-none');
        cronoBalon.textContent = fmt(balon);
      } else {
        filaBalon.classList.add('d-none');
        cronoBalon.textContent = '0.00';
      }
    });
  });
</script>
@endpush
