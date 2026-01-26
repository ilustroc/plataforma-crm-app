@push('head')
<style>
  .tabs{display:flex;gap:8px;margin-bottom:12px}
  .tabs .tab-btn{
    padding:.45rem .9rem;border:1px solid var(--border);background:var(--surface);
    border-radius:999px;cursor:pointer;font-weight:600;color:var(--ink)
  }
  .tabs .tab-btn:hover{
    border-color:color-mix(in oklab,var(--brand) 30%,transparent);
    background:color-mix(in oklab,var(--brand) 10%,transparent);
  }
  .tabs .tab-btn.active{
    border-color:color-mix(in oklab,var(--brand) 40%,transparent);
    background:color-mix(in oklab,var(--brand) 18%,transparent);
    color:var(--brand-ink)
  }
  .tab-panel{display:none} .tab-panel.show{display:block}
  .tab-btn:focus-visible{outline:3px solid color-mix(in oklab,var(--brand) 45%,transparent); outline-offset:2px}
</style>
@endpush

<div class="tabs" role="tablist" aria-label="Tipos de integración">
  <button class="tab-btn active" data-tab="propia" role="tab" aria-selected="true" aria-controls="panel-propia">
    <i class="bi bi-clipboard2-data me-1"></i> PROPIA
  </button>
</div>

@push('scripts')
<script>
  // Tabs accesibles + recordatorio de selección (sólo PROPIA por ahora)
  const TKEY='gest.tab';
  const tabs=[...document.querySelectorAll('.tab-btn')];
  const panels={ propia:document.getElementById('panel-propia') };

  function activate(key, persist){
    tabs.forEach(b=>{
      const on=b.dataset.tab===key;
      b.classList.toggle('active',on);
      b.setAttribute('aria-selected',on?'true':'false');
    });
    Object.entries(panels).forEach(([k,p])=>{ if(p) p.classList.toggle('show',k===key); });
    if(persist) localStorage.setItem(TKEY,key);
  }

  tabs.forEach(btn=>{
    btn.addEventListener('click',()=>activate(btn.dataset.tab,true));
    btn.addEventListener('keydown',e=>{
      if(e.key==='Enter' || e.key===' '){ e.preventDefault(); activate(btn.dataset.tab,true) }
    });
  });

  // Restaurar última pestaña
  activate(localStorage.getItem(TKEY)||'propia',false);
</script>
@endpush
