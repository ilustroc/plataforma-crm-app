{{-- resources/views/reportes/pagos/index.blade.php --}}
@extends('layouts.app')
@section('title','Reportes ▸ Pagos')
@section('crumb','Reportes ▸ Pagos')

@push('head')
<style>
  /* ====== CONTROLES (pestañas y barra de acciones) ====== */
  .seg { display:flex; flex-wrap:wrap; gap:.5rem; }
  .seg .btn{
    border-radius:999px;
    border:1px solid var(--border);
    background:var(--surface);
    font-weight:600;
  }
  .seg .btn.active{
    background:color-mix(in oklab, var(--brand) 16%, transparent);
    border-color:color-mix(in oklab, var(--brand) 34%, transparent);
    color:var(--ink);
  }
  .filters .form-control,.filters .form-select{ background:var(--surface) }

  .toolbar{
    display:flex; flex-wrap:wrap; gap:.5rem;
    align-items:center;
  }
  .toolbar .spacer{ flex:1 1 auto }

  /* resumen pequeñito (opcional) */
  .tiny{ font-size:.9rem; color:var(--muted) }

  /* skeleton para carga ajax */
  .skeleton{
    border:1px dashed var(--border);
    border-radius: var(--radius);
    padding: 1rem; color: var(--muted);
    text-align:center;
  }

  /* chips rápidos de rango */
  .quick-range .btn{
    --_bg: color-mix(in oklab, var(--brand) 10%, transparent);
    border:1px solid var(--border); background:var(--surface); border-radius:999px; padding:.25rem .6rem;
  }
  .quick-range .btn:hover{ background:var(--surface-2) }
</style>
@endpush

@section('content')
<div class="card pad">

  {{-- ===== Tabs de cartera (sin recarga) ===== --}}
  <div class="seg mb-3">
    <button class="btn tab" data-cartera="propia">
      <i class="bi bi-circle me-1"></i> Propia
    </button>
    <button class="btn tab" data-cartera="caja-cusco-castigada">
      <i class="bi bi-box-seam me-1"></i> Caja Cusco ▸ Castigada
    </button>
    <button class="btn tab" data-cartera="extrajudicial">
      <i class="bi bi-shield-check me-1"></i> Caja Cusco ▸ Extrajudicial
    </button>
    <div class="spacer"></div>
    <div id="summary" class="tiny d-none"></div>
  </div>

  {{-- ===== Filtros ===== --}}
  <form id="filtros" class="row g-2 align-items-end filters">
    <input type="hidden" name="cartera" id="cartera" value="{{ $cartera ?? 'propia' }}">

    <div class="col-6 col-md-2">
      <label class="form-label">Desde</label>
      <input type="date" name="from" class="form-control" value="{{ $from }}">
    </div>
    <div class="col-6 col-md-2">
      <label class="form-label">Hasta</label>
      <input type="date" name="to" class="form-control" value="{{ $to }}">
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label d-flex align-items-center justify-content-between">
        <span>Gestor</span>
        <span class="quick-range d-none d-md-inline-flex gap-1">
          <button type="button" class="btn btn-sm" data-range="hoy">Hoy</button>
          <button type="button" class="btn btn-sm" data-range="7">7d</button>
          <button type="button" class="btn btn-sm" data-range="30">30d</button>
        </span>
      </label>
      <input type="text" name="gestor" class="form-control" placeholder="Nombre/alias" value="{{ $gestor }}">
    </div>

    <div class="col-6 col-md-2">
      <label class="form-label">Status</label>
      <input type="text" name="status" class="form-control" placeholder="ej. APLICADO" value="{{ $status }}">
    </div>

    <div class="col-12 col-md-3">
      <label class="form-label">Buscar</label>
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="DNI / Pagaré / Titular / Cliente / Operación" value="{{ $q }}">
        <button class="btn btn-outline-secondary" id="btnBuscar"><i class="bi bi-search"></i></button>
      </div>
    </div>

    {{-- Barra de acciones --}}
    <div class="col-12">
      <div class="toolbar mt-1">
        <button class="btn btn-outline-secondary" id="btnLimpiar" type="button">
          Limpiar
        </button>

        <div class="spacer"></div>
        <a class="btn btn-danger" id="btnExport" href="#">
          <i class="bi bi-download me-1"></i> Exportar<span class="tiny"></span>
        </a>
      </div>
    </div>
  </form>

  <hr class="my-3">

  {{-- ===== Tabla (carga parcial) ===== --}}
  <div id="tablaPagos">
    {{-- Primer render del servidor para time-to-first-byte --}}
    @includeWhen(($cartera ?? 'propia')==='caja-cusco-castigada', 'reportes.pagos.castigada', get_defined_vars())
    @includeWhen(($cartera ?? 'propia')==='propia', 'reportes.pagos.propia', get_defined_vars())
    @includeWhen(($cartera ?? 'propia')==='extrajudicial', 'reportes.pagos.extrajudicial', get_defined_vars())
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const $tabs = document.querySelectorAll('.tab');
  const $cartera = document.getElementById('cartera');
  const $tabla = document.getElementById('tablaPagos');
  const $form = document.getElementById('filtros');
  const $btnExport = document.getElementById('btnExport');
  const $btnLimpiar = document.getElementById('btnLimpiar');
  const $btnBuscar = document.getElementById('btnBuscar');
  const $summary = document.getElementById('summary');

  // Marca pestaña activa
  function markActive(){
    $tabs.forEach(b=> b.classList.toggle('active', b.dataset.cartera === $cartera.value));
  }
  markActive();
  updateExportLinks();
  updateSummary();

  // Helpers
  function buildQuery(extra={}) {
    const fd = new FormData($form);
    const params = new URLSearchParams();
    for (const [k,v] of fd.entries()) if(v) params.set(k,v);
    for (const k in extra) if(extra[k]!==undefined && extra[k]!==null) params.set(k, extra[k]);
    return params.toString();
  }
  function setQuickRange(days){
    const d = new Date();
    const end = d.toISOString().slice(0,10);
    if(days === 'hoy'){
      document.querySelector('[name="from"]').value = end;
      document.querySelector('[name="to"]').value = end;
      return;
    }
    d.setDate(d.getDate()-(+days));
    const start = d.toISOString().slice(0,10);
    document.querySelector('[name="from"]').value = start;
    document.querySelector('[name="to"]').value = end;
  }

  // Carga parcial (tabla + paginación)
  async function loadData(url=null){
    try{
      if(!url){
        url = '/reportes/pagos?' + buildQuery({partial:1});
      }else{
        const u = new URL(url, location.origin);
        u.searchParams.set('partial','1');
        url = u.pathname + '?' + u.searchParams.toString();
      }
      $tabla.innerHTML = '<div class="skeleton">Cargando…</div>';
      const html = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.text());
      $tabla.innerHTML = html;
      hookPagination();
      updateExportLinks();
      updateSummary();
      // Refrescar URL (para compartir filtros) sin recargar
      const share = '/reportes/pagos?' + buildQuery();
      history.replaceState(null,'', share);
    }catch(e){
      $tabla.innerHTML = '<div class="text-danger p-3">Ocurrió un error al cargar los datos.</div>';
    }
  }

  function hookPagination(){
    $tabla.querySelectorAll('.pagination a').forEach(a=>{
      a.addEventListener('click', ev=>{
        ev.preventDefault();
        loadData(a.getAttribute('href'));
      });
    });
  }

  function updateExportLinks(){
    const meta = $tabla.querySelector('#pagMeta');
    const currentPage = meta?.dataset?.page || 1;
    $btnExport.href  = '/reportes/pagos/export?' + buildQuery({scope:'all', page:1});
  }

  function updateSummary(){
    const meta = $tabla.querySelector('#pagMeta');
    if(!meta){ $summary.classList.add('d-none'); return; }
    const page = meta.dataset.page || 1;
    const total = meta.dataset.total || '';
    $summary.textContent = total ? `Página ${page} · ${total} resultados` : '';
    $summary.classList.toggle('d-none', !total);
  }

  // Eventos: tabs
  $tabs.forEach(b=>{
    b.addEventListener('click', ()=>{
      $cartera.value = b.dataset.cartera;
      markActive();
      loadData();
    });
  });

  // Buscar (submit inline)
  $btnBuscar.addEventListener('click', ev=>{ ev.preventDefault(); loadData(); });
  $form.querySelectorAll('input').forEach(el=>{
    el.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); loadData(); }});
    el.addEventListener('change', ()=> updateExportLinks());
  });

  // Rango rápido (Hoy / 7d / 30d)
  document.querySelectorAll('.quick-range .btn').forEach(b=>{
    b.addEventListener('click', ()=>{
      setQuickRange(b.dataset.range);
      loadData();
    });
  });

  // Limpiar filtros
  $btnLimpiar.addEventListener('click', ()=>{
    const keep = $cartera.value;
    $form.reset();
    $cartera.value = keep || 'propia';
    markActive();
    loadData();
  });
})();
</script>
@endpush

