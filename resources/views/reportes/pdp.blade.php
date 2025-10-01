@extends('layouts.app')
@section('title','Reportes ▸ Promesas')
@section('crumb','Reportes ▸ Promesas')

@push('head')
<style>
  .skeleton{
    border:1px dashed var(--border);
    border-radius:var(--radius);
    padding:1rem;
    color:var(--muted);
    text-align:center
  }
  .tiny{ font-size:.9rem; color:var(--muted) }

  /* Tabla */
  .rpt-pdp .table thead th{
    position:sticky; top:0; z-index:1;
    background:color-mix(in oklab, var(--surface-2) 55%, transparent)
  }
  [data-theme="dark"] .rpt-pdp .table thead th{
    background:color-mix(in oklab, var(--surface-2) 40%, transparent)
  }
  .rpt-pdp .table tbody tr:nth-child(even){
    background:color-mix(in oklab, var(--surface-2) 22%, transparent)
  }
</style>
@endpush

@section('content')
<div class="card pad">
  {{-- ===== Filtros ===== --}}
  <form id="filtros" class="row g-2 align-items-end">
    <div class="col-6 col-md-2">
      <label class="form-label">Desde</label>
      <input type="date" name="from" class="form-control" value="{{ $from }}">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label">Hasta</label>
      <input type="date" name="to" class="form-control" value="{{ $to }}">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label">Estado</label>
      <input type="text" name="estado" class="form-control" placeholder="aprobada / pendiente / ..." value="{{ $estado }}">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label">Gestor</label>
      <input type="text" name="gestor" class="form-control" value="{{ $gestor }}">
    </div>
    <div class="col-12 col-md-3">
      <label class="form-label">Buscar</label>
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="DNI / nota / observación" value="{{ $q }}">
        <button class="btn btn-outline-secondary" id="btnBuscar"><i class="bi bi-search"></i></button>
      </div>
    </div>

    <div class="col-12">
      <div class="d-flex gap-2 align-items-center mt-1">
        <button class="btn btn-outline-secondary" id="btnLimpiar" type="button">Limpiar</button>
        <div class="ms-auto tiny" id="summary"></div>
        <a class="btn btn-danger" id="btnExport" href="#"><i class="bi bi-download me-1"></i> Exportar</a>
      </div>
    </div>
  </form>

  <hr class="my-3">

  {{-- ===== Tabla (contenido reemplazable por AJAX) ===== --}}
  <div id="tablaPdp">
    {{-- Meta para summary/export --}}
    <div id="pagMeta" data-page="{{ $rows->currentPage() }}" data-total="{{ $rows->total() }}"></div>

    <div class="rpt-pdp">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>DNI</th>
              <th>Operaciones</th>
              <th>Fecha promesa</th>
              <th class="text-end">Monto prometido</th>
              <th>Estado</th>
              <th>Gestor</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $p)
            @php
              $ops = method_exists($p,'operaciones')
                  ? $p->operaciones->pluck('operacion')->implode(', ')
                  : (is_array($p->operaciones ?? null) ? implode(', ', $p->operaciones) : '');

              $monto = $p->monto_prometido ?? $p->monto_total ?? $p->importe ?? $p->monto ?? null;

              $rawFecha = $p->{$fechaCol} ?? null;
              $fecha = $rawFecha
                       ? (optional($rawFecha)->format('Y-m-d') ?: (is_string($rawFecha) ? $rawFecha : null))
                       : null;
            @endphp
            <tr>
              <td class="text-nowrap">{{ $p->dni }}</td>
              <td class="text-nowrap">{{ $ops }}</td>
              <td class="text-nowrap">{{ $fecha }}</td>
              <td class="text-end">{{ $monto!==null ? number_format((float)$monto,2) : '—' }}</td>
              <td class="text-nowrap">{{ $p->workflow_estado ?? $p->estado ?? '—' }}</td>
              <td class="text-nowrap">{{ $p->gestor ?? ($p->user->name ?? '—') }}</td>
              <td class="text-nowrap">
                @if(Route::has('promesas.acuerdo'))
                  <a href="{{ route('promesas.acuerdo', $p->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-filetype-pdf"></i> PDF
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-secondary">Sin resultados.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-2">
        <div class="small text-muted">
          Mostrando {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} de {{ $rows->total() }}.
        </div>
        {{ $rows->onEachSide(1)->withQueryString()->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const $form = document.getElementById('filtros');
  const $tabla = document.getElementById('tablaPdp');
  const $btnBuscar = document.getElementById('btnBuscar');
  const $btnLimpiar = document.getElementById('btnLimpiar');
  const $btnExport = document.getElementById('btnExport');
  const $summary = document.getElementById('summary');

  // Usa rutas por nombre (si existen) para mayor robustez
  const baseUrl   = "{{ route('reportes.pdp') }}";
  const exportUrl = "{{ route('reportes.pdp.export') }}";

  function buildQuery(extra={}) {
    const fd = new FormData($form), p = new URLSearchParams();
    for (const [k,v] of fd.entries()) if(v) p.set(k,v);
    for (const k in extra) if(Object.prototype.hasOwnProperty.call(extra,k) && extra[k]!==undefined && extra[k]!==null) p.set(k,extra[k]);
    return p.toString();
  }

  async function loadData(url=null){
    try{
      if(!url){ url = baseUrl + '?' + buildQuery({partial:1}); }
      else {
        const u = new URL(url, location.origin);
        u.searchParams.set('partial','1');
        url = u.pathname + '?' + u.searchParams.toString();
      }

      $tabla.innerHTML = '<div class="skeleton">Cargando…</div>';

      const text = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.text());
      const doc  = new DOMParser().parseFromString(text, 'text/html');
      const frag = doc.querySelector('#tablaPdp');

      // Reemplaza solo el contenido del contenedor de tabla
      $tabla.innerHTML = frag ? frag.innerHTML : text;

      hookPagination(); updateExport(); updateSummary();

      // Actualiza URL navegable con los filtros actuales
      history.replaceState(null,'', baseUrl + '?' + buildQuery());
    }catch(e){
      console.error(e);
      $tabla.innerHTML = '<div class="text-danger p-3">Error al cargar.</div>';
    }
  }

  function hookPagination(){
    $tabla.querySelectorAll('.pagination a').forEach(a=>{
      a.addEventListener('click', e=>{
        e.preventDefault();
        loadData(a.href);
      });
    });
  }

  function updateExport(){
    $btnExport.href = exportUrl + '?' + buildQuery();
  }

  function updateSummary(){
    const m = $tabla.querySelector('#pagMeta');
    $summary.textContent = m ? `Página ${m.dataset.page} · ${m.dataset.total} resultados` : '';
  }

  // Eventos
  $btnBuscar.addEventListener('click', e=>{ e.preventDefault(); loadData(); });
  $btnLimpiar.addEventListener('click', ()=>{ $form.reset(); loadData(); });
  $form.querySelectorAll('input').forEach(el=>{
    el.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); loadData(); }});
    el.addEventListener('change', ()=> updateExport());
  });

  // Init
  updateExport(); updateSummary();
})();
</script>
@endpush