{{-- resources/views/placeholders/pagos/propia.blade.php --}}
{{-- === PROPIA === --}}
@push('head')
<style>
  /* Colores de marca y contraste */
  .upload-card a{ color:var(--brand) } .upload-card a:hover{ color:var(--brand-ink) }
  .upload-card .btn-primary{ background:var(--brand); border-color:var(--brand) }
  .upload-card .btn-primary:hover{ background:var(--brand-ink); border-color:var(--brand-ink) }

  .pill{display:inline-flex;align-items:center;gap:.35rem;border:1px solid var(--border);background:var(--surface);
        border-radius:999px;padding:.18rem .6rem;font-size:.8rem}
  .mini{font-size:.9rem;color:var(--muted)}
  .ok{color:#0a7a3d} .err{color:#b42318} .warn{color:#8a6a00}

  /* Guía con buen contraste en ambos temas */
  .spec .table thead th{color:var(--ink);background:color-mix(in oklab,var(--surface-2) 55%,transparent)}
  .spec .table tbody td{color:var(--ink)}
  [data-theme="dark"] .spec .table thead th{background:color-mix(in oklab,var(--surface-2) 40%,transparent)}
  .spec code{background:color-mix(in oklab,var(--brand) 10%,transparent);color:var(--brand);padding:.05rem .35rem;border-radius:6px}
  [data-theme="dark"] .spec code{background:color-mix(in oklab,var(--brand) 22%,transparent)}
</style>
@endpush

<div class="card pad mb-3 upload-card">
  <h5 class="mb-2 d-flex align-items-center gap-2"><i class="bi bi-upload"></i><span>Subida de archivo (PROPIA)</span></h5>
  <p class="text-secondary mb-2">
    Formato aceptado: <strong>CSV UTF-8</strong>, delimitado por <strong>coma</strong>.
    <a href="{{ route('integracion.pagos.template') }}">Descargar plantilla</a>.
  </p>

  <form method="POST" action="{{ route('integracion.pagos.import') }}" enctype="multipart/form-data" class="row g-2 align-items-end" id="formImportPropia">
    @csrf
    <div class="col-lg-7">
      <label class="form-label">Archivo CSV</label>
      <input type="file" name="archivo" id="csvFilePropia" class="form-control" accept=".csv,text/csv" required>
      <div class="form-text">
        Encabezados esperados:
        <span class="pill"><i class="bi bi-card-checklist"></i> DNI</span>
        <span class="pill">OPERACION</span>
        <span class="pill">ENTIDAD</span>
        <span class="pill">EQUIPOS</span>
        <span class="pill">NOMBRE_CLIENTE</span>
        <span class="pill">PRODUCTO</span>
        <span class="pill">MONEDA</span>
        <span class="pill">FECHA_DE_PAGO</span>
        <span class="pill">MONTO_PAGADO</span>
        <span class="pill">CONCATENAR</span>
        <span class="pill">FECHA</span>
        <span class="pill">PAGADO_EN_SOLES</span>
        <span class="pill">GESTOR</span>
        <span class="pill">STATUS</span>
      </div>
    </div>
    <div class="col-lg-3">
      <button class="btn btn-primary w-100" id="btnImportPropia" disabled>
        <i class="bi bi-cloud-upload me-1"></i> Importar
      </button>
    </div>

    {{-- Pre-check detallado --}}
    <div class="col-12 d-none" id="precheckBoxPropia">
      <hr class="my-2">
      <div class="d-flex flex-wrap align-items-center gap-3">
        <div><i class="bi bi-check-circle-fill ok me-1"></i><span class="mini" id="hdrMsgPropia">Validando encabezados…</span></div>
        <div><i class="bi bi-123 warn me-1"></i><span class="mini" id="typeMsgPropia">Tipos por muestra: —</span></div>
      </div>
      <div class="table-responsive mt-2 d-none" id="issuesWrapPropia">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Fila</th><th>Columna</th><th>Valor</th><th>Detalle</th></tr></thead>
          <tbody id="issuesBodyPropia"></tbody>
        </table>
      </div>
    </div>
  </form>
</div>

{{-- Guía rápida PROPIA --}}
<div class="card pad mb-3">
  <details>
    <summary class="fw-semibold d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i> Guía rápida de columnas (tipos y ejemplos)</summary>
    <div class="table-responsive mt-2 spec">
      <table class="table align-middle">
        <thead><tr><th>Columna</th><th>Tipo</th><th>Obligatoria</th><th>Ejemplo</th><th>Notas</th></tr></thead>
        <tbody>
          <tr><td><code>DNI</code></td><td>Texto</td><td>No</td><td>"00123456"</td><td>Guardar como <strong>texto</strong> para no perder ceros a la izquierda. Evitar formateo numérico.</td></tr>
          <tr><td><code>OPERACION</code></td><td>Texto</td><td>No</td><td>OP-77890</td><td>Alfanumérico permitido.</td></tr>
          <tr><td><code>ENTIDAD</code></td><td>Texto</td><td>No</td><td>BCP</td><td>Nombre corto.</td></tr>
          <tr><td><code>EQUIPOS</code></td><td>Texto</td><td>No</td><td>Plan A</td><td>Libre.</td></tr>
          <tr><td><code>NOMBRE_CLIENTE</code></td><td>Texto</td><td>No</td><td>Juan Pérez</td><td>Libre.</td></tr>
          <tr><td><code>PRODUCTO</code></td><td>Texto</td><td>No</td><td>Crédito</td><td>Libre.</td></tr>
          <tr><td><code>MONEDA</code></td><td>Texto</td><td>No</td><td>PEN</td><td>Usar <code>PEN</code> o <code>USD</code>; también se acepta <code>S/</code> o <code>$</code>.</td></tr>
          <tr><td><code>FECHA_DE_PAGO</code></td><td>Fecha</td><td>No</td><td>2025-01-31</td><td>Formatos válidos: <code>YYYY-MM-DD</code> o <code>DD/MM/YYYY</code>.</td></tr>
          <tr><td><code>MONTO_PAGADO</code></td><td>Número</td><td>No</td><td>1234.56</td><td>Separador decimal punto (<code>.</code>). Comas se normalizan.</td></tr>
          <tr><td><code>CONCATENAR</code></td><td>Texto</td><td>No</td><td>ABC-001</td><td>Libre.</td></tr>
          <tr><td><code>FECHA</code></td><td>Fecha</td><td>No</td><td>2025-01-31</td><td>Igual a <code>FECHA_DE_PAGO</code>.</td></tr>
          <tr><td><code>PAGADO_EN_SOLES</code></td><td>Número</td><td>No</td><td>1234.56</td><td>Convertido a PEN si aplica.</td></tr>
          <tr><td><code>GESTOR</code></td><td>Texto</td><td>No</td><td>Ana</td><td>Libre.</td></tr>
          <tr><td><code>STATUS</code></td><td>Texto</td><td>No</td><td>APLICADO</td><td>Libre.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="mt-2 small text-secondary">
      <i class="bi bi-lightbulb me-1"></i>En Excel/Sheets, formatea <strong>DNI</strong> como Texto y las fechas como <code>YYYY-MM-DD</code> para evitar cambios automáticos.
    </div>
  </details>
</div>

{{-- Último lote (PROPIA) --}}
<div class="card pad">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0 d-flex align-items-center gap-2"><i class="bi bi-clock-history"></i> <span>Último lote importado</span></h5>
    @if($ultimoLotePropia)
      <span class="text-secondary small">
        Lote #{{ $ultimoLotePropia->id }} · {{ $ultimoLotePropia->created_at->format('Y-m-d H:i') }} · {{ $ultimoLotePropia->total_registros }} registros
      </span>
    @endif
  </div>

  @if(!$ultimoLotePropia)
    <div class="text-secondary">Aún no hay importaciones.</div>
  @else
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>DNI</th><th>Operación</th><th>Entidad</th><th>Equipos</th><th>Cliente</th>
            <th>Producto</th><th>Moneda</th><th>F. Pago</th>
            <th class="text-end">Monto Pagado</th><th class="text-end">Pagado en S/</th><th>Gestor</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pagosPropia as $p)
            <tr>
              <td class="text-nowrap">{{ $p->dni }}</td>
              <td class="text-nowrap">{{ $p->operacion }}</td>
              <td>{{ $p->entidad }}</td>
              <td>{{ $p->equipos }}</td>
              <td>{{ $p->nombre_cliente }}</td>
              <td>{{ $p->producto }}</td>
              <td>{{ $p->moneda }}</td>
              <td class="text-nowrap">{{ optional($p->fecha_de_pago)->format('Y-m-d') }}</td>
              <td class="text-end">{{ number_format((float)$p->monto_pagado, 2) }}</td>
              <td class="text-end">{{ number_format((float)$p->pagado_en_soles, 2) }}</td>
              <td>{{ $p->gestor }}</td>
              <td>{{ $p->status }}</td>
            </tr>
          @empty
            <tr><td colspan="12" class="text-secondary">Sin datos para mostrar.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>

@push('scripts')
<script>
(function(){
  const HEADERS = ["DNI","OPERACION","ENTIDAD","EQUIPOS","NOMBRE_CLIENTE","PRODUCTO","MONEDA","FECHA_DE_PAGO","MONTO_PAGADO","CONCATENAR","FECHA","PAGADO_EN_SOLES","GESTOR","STATUS"];
  const $file = document.getElementById('csvFilePropia');
  const $btn  = document.getElementById('btnImportPropia');
  const $box  = document.getElementById('precheckBoxPropia');
  const $hdr  = document.getElementById('hdrMsgPropia');
  const $typ  = document.getElementById('typeMsgPropia');
  const $wrap = document.getElementById('issuesWrapPropia');
  const $body = document.getElementById('issuesBodyPropia');

  // CSV helpers
  function splitCSV(line, sep){ const out=[]; let cur=''; let q=false;
    for(let i=0;i<line.length;i++){ const c=line[i];
      if(c==='"'){ q=!q } else if(c===sep && !q){ out.push(cur); cur='' } else { cur+=c }
    } out.push(cur); return out.map(s=>s.replace(/^"|"$/g,'').trim());
  }
  function parseCSV(text){
    text=(text||'').replace(/\r/g,'');
    const lines=text.split(/\n+/).filter(Boolean);
    if(!lines.length) return {rows:[],sep:','};
    const sep = (lines[0].split(';').length > lines[0].split(',').length) ? ';' : ',';
    return {rows:lines.map(l=>splitCSV(l,sep)), sep};
  }
  const isNumber=v=>{ if(v===''||v==null) return true; const x=(v+'').replace(/\s/g,'').replace(/,/g,'.'); return /^-?\d+(\.\d+)?$/.test(x) }
  const parseDate=v=>{ if(!v) return null; const s=v.trim(); let m=s.match(/^(\d{4})-(\d{2})-(\d{2})$/); if(m) return s; m=s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/); if(m) return `${m[3]}-${m[2]}-${m[1]}`; return null }
  const isDate=v=> v==='' || parseDate(v)!=null;

  $file?.addEventListener('change', ev=>{
    const file = ev.target.files?.[0];
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
          if(h==='FECHA_DE_PAGO' || h==='FECHA'){ ok=isDate(val); if(!ok) detail='Fecha inválida. Use YYYY-MM-DD o DD/MM/YYYY' }
          else if(h==='MONTO_PAGADO' || h==='PAGADO_EN_SOLES'){ ok=isNumber(val); if(!ok) detail='Número inválido' }
          else if(h==='MONEDA'){ ok=!val || ['PEN','USD','S/','$'].includes((val+'').toUpperCase()); if(!ok) detail='Use PEN, USD, S/ o $' }
          if(!ok){ issues.push({r:r+1,col:h,val,detail}) }
        });
      }
      $typ.textContent = `Tipos por muestra: ${sampled} fila(s) verificadas, ${issues.length} posible(s) problema(s)`;
      if(issues.length){ $wrap.classList.remove('d-none'); $body.innerHTML=issues.slice(0,80).map(it=>`<tr><td>${it.r}</td><td>${it.col}</td><td>${(it.val||'').replace(/</g,'&lt;')}</td><td>${it.detail}</td></tr>`).join('') }
      $btn.disabled = !headerOk; // Cambia a (!headerOk || issues.length>0) si quieres bloquear por tipos.
    };
    reader.readAsText(file,'UTF-8');
  });
})();
</script>
@endpush


