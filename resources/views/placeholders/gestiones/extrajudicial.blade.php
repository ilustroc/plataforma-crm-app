{{-- resources/views/placeholders/pagos/extrajudicial.blade.php --}}
@push('head')
<style>
  .upload-card a{ color:var(--brand) } .upload-card a:hover{ color:var(--brand-ink) }
  .upload-card .btn-primary{ background:var(--brand); border-color:var(--brand) }
  .upload-card .btn-primary:hover{ background:var(--brand-ink); border-color:var(--brand-ink) }

  .pill{display:inline-flex;align-items:center;gap:.35rem;border:1px solid var(--border);background:var(--surface);
        border-radius:999px;padding:.18rem .6rem;font-size:.8rem}
  .mini{font-size:.9rem;color:var(--muted)}
  .ok{color:#0a7a3d} .err{color:#b42318} .warn{color:#8a6a00}

  .spec .table thead th{color:var(--ink);background:color-mix(in oklab,var(--surface-2) 55%,transparent)}
  .spec .table tbody td{color:var(--ink)}
  [data-theme="dark"] .spec .table thead th{background:color-mix(in oklab,var(--surface-2) 40%,transparent)}
  .spec code{background:color-mix(in oklab,var(--brand) 10%,transparent);color:var(--brand);padding:.05rem .35rem;border-radius:6px}
  [data-theme="dark"] .spec code{background:color-mix(in oklab,var(--brand) 22%,transparent)}
</style>
@endpush

{{-- === CAJA CUSCO ▸ EXTRAJUDICIAL === --}}
<div class="card pad mb-3 upload-card">
  <h5 class="mb-2 d-flex align-items-center gap-2">
    <i class="bi bi-upload"></i> <span>Subida de archivo (CAJA CUSCO ▸ EXTRAJUDICIAL)</span>
  </h5>
  <p class="text-secondary mb-2">
    Formato aceptado: <strong>CSV UTF-8</strong>, delimitado por <strong>coma</strong> o <strong>punto y coma</strong>.
    <a href="{{ route('integracion.pagos.template.cusco_extrajudicial') }}">Descargar plantilla</a>.
  </p>

  <form method="POST" action="{{ route('integracion.pagos.import.cusco_extrajudicial') }}" enctype="multipart/form-data" class="row g-2 align-items-end" id="formImportEXJ">
    @csrf
    <div class="col-lg-7">
      <label class="form-label">Archivo CSV</label>
      <input type="file" name="archivo" id="csvFileEXJ" class="form-control" accept=".csv,text/csv" required>
      <div class="form-text">
        Encabezados esperados (exactos, según tu layout):
        <span class="pill">REGION</span>
        <span class="pill">Agencia</span>
        <span class="pill">Titular</span>
        <span class="pill">DNI</span>
        <span class="pill">Pagare</span>
        <span class="pill">Moneda</span>
        <span class="pill">Tipo de Recuperacion</span>
        <span class="pill">Condición</span>
        <span class="pill">Demanda</span>
        <span class="pill">Fecha</span>
        <span class="pill">Pagado en Soles</span>
        <span class="pill">Monto Pagado</span>
        <span class="pill">VERIFICACION DE BPO</span>
        <span class="pill">Estado final</span>
        <span class="pill">CONCATENAR</span>
        <span class="pill">Fecha</span>
        <span class="pill">Pagado en Soles</span>
        <span class="pill">Gestor</span>
      </div>
    </div>
    <div class="col-lg-3">
      <button class="btn btn-primary w-100" id="btnImportEXJ" disabled>
        <i class="bi bi-cloud-upload me-1"></i> Importar
      </button>
    </div>

    {{-- Pre-check --}}
    <div class="col-12 d-none" id="precheckBoxEXJ">
      <hr class="my-2">
      <div class="d-flex flex-wrap align-items-center gap-3">
        <div><i class="bi bi-check-circle-fill ok me-1"></i><span class="mini" id="hdrMsgEXJ">Validando encabezados…</span></div>
        <div><i class="bi bi-123 warn me-1"></i><span class="mini" id="typeMsgEXJ">Tipos por muestra: —</span></div>
      </div>
      <div class="table-responsive mt-2 d-none" id="issuesWrapEXJ">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Fila</th><th>Columna</th><th>Valor</th><th>Detalle</th></tr></thead>
          <tbody id="issuesBodyEXJ"></tbody>
        </table>
      </div>
    </div>
  </form>
</div>

{{-- Guía rápida EXTRAJUDICIAL --}}
<div class="card pad mb-3">
  <details>
    <summary class="fw-semibold d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i> Guía rápida de columnas (tipos y ejemplos)</summary>
    <div class="table-responsive mt-2 spec">
      <table class="table align-middle">
        <thead><tr><th>Columna</th><th>Tipo</th><th>Obligatoria</th><th>Ejemplo</th><th>Notas</th></tr></thead>
        <tbody>
          <tr><td><code>Región</code></td><td>Texto</td><td>No</td><td>R6: AREQUIPA NORTE</td><td>Libre.</td></tr>
          <tr><td><code>Agencia</code></td><td>Texto</td><td>No</td><td>Agencia Cayma</td><td>Libre.</td></tr>
          <tr><td><code>Titular</code></td><td>Texto</td><td>No</td><td>BARRIONUEVO/ROMERO,CRISTOFER EDUARD</td><td>Libre.</td></tr>
          <tr><td><code>DNI</code></td><td>Texto</td><td>No</td><td>49073887</td><td>Guardar como texto.</td></tr>
          <tr><td><code>Pagare</code></td><td>Texto</td><td>No</td><td>106713041000171174</td><td>Alfanumérico válido.</td></tr>
          <tr><td><code>Moneda</code></td><td>Texto</td><td>No</td><td>SOLES</td><td>PEN/USD/etc.</td></tr>
          <tr><td><code>Tipo de Recuperacion</code></td><td>Texto</td><td>No</td><td>JUDICIAL</td><td>Libre.</td></tr>
          <tr><td><code>Condición</code></td><td>Texto</td><td>No</td><td>JUDICIAL</td><td>Libre.</td></tr>
          <tr><td><code>Demanda</code></td><td>Texto</td><td>No</td><td>Sin Demanda</td><td>Libre.</td></tr>
          <tr><td><code>Fecha</code></td><td>Fecha</td><td>No</td><td>01/08/2025</td><td>Primera fecha → <code>fecha_de_pago</code>.</td></tr>
          <tr><td><code>Pagado en Soles</code></td><td>Número</td><td>No</td><td>186.73</td><td>Primero → <code>pagado_en_soles</code>.</td></tr>
          <tr><td><code>Monto Pagado</code></td><td>Número</td><td>No</td><td>186.73</td><td>→ <code>monto_pagado</code>.</td></tr>
          <tr><td><code>VERIFICACION DE BPO</code></td><td>Texto</td><td>No</td><td>IMPULSE GO SERVICIOS...</td><td>Libre.</td></tr>
          <tr><td><code>Estado final</code></td><td>Texto</td><td>No</td><td>Vigente Judicial</td><td>Libre.</td></tr>
          <tr><td><code>CONCATENAR</code></td><td>Texto</td><td>No</td><td>49073887...</td><td>Libre.</td></tr>
          <tr><td><code>Fecha</code></td><td>Fecha</td><td>No</td><td>01/08/2025</td><td>Segunda fecha → <code>fecha_alt</code>.</td></tr>
          <tr><td><code>Pagado en Soles</code></td><td>Número</td><td>No</td><td>186.73</td><td>Segundo → <code>pagado_en_soles_alt</code>.</td></tr>
          <tr><td><code>Gestor</code></td><td>Texto</td><td>No</td><td>A.RAMIREZ</td><td>Libre.</td></tr>
        </tbody>
      </table>
    </div>
  </details>
</div>

{{-- Último lote (resumen) --}}
<div class="card pad">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0 d-flex align-items-center gap-2"><i class="bi bi-clock-history"></i> <span>Último lote importado (Caja Cusco ▸ Extrajudicial)</span></h5>
    @if($ultimoLoteExtrajudicial ?? null)
      <span class="text-secondary small">
        Lote #{{ $ultimoLoteExtrajudicial->id }} · {{ $ultimoLoteExtrajudicial->created_at->format('Y-m-d H:i') }} · {{ $ultimoLoteExtrajudicial->total_registros }} registros
      </span>
    @endif
  </div>

  @if(!($ultimoLoteExtrajudicial ?? null))
    <div class="text-secondary">Aún no hay importaciones de Caja Cusco ▸ Extrajudicial.</div>
  @else
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>DNI</th><th>Pagaré</th><th>Titular</th><th>Moneda</th>
            <th>Tipo Recup.</th><th>Condición</th><th>Demanda</th>
            <th>F. Pago</th><th class="text-end">Pagado S/</th><th class="text-end">Monto Pag.</th>
            <th>Verif. BPO</th><th>Estado final</th><th>F. (alt)</th><th class="text-end">Pagado S/ (alt)</th>
            <th>Gestor</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($pagosExtrajudicial ?? collect()) as $p)
          <tr>
            <td class="text-nowrap">{{ $p->dni }}</td>
            <td class="text-nowrap">{{ $p->pagare }}</td>
            <td>{{ $p->titular }}</td>
            <td>{{ $p->moneda }}</td>
            <td>{{ $p->tipo_de_recuperacion }}</td>
            <td>{{ $p->condicion }}</td>
            <td>{{ $p->demanda }}</td>
            <td class="text-nowrap">{{ optional($p->fecha_de_pago)->format('Y-m-d') }}</td>
            <td class="text-end">{{ number_format((float)$p->pagado_en_soles, 2) }}</td>
            <td class="text-end">{{ number_format((float)$p->monto_pagado, 2) }}</td>
            <td>{{ $p->verificacion_de_bpo }}</td>
            <td>{{ $p->estado_final }}</td>
            <td class="text-nowrap">{{ optional($p->fecha_alt)->format('Y-m-d') }}</td>
            <td class="text-end">{{ number_format((float)$p->pagado_en_soles_alt, 2) }}</td>
            <td>{{ $p->gestor }}</td>
          </tr>
          @empty
            <tr><td colspan="15" class="text-secondary">Sin datos para mostrar.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>

@push('scripts')
<script>
(function(){
  // Encabezados EXACTOS (respetamos títulos de tu Excel)
  const HEADERS = [
    "REGION","AGENCIA","TITULAR","DNI","PAGARE","MONEDA",
    "TIPO_DE_RECUPERACION","CONDICION","DEMANDA",
    "FECHA","PAGADO_EN_SOLES","MONTO_PAGADO",
    "VERIFICACION_DE_BPO","ESTADO_FINAL","CONCATENAR",
    "FECHA","PAGADO","GESTOR"
  ];

  const $file = document.getElementById('csvFileEXJ');
  const $btn  = document.getElementById('btnImportEXJ');
  const $box  = document.getElementById('precheckBoxEXJ');
  const $hdr  = document.getElementById('hdrMsgEXJ');
  const $typ  = document.getElementById('typeMsgEXJ');
  const $wrap = document.getElementById('issuesWrapEXJ');
  const $body = document.getElementById('issuesBodyEXJ');

  function splitCSV(line,sep){ const out=[]; let cur=''; let q=false;
    for(let i=0;i<line.length;i++){ const c=line[i];
      if(c==='"'){ q=!q } else if(c===sep && !q){ out.push(cur); cur='' } else { cur+=c } }
    out.push(cur); return out.map(s=>s.replace(/^"|"$/g,'').trim());
  }
  function parseCSV(text){
    text=(text||'').replace(/\r/g,'');
    const lines=text.split(/\n+/).filter(Boolean);
    if(!lines.length) return {rows:[],sep:','};
    const sep=(lines[0].split(';').length > lines[0].split(',').length) ? ';' : ',';
    return {rows:lines.map(l=>splitCSV(l,sep)),sep};
  }
  const isNumber=v=>{ if(v===''||v==null) return true; const x=(v+'').replace(/\s/g,'').replace(/,/g,'.'); return /^-?\d+(\.\d+)?$/.test(x) }
  const parseDate=v=>{ if(!v) return null; const s=v.trim(); let m=s.match(/^(\d{4})-(\d{2})-(\d{2})$/); if(m) return s; m=s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/); if(m) return `${m[3]}-${m[2]}-${m[1]}`; return null }
  const isDate=v=> v==='' || parseDate(v)!=null;

  $file?.addEventListener('change', ev=>{
    const file=ev.target.files?.[0];
    if(!file){ $btn.disabled=true; return }
    const reader=new FileReader();
    reader.onload=e=>{
      const {rows}=parseCSV(e.target.result||''); if(!rows.length){ $btn.disabled=true; return }
      $box.classList.remove('d-none'); $body.innerHTML=''; $wrap.classList.add('d-none');

      const header=rows[0];
      const missing=HEADERS.filter(h=>!header.includes(h));
      const extra=header.filter(h=>!HEADERS.includes(h));
      const headerOk=missing.length===0;

      $hdr.innerHTML = headerOk
        ? `Encabezados: OK (<span class="ok">${header.length}</span>)`
        : `Encabezados: faltan <span class="err">${missing.join(', ')||'-'}</span>${extra.length?`, extra: <span class='warn'>${extra.join(', ')}</span>`:''}`;

      let issues=[], sampled=0;
      for(let r=1; r<Math.min(rows.length,51); r++){
        const row=rows[r]; if(!row||!row.length) continue; sampled++;
        HEADERS.forEach((h,idx)=>{
          const val=(row[idx]??'').trim(); let ok=true, detail='';
          if(h==='Fecha'){ ok=isDate(val); if(!ok) detail='Fecha inválida. Use YYYY-MM-DD o DD/MM/YYYY' }
          else if(h==='Pagado en Soles' || h==='Monto Pagado'){ ok=isNumber(val); if(!ok) detail='Número inválido' }
          if(!ok){ issues.push({r:r+1,col:h,val,detail}) }
        });
      }

      $typ.textContent = `Tipos por muestra: ${sampled} fila(s) verificadas, ${issues.length} posible(s) problema(s)`;
      if(issues.length){
        $wrap.classList.remove('d-none');
        $body.innerHTML = issues.slice(0,80).map(it=>`<tr><td>${it.r}</td><td>${it.col}</td><td>${(it.val||'').replace(/</g,'&lt;')}</td><td>${it.detail}</td></tr>`).join('');
      }
      $btn.disabled = !headerOk;
    };
    reader.readAsText(file,'UTF-8');
  });
})();
</script>
@endpush

