@extends('layouts.app')

@section('title','Integración ▸ Subir Pagos')
@section('crumb','Integración ▸ Subir Pagos')

@push('head')
<style>
  .tabs { display:flex; gap:8px; margin-bottom:12px }
  .tabs .tab-btn{
    padding:.45rem .75rem; border:1px solid var(--border); background:var(--surface);
    border-radius:999px; cursor:pointer; font-weight:600;
  }
  .tabs .tab-btn.active{
    border-color: color-mix(in oklab, var(--brand) 40%, var(--border));
    background: color-mix(in oklab, var(--brand) 10%, transparent);
    color: var(--brand-ink);
  }
  .tab-panel { display:none }
  .tab-panel.show { display:block }
</style>
@endpush

@section('content')
  {{-- ALERTAS GLOBALES --}}
  @if(session('ok'))
    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{!! nl2br(e(session('ok'))) !!}</div>
  @endif
  @if(session('warn'))
    <div class="alert alert-warning"><pre class="mb-0" style="white-space:pre-wrap">{{ session('warn') }}</pre></div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  {{-- TABS (sin recargar la página) --}}
  <div class="tabs">
    <button class="tab-btn active" data-tab="propia"><i class="bi bi-file-spreadsheet me-1"></i> PROPIA</button>
    <button class="tab-btn" data-tab="cusco"><i class="bi bi-bank me-1"></i> Caja Cusco ▸ Castigada</button>
    <button class="tab-btn" data-tab="extrajudicial"><i class="bi bi-briefcase me-1"></i> Caja Cusco ▸ Extrajudicial</button>
  </div>

  {{-- PANEL: PROPIA --}}
  <div id="panel-propia" class="tab-panel show">
    @include('placeholders.pagos.propia', [
      'ultimoLotePropia' => $ultimoLotePropia ?? null,
      'pagosPropia' => $pagosPropia ?? collect(),
    ])
  </div>

  {{-- PANEL: CAJA CUSCO ▸ CASTIGADA --}}
  <div id="panel-cusco" class="tab-panel">
    @include('placeholders.pagos.castigada', [
      'ultimoLoteCusco' => $ultimoLoteCusco ?? null,
      'pagosCusco' => $pagosCusco ?? collect(),
    ])
  </div>

  {{-- PANEL: CAJA CUSCO ▸ EXTRAJUDICIAL --}}
  <div id="panel-extrajudicial" class="tab-panel">
    @include('placeholders.pagos.extrajudicial')
  </div>
@endsection

@push('scripts')
<script>
  // Tabs sin recargar
  const tabs = document.querySelectorAll('.tab-btn');
  const panels = {
    propia: document.getElementById('panel-propia'),
    cusco: document.getElementById('panel-cusco'),
    extrajudicial: document.getElementById('panel-extrajudicial'),
  };
  tabs.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      tabs.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.tab;
      Object.values(panels).forEach(p=>p.classList.remove('show'));
      panels[key]?.classList.add('show');
    });
  });
</script>
@endpush

