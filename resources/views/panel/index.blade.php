@extends('layouts.app')
@section('title','Panel')
@section('crumb','Resumen')

@section('content')

{{-- ===== Notificaciones ===== --}}
@if($ppPendCount || $cnaPendCount || $vencCount)
  <div class="alert alert-warning d-flex align-items-center justify-content-between">
    <div>
      <strong>Notificaciones:</strong>
      @if($ppPendCount)
        <span class="ms-2 badge text-bg-warning">{{ $ppPendCount }}</span> promesa(s) pendiente(s) @if($isSupervisor) de pre-aprobar @else de aprobar @endif
      @endif
      @if($cnaPendCount)
        <span class="ms-3 badge text-bg-info">{{ $cnaPendCount }}</span> CNA(s) pendientes
      @endif
      @if($vencCount)
        <span class="ms-3 badge text-bg-danger">{{ $vencCount }}</span> cuota(s) con vencimiento en 7 días
      @endif
    </div>
    <div class="ms-3">
      <a href="{{ route('autorizacion') }}" class="btn btn-sm btn-outline-dark">Ir a Autorización</a>
    </div>
  </div>
@endif

{{-- ===== Buscar cliente ===== --}}
<div class="card mb-3">
  <div class="card-body d-flex align-items-center justify-content-between">
    <form class="d-flex w-100" method="GET" action="{{ route('clientes.index') }}">
      <input name="q" class="form-control me-2" placeholder="Buscar por DNI / Operación / Titular / Cartera">
      <button class="btn btn-primary">Buscar</button>
    </form>
    <a class="btn btn-outline-secondary ms-2" href="{{ route('clientes.index') }}">Ver listado</a>
  </div>
</div>

{{-- ===== KPIs ===== --}}
<div class="row g-3">
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-secondary small">Promesas pendientes</div>
      <div class="h4 mb-0">{{ $ppPendCount }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-secondary small">CNA pendientes</div>
      <div class="h4 mb-0">{{ $cnaPendCount }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-secondary small">Promesas creadas hoy</div>
      <div class="h4 mb-0">{{ $kpiPromHoy }}</div>
    </div></div>
  </div>
  <div class="col-md-3">
    <div class="card h-100"><div class="card-body">
      <div class="text-secondary small">Pagos hoy (S/)</div>
      <div class="h4 mb-0">{{ number_format($kpiPagosHoy,2,'.',',') }}</div>
    </div></div>
  </div>
</div>

{{-- ===== Listas rápidas ===== --}}
<div class="row g-3 mt-1">

  {{-- Pendientes de autorización --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header fw-bold">
        @if($isSupervisor) Promesas por Pre-aprobar @else Promesas por Aprobar @endif
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="text-center">DNI</th>
                <th class="text-center">Fecha</th>
                <th>Operación(es)</th>
                <th class="text-end">Monto (S/)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($ppPend as $p)
                <tr>
                  <td class="text-center text-nowrap">{{ $p->dni }}</td>
                  <td class="text-center text-nowrap">{{ optional($p->fecha_promesa)->format('Y-m-d') }}</td>
                  <td class="text-nowrap">{{ $p->operacion ?: '—' }}</td>
                  <td class="text-end text-nowrap">{{ number_format($p->monto_mostrar,2,'.',',') }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">Sin pendientes.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-end">
        <a href="{{ route('autorizacion') }}" class="btn btn-sm btn-outline-primary">Ver todo</a>
      </div>
    </div>
  </div>

  {{-- Próximos vencimientos (7 días) --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header fw-bold">Próximos vencimientos (7 días)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="text-center">DNI</th>
                <th>Operación(es)</th>
                <th class="text-center">Fecha</th>
                <th class="text-end">Monto (S/)</th>
              </tr>
            </thead>
            <tbody>
              @forelse($venc as $v)
                <tr>
                  <td class="text-center text-nowrap">{{ $v->dni }}</td>
                  <td class="text-nowrap">{{ $v->operacion }}</td>
                  <td class="text-center text-nowrap">{{ \Carbon\Carbon::parse($v->fecha)->format('Y-m-d') }}</td>
                  <td class="text-end text-nowrap">{{ number_format((float)$v->monto,2,'.',',') }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">Sin vencimientos cercanos.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- CNA pendientes --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header fw-bold">CNA pendientes</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="text-center">DNI</th>
                <th class="text-center">No. Carta</th>
                <th>Observación</th>
                <th class="text-center">Fecha</th>
              </tr>
            </thead>
            <tbody>
              @forelse($cnaPend as $c)
                <tr>
                  <td class="text-center text-nowrap">{{ $c->dni }}</td>
                  <td class="text-center text-nowrap">{{ $c->nro_carta }}</td>
                  <td class="text-truncate" style="max-width:340px" title="{{ $c->observacion }}">{{ $c->observacion ?: '—' }}</td>
                  <td class="text-center text-nowrap">{{ optional($c->created_at)->format('Y-m-d') }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">Sin CNA pendientes.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-end">
        <a href="{{ route('autorizacion') }}#cna" class="btn btn-sm btn-outline-primary">Ir a CNA</a>
      </div>
    </div>
  </div>

  {{-- Pagos recientes --}}
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header fw-bold">Pagos recientes</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Operación/Pagaré</th>
                <th class="text-center">Fecha</th>
                <th class="text-end">Monto (S/)</th>
                <th>Gestor</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              @forelse($pagos as $p)
                <tr>
                  <td class="text-nowrap">{{ $p->oper }}</td>
                  <td class="text-center text-nowrap">{{ $p->fecha }}</td>
                  <td class="text-end text-nowrap">{{ number_format((float)$p->monto,2,'.',',') }}</td>
                  <td class="text-nowrap">{{ $p->gestor }}</td>
                  <td class="text-nowrap">{{ $p->estado }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-3">Sin registros.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
