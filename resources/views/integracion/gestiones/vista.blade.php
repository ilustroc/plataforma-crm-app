@extends('layouts.app')

@section('title','Integración ▸ Subir Gestiones')
@section('crumb','Integración ▸ Subir Gestiones')

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

  {{-- TABS (dejamos solo PROPIA por ahora, para futuro crecimiento) --}}
  <div class="tabs">
    <button class="tab-btn active" data-tab="propia"><i class="bi bi-clipboard2-data me-1"></i> PROPIA</button>
  </div>

  {{-- PANEL: PROPIA --}}
  <div id="panel-propia" class="tab-panel show">
    @include('placeholders.gestiones.propia', [
      'ultimoLotePropia' => $ultimoLotePropia ?? null,
      'gestionesPropia'  => $gestionesPropia ?? collect(),
    ])
  </div>
@endsection

@push('scripts')
<script>
  // Soporte por si luego agregas más tabs
  const tabs = document.querySelectorAll('.tab-btn');
  const panels = { propia: document.getElementById('panel-propia') };
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
  