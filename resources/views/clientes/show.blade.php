{{-- resources/views/clientes/show.blade.php --}}
@extends('layouts.app')
@section('title','Cliente '.$dni)
@section('crumb','Cliente')

@push('head')
<style>
  /* ====== Densidad / helpers ====== */
  .tbl-compact.table> :not(caption)>*>*{ padding:.5rem .65rem }
  .max-h-320{ max-height:320px; overflow:auto }
  .max-h-260{ max-height:260px; overflow:auto }

  /* ====== Encabezado ====== */
  .cli-head .dni-pill{
    display:inline-flex; align-items:center; gap:.4rem;
    background:color-mix(in oklab, var(--brand) 10%, transparent);
    color:var(--brand);
    border:1px solid color-mix(in oklab, var(--brand) 25%, transparent);
    padding:.18rem .55rem; border-radius:999px; font-weight:600
  }
  .cli-head .meta{ display:flex; flex-wrap:wrap; gap:.5rem 1rem; color:var(--muted) }
  .kpi-mini{ background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:10px 12px }
  .kpi-mini .label{ color:var(--muted); font-size:.83rem }
  .kpi-mini .value{ font-weight:800; font-size:1.15rem; line-height:1.1 }

  /* ====== Tablas ====== */
  .tbl-compact thead th{
    position: sticky; top: 0; z-index: 1;
    background: color-mix(in oklab, var(--surface-2) 55%, transparent);
    text-transform: uppercase; font-size:.8rem; letter-spacing:.3px;
    border-bottom:1px solid var(--border);
    box-shadow:0 3px 8px rgba(15,23,42,.06);
  }
  [data-theme="dark"] .tbl-compact thead th{
    background: color-mix(in oklab, var(--surface-2) 42%, transparent);
    box-shadow:0 3px 10px rgba(0,0,0,.25);
  }
  .tbl-compact tbody tr:nth-child(even){
    background: color-mix(in oklab, var(--surface-2) 14%, transparent);
  }
  .tbl-compact tbody tr:hover{
    background: color-mix(in oklab, var(--brand) 10%, transparent);
  }
  .tbl-compact tfoot td{
    background: color-mix(in oklab, var(--surface-2) 35%, transparent);
    font-weight:700; border-top:1px solid var(--border);
  }

  /* ====== Promesas: look de fila destacada por estado ====== */
  .promesas .table>tbody>tr{ transition:box-shadow .2s ease, transform .05s ease }
  .promesas .table>tbody>tr:hover{ box-shadow:0 1px 0 rgba(15,23,42,.06) inset, 0 2px 10px rgba(15,23,42,.06) }

  .promesas .pp-state-preaprobada  { box-shadow: 4px 0 0 0 #7aa7f7 inset; background: color-mix(in oklab,#e9f1ff 25%, transparent) }
  .promesas .pp-state-aprobada     { box-shadow: 4px 0 0 0 #57b485 inset; background: color-mix(in oklab,#e7fbf1 22%, transparent) }
  .promesas .pp-state-rechazada    { box-shadow: 4px 0 0 0 #e38074 inset; background: color-mix(in oklab,#feecec 20%, transparent) }
  .promesas .pp-state-pendiente    { box-shadow: 4px 0 0 0 #cfd7e1 inset; background: color-mix(in oklab,#f3f6fa 18%, transparent) }

  .promesas .card-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.5rem }
  .promesas .hint{ color:var(--muted); font-size:.85rem }
  .promesas .nota-clamp{
    display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical;
    overflow:hidden; max-width: 28rem;
  }

  .badge-pill{ border-radius:999px; padding:.18rem .6rem; font-weight:700 }
  .badge-pp-warning{ background:#f7e9c4; color:#7a5a00; border:1px solid #e7d08d }
  .badge-pp-info{ background:#dfeffd; color:#0b4e9b; border:1px solid #b7d3fb }
  .badge-pp-success{ background:#dff7ea; color:#0a6b3a; border:1px solid #bce9d0 }
  .badge-pp-danger{ background:#fde0de; color:#8a1a10; border:1px solid #f3b7b2 }
  .badge-pp-secondary{ background:#e9edf3; color:#495869; border:1px solid #cfd7e1 }

  .badge-soft{
    background:color-mix(in oklab, var(--brand) 12%, transparent);
    color:var(--brand);
    border:1px solid color-mix(in oklab, var(--brand) 24%, transparent);
    border-radius:999px; padding:.18rem .55rem; font-weight:600
  }

  .table-responsive::-webkit-scrollbar{ height:10px }
  .table-responsive::-webkit-scrollbar-thumb{ background: color-mix(in oklab, var(--brand) 22%, transparent); border-radius:10px }

  /* ====== DECISIONES ====== */
  .decision-cell{ min-width:260px }
  .decision-cell .nota-clamp{ -webkit-line-clamp:2; max-width:32rem }
  .decision-cell .meta{ color:var(--muted); font-size:.82rem }

  .decision-box{
    border:1px solid var(--border);
    border-left-width:4px;
    border-radius:12px;
    padding:.45rem .6rem;
    background:var(--surface);
  }
  .decision-box.is-preaprobada{ border-left-color:#7aa7f7; background:color-mix(in oklab,#dfeffd 35%, transparent) }
  .decision-box.is-aprobada{ border-left-color:#57b485; background:color-mix(in oklab,#dff7ea 35%, transparent) }
  .decision-box.is-rechazada{ border-left-color:#e38074; background:color-mix(in oklab,#fde0de 35%, transparent) }
  .decision-box.is-pendiente{ border-left-color:#cfd7e1; background:color-mix(in oklab,#e9edf3 35%, transparent) }
</style>
@endpush

@section('content')
  @if(session('ok')) <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle me-2"></i><div>{{ session('ok') }}</div></div> @endif
  @if($errors->any()) <div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i><div>{{ $errors->first() }}</div></div> @endif

  {{-- ENCABEZADO --}}
  <div class="card pad cli-head mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h5 mb-1 d-flex align-items-center gap-2"><i class="bi bi-person-badge"></i><span>{{ $titular }}</span></h1>
        <div class="meta">
          <div class="d-flex align-items-center gap-1">
            <span class="dni-pill"><i class="bi bi-credit-card-2-front"></i> DNI {{ $dni }}</span>
            <button class="btn btn-outline-secondary btn-sm ms-1" id="btnCopyDni" type="button" title="Copiar DNI" data-bs-toggle="tooltip"><i class="bi bi-clipboard"></i></button>
          </div>
          @if(isset($cuentas) && count($cuentas)) <div><i class="bi bi-wallet2 me-1"></i>{{ count($cuentas) }} cuenta(s)</div>@endif
          @if(isset($pagos)) <div><i class="bi bi-receipt me-1"></i>{{ count($pagos) }} pago(s)</div>@endif
          @if(isset($promesas)) <div><i class="bi bi-flag me-1"></i>{{ count($promesas) }} promesa(s)</div>@endif
        </div>
      </div>
      @php
        $totSaldo = (float)($cuentas->sum('saldo_capital') ?? 0);
        $totDeuda = (float)($cuentas->sum('deuda_total') ?? 0);
        $totPagos = (float)($pagos->sum('monto') ?? 0);
      @endphp
      <div class="d-flex flex-wrap gap-2">
        <div class="kpi-mini text-end"><div class="label">Saldo capital</div><div class="value">S/ {{ number_format($totSaldo,2) }}</div></div>
        <div class="kpi-mini text-end"><div class="label">Deuda total</div><div class="value">S/ {{ number_format($totDeuda,2) }}</div></div>
        <div class="kpi-mini text-end"><div class="label">Pagos registrados</div><div class="value">S/ {{ number_format($totPagos,2) }}</div></div>
      </div>
    </div>
  </div>

  {{-- CUENTAS --}} 
  <div class="card pad mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h6 mb-0 d-flex align-items-center gap-2"><i class="bi bi-wallet2"></i><span>Cuentas</span></h2>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm" id="btnCopyCtas" type="button" title="Copiar cuentas" data-bs-toggle="tooltip"><i class="bi bi-clipboard"></i> Copiar</button>
        {{-- Generar propuesta (habilita con selección) --}}
        <button class="btn btn-primary btn-sm" id="btnPropuesta" type="button" data-bs-toggle="modal" data-bs-target="#modalPropuesta" disabled>
          <i class="bi bi-flag"></i> Generar propuesta
          <span class="ms-1 badge rounded-pill text-bg-light align-middle" id="selCount">0</span>
        </button>
      </div>
    </div>

    <div class="table-responsive max-h-320">
      <table class="table table-sm table-striped table-hover align-middle tbl-compact mb-0" id="tblCuentas">
        <thead>
          <tr>
            <th class="text-center" style="width:36px"><input type="checkbox" id="chkAll"></th>
            <th>Cartera</th>
            <th class="text-nowrap">Operación</th>
            <th>Moneda</th>
            <th>Entidad</th>
            <th>Producto</th>
            <th class="text-end text-nowrap">Saldo Capital</th>
            <th class="text-end text-nowrap">Deuda Total</th>
            <th class="text-nowrap">
              Pagos
              <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" title="Conteo y total de pagos aplicados a esta operación."></i>
            </th>
          </tr>
        </thead>
        <tbody>
          @foreach ($cuentas as $c)
            @php
              $cnt = (int)($c->pagos_count ?? 0);
              $sum = (float)($c->pagos_sum ?? 0);
              $hasList = isset($c->pagos_list) && (($c->pagos_list instanceof \Illuminate\Support\Collection && $c->pagos_list->count()) || (is_array($c->pagos_list) && count($c->pagos_list)));
              $collapseId = 'pagos-'.$loop->index;
            @endphp
            <tr>
              <td class="text-center">
                <input type="checkbox" class="chkOp" value="{{ $c->operacion }}" {{ empty($c->operacion) ? 'disabled' : '' }}>
              </td>
              <td class="text-nowrap">{{ $c->cartera ?? '—' }}</td>
              <td class="text-nowrap">{{ $c->operacion ?? '—' }}</td>
              <td>{{ $c->moneda ?? '—' }}</td>
              <td>{{ $c->entidad ?? '—' }}</td>
              <td>{{ $c->producto ?? '—' }}</td>
              <td class="text-end text-nowrap">{{ number_format((float)($c->saldo_capital ?? 0), 2) }}</td>
              <td class="text-end text-nowrap">{{ number_format((float)($c->deuda_total ?? 0), 2) }}</td>
              <td class="text-nowrap">
                <span class="badge rounded-pill text-bg-light border">{{ $cnt }} pago(s)</span>
                @if($sum > 0)
                  <small class="text-secondary ms-1">· S/ {{ number_format($sum, 2) }}</small>
                @endif
                @if($hasList)
                  <button class="btn btn-sm btn-outline-secondary ms-2" type="button"
                          data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                          aria-expanded="false" aria-controls="{{ $collapseId }}">
                    Ver detalle
                  </button>
                  <div class="collapse mt-2" id="{{ $collapseId }}">
                    <ul class="list-unstyled mb-0 small">
                      @foreach($c->pagos_list as $p)
                        @php
                          $f = !empty($p->fecha) ? \Carbon\Carbon::parse($p->fecha)->format('d/m/Y') : '—';
                          $m = number_format((float)($p->monto ?? 0), 2);
                          $src = $p->fuente ?? '—';
                        @endphp
                        <li class="d-flex align-items-center gap-2">
                          <i class="bi bi-dot"></i>
                          <span class="text-nowrap">{{ $f }}</span>
                          <span>· S/ {{ $m }}</span>
                          <span class="text-secondary">· {{ $src }}</span>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- PAGOS --}}
  <div class="card pad mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <h2 class="h6 mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-receipt"></i><span>Pagos</span>
      </h2>
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm" id="btnCopyPag" type="button" title="Copiar pagos" data-bs-toggle="tooltip"><i class="bi bi-clipboard"></i> Copiar</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#pagosCollapse">Ver/ocultar</button>
      </div>
    </div>

    <div id="pagosCollapse" class="collapse mt-2">
      <div class="table-responsive max-h-260">
        <table class="table table-sm align-middle tbl-compact" id="tblPagos">
          <thead>
            <tr><th>Fecha</th><th class="text-end">Monto (S/)</th><th>Operación/Pagaré</th><th>Fuente</th></tr>
          </thead>
          <tbody>
            @forelse($pagos as $p)
              <tr>
                <td class="text-nowrap">{{ $p->fecha? \Carbon\Carbon::parse($p->fecha)->format('d/m/Y'):'' }}</td>
                <td class="text-end">{{ number_format((float)$p->monto,2) }}</td>
                <td class="text-nowrap">{{ $p->referencia }}</td>
                <td><span class="badge-soft">{{ strtoupper($p->fuente ?? '-') }}</span></td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-secondary">Sin pagos</td></tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr><td class="text-end">Total</td><td class="text-end">S/ {{ number_format($totPagos,2) }}</td><td colspan="2"></td></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  {{-- PROMESAS --}}
  <div class="promesas">
    <div class="card pad h-100">
      <div class="card-head">
        <h2 class="h6 mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-flag"></i> <span>Promesas de pago</span>
        </h2>
        @if(($promesas ?? collect())->count())
          <div class="hint">{{ $promesas->count() }} registro(s)</div>
        @endif
      </div>

      <div class="table-responsive max-h-320">
        <table class="table align-middle tbl-compact" id="tblPromesas">
          <thead>
            <tr>
              <th>Fecha</th>
              <th class="text-nowrap">Tipo</th>
              <th class="text-end">Monto</th>
              <th class="text-nowrap">Operación(es)</th>
              <th>Plan</th>
              <th>Compromiso</th>
              <th class="text-nowrap">Decisión / Nota</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($promesas as $pp)
              @php
                $notaDec  = $pp->decision_nota;
                $autorDec = $pp->decision_user_name;
                $fechaFmt = $pp->decision_at?->format('d/m/Y H:i');
                $state    = $pp->workflow_estado ?? 'pendiente'; // preaprobada|aprobada|rechazada|pendiente
                $rowClass = 'pp-state-'.str_replace(['pre-aprobada',' '], ['preaprobada',''], strtolower($state));
              @endphp
              <tr class="{{ $rowClass }}">
                {{-- Fecha --}}
                <td class="text-nowrap">{{ optional($pp->fecha_promesa)->format('d/m/Y') ?? '—' }}</td>

                {{-- Tipo --}}
                <td>
                  <span class="badge badge-pill {{ $pp->tipo_badge_class }}">
                    {{ $pp->tipo_label }}
                  </span>
                </td>

                {{-- Monto principal (si monto = 0, cae a monto_convenio) --}}
                <td class="text-end text-nowrap">
                  S/ {{ number_format((float)(($pp->monto ?? 0) > 0 ? $pp->monto : ($pp->monto_convenio ?? 0)), 2) }}
                </td>

                {{-- Operaciones --}}
                <td class="text-nowrap">
                  @php
                    $ops = $pp->relationLoaded('operaciones') ? $pp->operaciones->pluck('operacion')->all() : [];
                    if (empty($ops) && !empty($pp->operacion)) $ops = [$pp->operacion];
                  @endphp
                  @if($ops)
                    @foreach($ops as $o)
                      <span class="badge rounded-pill text-bg-light border me-1">{{ $o }}</span>
                    @endforeach
                  @else
                    <span class="text-secondary">—</span>
                  @endif
                </td>

                {{-- Plan (convenio) --}}
                <td class="small">
                  @if($pp->tipo === 'convenio')
                    {{ (int)($pp->nro_cuotas ?? 0) }} cuota(s)
                    @if($pp->monto_cuota)     · Cuota: S/ {{ number_format((float)$pp->monto_cuota,2) }} @endif
                    @if($pp->monto_convenio)  · Convenio: S/ {{ number_format((float)$pp->monto_convenio,2) }} @endif
                    @if($pp->fecha_pago)      · 1ª: {{ optional($pp->fecha_pago)->format('d/m/Y') }} @endif
                  @else
                    <span class="text-secondary">—</span>
                  @endif
                </td>

                {{-- Compromiso --}}
                <td class="small">
                  @if($pp->fecha_pago)
                    <div>{{ optional($pp->fecha_pago)->format('d/m/Y') }}</div>
                  @endif

                  @if($pp->tipo === 'cancelacion')
                    <div>Cancelación: S/ {{ number_format((float)($pp->monto ?? 0),2) }}</div>
                  @elseif($pp->tipo === 'convenio' && $pp->monto_cuota)
                    <div>Convenio: S/ {{ number_format((float)$pp->monto_cuota,2) }}</div>
                  @endif
                </td>

                {{-- Decisión / Nota --}}
                <td class="decision-cell">
                  <div class="d-flex flex-column gap-2">
                    <span class="badge badge-pill {{ $pp->workflow_badge_class }}">
                      {{ $pp->workflow_estado_label }}
                    </span>

                    @if($notaDec)
                      <div class="decision-box {{ $pp->decision_css_class }}">
                        <div class="small text-secondary nota-clamp" title="{{ $notaDec }}">{{ $notaDec }}</div>
                        <div class="meta mt-1">
                          @if($autorDec)<i class="bi bi-person"></i> {{ $autorDec }} @endif
                          @if($autorDec && $fechaFmt) · @endif
                          @if($fechaFmt)<i class="bi bi-clock"></i> {{ $fechaFmt }}@endif
                        </div>
                      </div>
                      <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalNota" data-nota="{{ e($notaDec) }}">
                          <i class="bi bi-journal-text me-1"></i> Ver nota
                        </button>
                        @if(($pp->nota ?? null) && trim($pp->nota) !== '')
                          <button class="btn btn-sm btn-outline-secondary"
                                  type="button"
                                  data-bs-toggle="modal" data-bs-target="#modalNota"
                                 data-nota-json='@json($pp->nota)'>
                               Ver propuesta
                          </button>
                        @endif
                      </div>
                    @else
                      <span class="text-secondary small">—</span>

                      @if(($pp->nota ?? null) && trim($pp->nota) !== '')
                        <div class="small mt-1">
                          <span class="badge rounded-pill text-bg-light border me-1">Propuesta</span>
                          <span class="text-secondary nota-clamp">{{ $pp->nota }}</span>
                          <button class="btn btn-sm btn-link p-0 ms-1" type="button"
                                  data-bs-toggle="modal" data-bs-target="#modalNota"
                                  data-nota='@js($pp->nota)'>Ver</button>
                        </div>
                      @endif
                    @endif
                  </div>
                </td>

                {{-- Acciones --}}
                <td class="text-end">
                  @if(($pp->workflow_estado ?? '') === 'aprobada' || ($pp->workflow_estado ?? '') === 'preaprobada')
                    <a class="btn btn-outline-primary btn-sm"
                       href="{{ route('promesas.acuerdo', $pp) }}"
                       target="_blank" data-bs-toggle="tooltip" title="Descargar acuerdo en PDF">
                      <i class="bi bi-filetype-pdf me-1"></i> PDF
                    </a>
                  @else
                    <span class="text-secondary small">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-secondary">Sin promesas</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Modal Nota --}}
  <div class="modal fade" id="modalNota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-journal-text me-1"></i> Nota</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <!-- usa white-space:pre-wrap para mostrar saltos de línea -->
        <div class="modal-body"><div id="notaFull" class="mb-0" style="white-space:pre-wrap"></div></div>
        <div class="modal-footer"><button class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button></div>
      </div>
    </div>
  </div>

  {{-- MODAL: Generar Propuesta --}}
  <div class="modal fade" id="modalPropuesta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('clientes.promesas.store', $dni) }}" id="formPropuesta">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-flag me-1"></i> Generar propuesta</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            {{-- Operaciones seleccionadas --}}
            <div class="mb-3">
              <div class="form-label">Operaciones a incluir</div>
              <div id="opsResumen" class="small"></div>
              <div id="opsHidden"></div>
            </div>

            {{-- Tipo de propuesta --}}
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <label class="form-label">Tipo de propuesta</label>
                <select name="tipo" id="tipoPropuesta" class="form-select" required>
                  <optgroup label="Convenios">
                    <option value="convenio" data-balon="0">Convenio</option>
                    <option value="convenio" data-balon="1">Convenio (cuota balón)</option>
                  </optgroup>
                  <optgroup label="Otros">
                    <option value="cancelacion">Cancelación</option>
                  </optgroup>
                </select>
                <!-- bandera opcional por si quieres leerla en backend en el futuro -->
                <input type="hidden" name="force_balon" id="forceBalon" value="0">
              </div>
              <div class="col-md-6">
                <label class="form-label">Observación (opcional)</label>
                <input name="nota" class="form-control" maxlength="500" placeholder="Detalle (máx. 500)">
              </div>
            </div>

            {{-- CONVENIO (cronograma sin "Balón") --}}
            <div id="formConvenio" class="row g-2">
              <div class="col-md-3">
                <label class="form-label">Nro cuotas</label>
                <input type="number" min="1" step="1" name="nro_cuotas" id="cvNro" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Monto convenio (S/)</label>
                <input type="number" step="0.01" min="0.01" name="monto_convenio" id="cvTotal" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Monto de cuota (S/) (sugerido)</label>
                <input type="number" step="0.01" min="0.01" name="monto_cuota" id="cvCuota" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label">Fecha inicial (opción auto)</label>
                <input type="date" id="cvFechaIni" class="form-control">
                <div class="form-text" id="cvHintDia">Día de pago: —</div>
              </div>
            
              <div class="col-12">
                <div class="d-flex gap-2 align-items-center mb-2">
                  <button type="button" id="cvGen" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-magic"></i> Generar cronograma
                  </button>
                  <span class="text-secondary small">Puedes editar fechas y montos después de generar.</span>
                </div>
            
                <div class="table-responsive">
                  <table class="table table-sm align-middle tbl-compact" id="tblCrono">
                    <thead>
                      <tr>
                        <th style="width:70px">#</th>
                        <th style="width:180px">Fecha</th>
                        <th>Importe (S/)</th>
                      </tr>
                    </thead>
                    <tbody>
                      {{-- filas dinámicas --}}
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="2" class="text-end fw-bold">Total cronograma</td>
                        <td><span id="cvSuma" class="fw-bold">0.00</span></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
            
                {{-- inputs ocultos que se envían --}}
                <div id="cvHidden"></div>
            
                <div class="row g-2 mt-2">
                  <div class="col-md-4">
                    <div class="form-text">Deuda capital seleccionada: <b>S/ <span id="cvCapSel">0.00</span></b></div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-text">Total cronograma: <b>S/ <span id="cvSuma">0.00</span></b></div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-text">Cuota balón estimada (capital − convenio): <b>S/ <span id="cvBalonEst">0.00</span></b></div>
                  </div>
                </div>
            
                <div class="form-text mt-1">
                  * El total del cronograma debe coincidir con el <b>Monto convenio</b>.
                </div>
              </div>
            </div>

            {{-- CANCELACIÓN --}}
            <div id="formCancelacion" class="row g-2 d-none">
              <div class="col-md-6">
                <label class="form-label">Fecha de pago</label>
                <input type="date" name="fecha_pago_cancel" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Monto (S/)</label>
                <input type="number" step="0.01" min="0.01" name="monto_cancel" class="form-control">
              </div>
            </div>

            <div class="small text-secondary mt-2">
              * La propuesta se asociará al DNI {{ $dni }} y a las operaciones seleccionadas.
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Guardar propuesta</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- GESTIONES --}}
  <div class="card pad mt-3">
    <h2 class="h6 mb-2 d-flex align-items-center gap-2"><i class="bi bi-chat-dots"></i> Gestiones</h2>
    <div class="alert alert-info py-2 d-flex align-items-center mb-0"><i class="bi bi-info-circle me-2"></i> Aún no hay gestiones cargadas.</div>
  </div>

  {{-- DOCUMENTOS CCD --}}
  @if($ccd->count())
    <div class="card pad mt-3">
      <h2 class="h6 mb-2 d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i> Documentos CCD</h2>
      <div class="table-responsive max-h-260">
        <table class="table table-sm align-middle tbl-compact" id="tblCCD">
          <thead><tr><th>ID</th><th>Documento</th><th>Archivo</th><th>Fecha</th></tr></thead>
          <tbody>
            @foreach($ccd as $d)
              @php $path = $d->archivo ?? $d->ruta ?? $d->url ?? '-'; @endphp
              <tr>
                <td>{{ $d->id }}</td>
                <td>{{ $d->documento ?? ($d->nombre ?? '-') }}</td>
                <td class="text-truncate" style="max-width:420px;"><code class="small">{{ $path }}</code></td>
                <td class="text-nowrap">{{ isset($d->created_at)? \Carbon\Carbon::parse($d->created_at)->format('d/m/Y H:i'):'' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection

@push('scripts')
<script>
    (function(){
      // ===== Map de saldos por operación (inyectado desde Blade)
      // => { "6799186": 1400.23, ... }
      const OP_SALDOS = @json($cuentas->pluck('saldo_capital','operacion'));
    
      // ===== Refs del cronograma
      const nro   = document.getElementById('cvNro');
      const total = document.getElementById('cvTotal');   // Monto convenio
      const cuota = document.getElementById('cvCuota');   // sugerido (opcional)
      const fIni  = document.getElementById('cvFechaIni');
      const gen   = document.getElementById('cvGen');
      const tblEl = document.getElementById('tblCrono');
      const suma  = document.getElementById('cvSuma');
      const hid   = document.getElementById('cvHidden');
      const btnGuardar = document.querySelector('#formPropuesta button[type="submit"]');
      const hintDia = document.getElementById('cvHintDia');
    
      // Resumenes extra
      const capSelOut = document.getElementById('cvCapSel');   // span para capital seleccionado
      const balonOut  = document.getElementById('cvBalonEst'); // span para cuota balón estimada
    
      if (!tblEl || !nro || !total || !hid) return;
      const tbl = tblEl.querySelector('tbody');
    
      // ===== Utils
      const to2 = n => String(n).padStart(2,'0');
      const fmt = n => (Math.round((Number(n)||0)*100)/100).toFixed(2);
      const num = v => Number(String(v ?? '').replace(',','.')) || 0;
    
      const selectedOps = () =>
        [...document.querySelectorAll('#opsHidden input[name="operaciones[]"]')].map(i => i.value);
    
      const capitalSeleccionado = () =>
        selectedOps().reduce((s, op) => s + (num(OP_SALDOS?.[op])||0), 0);
    
      // Ocultar la columna BALÓN (ya no se usa en convenio estándar)
      (function hideBalonColumn(){
        const ths = document.querySelectorAll('#tblCrono thead th');
        if (ths.length >= 4) ths[3].style.display = 'none';
      })();
    
      // ===== Render de filas del cronograma
      function renderRows(n){
        tbl.innerHTML = '';
        for (let i=1; i<=n; i++){
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="text-center">${to2(i)}</td>
            <td><input type="date" class="form-control form-control-sm cr-fecha"></td>
            <td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm cr-monto"></td>
            <td style="display:none"></td>
          `;
          tbl.appendChild(tr);
        }
        recalc();
      }
    
      // ===== Recalc + sincronización de inputs ocultos
      function recalc(){
        // 1) totalizar cronograma
        let s = 0;
        const rows = [...tbl.querySelectorAll('tr')];
        rows.forEach(tr => s += num(tr.querySelector('.cr-monto')?.value));
        if (suma) suma.textContent = fmt(s);
    
        // 2) sincronizar ocultos
        hid.innerHTML = '';
        rows.forEach(tr => {
          const f = tr.querySelector('.cr-fecha')?.value || '';
          const m = tr.querySelector('.cr-monto')?.value || '';
          hid.insertAdjacentHTML('beforeend', `<input type="hidden" name="cron_fecha[]" value="${f}">`);
          hid.insertAdjacentHTML('beforeend', `<input type="hidden" name="cron_monto[]" value="${m}">`);
        });
        // (no enviamos cron_balon)
    
        // 3) resumenes: capital seleccionado y cuota balón estimada
        const capSel = capitalSeleccionado();
        const montoConvenio = num(total.value);
        const balon = Math.max(0, +(capSel - montoConvenio).toFixed(2));
        if (capSelOut) capSelOut.textContent = fmt(capSel);
        if (balonOut)  balonOut.textContent  = fmt(balon);
    
        // 4) validación: suma cronograma == monto convenio
        const ok = Math.abs(s - montoConvenio) <= 0.01;
        if (btnGuardar) btnGuardar.disabled = !ok;
        if (suma) suma.classList.toggle('text-danger', !ok);
      }
    
      // ===== Autogenerar cronograma
      function addMonthsNoOverflow(base, months){
        const d = new Date(base);
        const day = d.getDate();
        d.setMonth(d.getMonth() + months);
        if (d.getDate() !== day) d.setDate(0);
        return d;
      }
      function genAuto(){
        const n = Math.max(1, parseInt(nro.value || '0', 10));
        if (!n) return;
        if (tbl.children.length !== n) renderRows(n);
    
        const start = fIni?.value ? new Date(fIni.value + 'T00:00:00') : null;
        const m = num(cuota?.value) || (num(total.value) / n);
    
        [...tbl.querySelectorAll('tr')].forEach((tr, idx)=>{
          const f = tr.querySelector('.cr-fecha');
          const mm = tr.querySelector('.cr-monto');
          if (start){
            const d = addMonthsNoOverflow(start, idx);
            f.valueAsDate = d;
          }
          mm.value = fmt(m);
        });
        recalc();
      }
    
      // ===== Eventos cronograma
      gen?.addEventListener('click', genAuto);
      nro.addEventListener('change', ()=>{
        const n = Math.max(1, parseInt(nro.value || '1', 10));
        renderRows(n);
      });
      tbl.addEventListener('input', e=>{
        if (e.target.matches('.cr-monto, .cr-fecha')) recalc();
      });
      fIni?.addEventListener('change', ()=>{
        const v = fIni.value;
        if (!hintDia) return;
        if (!v){ hintDia.textContent = 'Día de pago: —'; return; }
        const d = new Date(v + 'T00:00:00');
        hintDia.textContent = `Día de pago: ${d.getDate()} de cada mes`;
      });
      total?.addEventListener('input', recalc);
    
      // Estado inicial
      renderRows(Math.max(1, parseInt(nro.value || '1', 10)));
    })();
    
    /* ================== Utilidades generales de la vista ================== */
    
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>{ new bootstrap.Tooltip(el); });
    
    // Copiar DNI
    document.getElementById('btnCopyDni')?.addEventListener('click', async ()=>{
      try{ await navigator.clipboard.writeText(String(@json($dni))); alert('DNI copiado.'); }catch(e){ alert('No se pudo copiar.'); }
    });
    
    // Copiar tabla (sin CSV)
    function copyTableToClipboard(tableId, cols){
      const t = document.getElementById(tableId); if(!t) return;
      const head = [...t.querySelectorAll('thead th')].map(th=>th.innerText.trim()).slice(0, cols ?? undefined);
      const body = [...t.querySelectorAll('tbody tr')].map(tr=>{
        const tds = tr.querySelectorAll('td'); const arr=[];
        for(let i=0;i<(cols ?? tds.length);i++){ arr.push((tds[i]?.innerText ?? '').trim()); }
        return arr.join('\t');
      });
      const txt = [head.join('\t'), ...body].join('\n');
      return navigator.clipboard.writeText(txt);
    }
    document.getElementById('btnCopyCtas')?.addEventListener('click', async ()=>{
      try{ await copyTableToClipboard('tblCuentas'); alert('Cuentas copiadas.'); }catch(e){ alert('No se pudo copiar.'); }
    });
    document.getElementById('btnCopyPag')?.addEventListener('click', async ()=>{
      try{ await copyTableToClipboard('tblPagos'); alert('Pagos copiados.'); }catch(e){ alert('No se pudo copiar.'); }
    });
    
    // Modal Nota — soporta data-nota y data-nota-json
    (function(){
      const modal = document.getElementById('modalNota');
      if (!modal) return;

      modal.addEventListener('show.bs.modal', (ev) => {
        const btn = ev.relatedTarget; // botón que abrió el modal
        let txt = '';
        if (!btn) return;

        if (btn.hasAttribute('data-nota-json')) {
          try { txt = JSON.parse(btn.getAttribute('data-nota-json') || '""') || ''; }
          catch { txt = ''; }
        } else if (btn.hasAttribute('data-nota')) {
          // viene ya como texto plano
          txt = btn.getAttribute('data-nota') || '';
        }

        const tgt = modal.querySelector('#notaFull');
        if (tgt) tgt.textContent = String(txt);
      });
    })();
    
    // ===== Selección de cuentas (para el modal de propuesta)
    const chkAll   = document.getElementById('chkAll');
    const chks     = Array.from(document.querySelectorAll('.chkOp'));
    const btnProp  = document.getElementById('btnPropuesta');
    const selCount = document.getElementById('selCount');
    
    function refreshSelection(){
      const selected = chks.filter(c => c.checked && !c.disabled).map(c => c.value).filter(Boolean);
      selCount.textContent = String(selected.length);
      btnProp.disabled = selected.length === 0;
      return selected;
    }
    chkAll?.addEventListener('change', () => {
      chks.forEach(c => { if(!c.disabled) c.checked = chkAll.checked; });
      refreshSelection();
    });
    chks.forEach(c => c.addEventListener('change', () => {
      const enabled = chks.filter(x => !x.disabled).length;
      const checked = chks.filter(x => x.checked && !x.disabled).length;
      if (enabled) chkAll.checked = (checked === enabled);
      refreshSelection();
    }));
    
    // ===== Modal Propuesta: llenar operaciones y recalcular capital/balón
    const modalProp = document.getElementById('modalPropuesta');
    const opsResumen = document.getElementById('opsResumen');
    const opsHidden  = document.getElementById('opsHidden');
    
    modalProp?.addEventListener('show.bs.modal', () => {
      const ops = refreshSelection();
    
      // Resumen visible
      opsResumen.innerHTML = ops.length
        ? ops.map(o => `<span class="badge rounded-pill text-bg-light border me-1">${o}</span>`).join('')
        : '<span class="text-secondary">Ninguna</span>';
    
      // Hidden inputs
      opsHidden.innerHTML = '';
      ops.forEach(op => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = 'operaciones[]'; i.value = String(op);
        opsHidden.appendChild(i);
      });
    
      // Disparar recálculo (para capital seleccionado / balón estimado)
      document.getElementById('cvTotal')?.dispatchEvent(new Event('input'));
    });
    
    // ===== Alternar bloques por tipo (y DESHABILITAR el bloque oculto)
    const tipo = document.getElementById('tipoPropuesta');
    const fCon = document.getElementById('formConvenio');
    const fCan = document.getElementById('formCancelacion');
    
    function setEnabled(container, enabled){
      if (!container) return;
      container.querySelectorAll('input,select,textarea,button').forEach(el=>{
        if (enabled) el.removeAttribute('disabled');
        else el.setAttribute('disabled','disabled');
      });
      // Limpia valores del bloque oculto para no mandar basura
      if (!enabled){
        container.querySelectorAll('input:not([type="hidden"]),textarea').forEach(el=>{ el.value=''; });
      }
    }
    function req(el, on){
      if (!el) return;
      if (on) el.setAttribute('required','required'); else el.removeAttribute('required');
    }
    function toggleTipo(){
      const t = tipo.value;
      // Mostrar/ocultar
      fCon.classList.toggle('d-none', t !== 'convenio');
      fCan.classList.toggle('d-none', t !== 'cancelacion');
      // Habilitar/deshabilitar
      setEnabled(fCon, t === 'convenio');
      setEnabled(fCan, t === 'cancelacion');
      // Requireds
      const fields = {
        convenio: ['nro_cuotas','monto_convenio'],
        cancelacion: ['fecha_pago_cancel','monto_cancel']
      };
      [...fields.convenio, ...fields.cancelacion]
        .forEach(n => req(document.querySelector(`[name="${n}"]`), false));
      (fields[t] || []).forEach(n => req(document.querySelector(`[name="${n}"]`), true));
    }
    tipo?.addEventListener('change', toggleTipo);
    toggleTipo();
    
    // Hint opcional (si usas fecha inicial)
    const fechaPagoConvenio = document.getElementById('fechaPagoConvenio') || document.querySelector('[name="fecha_pago"]');
    const hintDiaMes = document.getElementById('hintDiaMes');
    function actualizarHint(){
      const v = fechaPagoConvenio?.value || '';
      if (!hintDiaMes) return;
      if (!v) { hintDiaMes.textContent = 'Día de pago: —'; return; }
      const d = new Date(v + 'T00:00:00');
      if (isNaN(d)) { hintDiaMes.textContent = 'Día de pago: —'; return; }
      const dia = d.getDate();
      hintDiaMes.textContent = `Día de pago: ${dia} de cada mes (si el mes no tiene ese día, se ajusta al último)`;
    }
    fechaPagoConvenio?.addEventListener('change', actualizarHint);
    actualizarHint();
</script>
@endpush
