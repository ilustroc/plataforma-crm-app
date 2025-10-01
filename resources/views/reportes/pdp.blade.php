@extends('layouts.app')
@section('title','Reportes ▸ Promesas')
@section('crumb','Reportes ▸ Promesas')

@push('head')
<style>
  .skeleton{border:1px dashed var(--border);border-radius:var(--radius);padding:1rem;color:var(--muted);text-align:center}
  .tiny{ font-size:.9rem; color:var(--muted) }
</style>
@endpush

@section('content')
<div class="card pad">
  {{-- Filtros --}}
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

  {{-- Tabla (parcial) --}}
  <div id="tablaPdp">
    @include('reportes.promesas.propia', get_defined_vars())
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

  function buildQuery(extra={}) {
    const fd = new FormData($form), p = new URLSearchParams();
    for (const [k,v] of fd.entries()) if(v) p.set(k,v);
    for (const k in extra) if(extra[k]!==undefined && extra[k]!==null) p.set(k,extra[k]);
    return p.toString();
  }

  async function loadData(url=null){
    try{
      if(!url){ url = '/reportes/pdp?' + buildQuery({partial:1}); }
      else { const u = new URL(url, location.origin); u.searchParams.set('partial','1'); url = u.pathname + '?' + u.searchParams.toString(); }
      $tabla.innerHTML = '<div class="skeleton">Cargando…</div>';
      const html = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.text());
      $tabla.innerHTML = html;
      hookPagination(); updateExport(); updateSummary();
      history.replaceState(null,'', '/reportes/pdp?'+buildQuery());
    }catch{ $tabla.innerHTML = '<div class="text-danger p-3">Error al cargar.</div>'; }
  }

  function hookPagination(){
    $tabla.querySelectorAll('.pagination a').forEach(a=>{
      a.addEventListener('click', e=>{ e.preventDefault(); loadData(a.href); });
    });
  }

  function updateExport(){ $btnExport.href = '/reportes/pdp/export?' + buildQuery(); }

  function updateSummary(){
    const m = $tabla.querySelector('#pagMeta');
    $summary.textContent = m ? `Página ${m.dataset.page} · ${m.dataset.total} resultados` : '';
  }

  $btnBuscar.addEventListener('click', e=>{ e.preventDefault(); loadData(); });
  $btnLimpiar.addEventListener('click', ()=>{ $form.reset(); loadData(); });
  $form.querySelectorAll('input').forEach(el=>{
    el.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); loadData(); }});
    el.addEventListener('change', ()=> updateExport());
  });

  updateExport(); updateSummary();
})();
</script>
@endpush
