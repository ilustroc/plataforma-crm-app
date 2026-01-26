{{-- resources/views/placeholders/gestiones/propia.blade.php --}}
@push('head')
<style>
  .upload-card a{ color:var(--brand) } .upload-card a:hover{ color:var(--brand-ink) }
  .upload-card .btn-primary{ background:var(--brand); border-color:var(--brand) }
  .upload-card .btn-primary:hover{ background:var(--brand-ink); border-color:var(--brand-ink) }

  .pill{display:inline-flex;align-items:center;gap:.35rem;border:1px solid var(--border);background:var(--surface);
        border-radius:999px;padding:.18rem .6rem;font-size:.8rem}
  .mini{font-size:.9rem;color:var(--muted)}
  .ok{color:#0a7a3d} .err{color:#b42318} .warn{color:#8a6a00}
</style>
@endpush

<div class="card pad mb-3 upload-card">
  <h5 class="mb-2 d-flex align-items-center gap-2">
    <i class="bi bi-upload"></i> <span>Subida de archivo (GESTIONES ▸ PROPIA)</span>
  </h5>
  <p class="text-secondary mb-2">
    Formato aceptado: <strong>CSV UTF-8</strong>, delimitado por <strong>coma</strong> o <strong>punto y coma</strong>.
    <a href="{{ route('integracion.gestiones.template.propia') }}">Descargar plantilla</a>.
  </p>

  <form method="POST" action="{{ route('integracion.gestiones.import.propia') }}" enctype="multipart/form-data"
        class="row g-2 align-items-end" id="formImportGestPropia">
    @csrf
    <div class="col-lg-7">
      <label class="form-label">Archivo CSV</label>
      <input type="file" name="archivo" id="csvFileGestPropia" class="form-control" accept=".csv,text/csv" required>
      <div class="form-text">
        Encabezados esperados:
        <span class="pill">DOCUMENTO</span>
        <span class="pill">CLIENTE</span>
        <span class="pill">NIVEL 3</span>
        <span class="pill">CONTACTO</span>
        <span class="pill">AGENTE</span>
        <span class="pill">OPERACION</span>
        <span class="pill">ENTIDAD</span>
        <span class="pill">EQUIPO</span>
        <span class="pill">FECHA GESTION</span>
        <span class="pill">FECHA CITA</span>
        <span class="pill">TELEFONO</span>
        <span class="pill">OBSERVACION</span>
        <span class="pill">MONTO PROMESA</span>
        <span class="pill">NRO CUOTAS</span>
        <span class="pill">FECHA PROMESA</span>
        <span class="pill">PROCEDENCIA LLAMADA</span>
      </div>
    </div>
    <div class="col-lg-3">
      <button class="btn btn-primary w-100" id="btnImportGestPropia" disabled>
        <i class="bi bi-cloud-upload me-1"></i> Importar
      </button>
    </div>

    {{-- Pre-check --}}
    <div class="col-12 d-none" id="precheckBoxGestPropia">
      <hr class="my-2">
      <div class="d-flex flex-wrap align-items-center gap-3">
        <div><i class="bi bi-check-circle-fill ok me-1"></i><span class="mini" id="hdrMsgGestPropia">Validando encabezados…</span></div>
        <div><i class="bi bi-123 warn me-1"></i><span class="mini" id="typeMsgGestPropia">Tipos por muestra: —</span></div>
      </div>
      <div class="table-responsive mt-2 d-none" id="issuesWrapGestPropia">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Fila</th><th>Columna</th><th>Valor</th><th>Detalle</th></tr></thead>
          <tbody id="issuesBodyGestPropia"></tbody>
        </table>
      </div>
    </div>
  </form>
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
            <th>DNI</th><th>Cliente</th><th>Nivel 3</th><th>Contacto</th><th>Agente</th>
            <th>Operación</th><th>Entidad</th><th>Equipo</th>
            <th>F. Gestión</th><th>F. Cita</th><th>Teléfono</th>
            <th class="text-end">Monto Promesa</th><th class="text-end">Nro Cuotas</th><th>F. Promesa</th>
            <th>Procedencia</th><th>Obs.</th>
          </tr>
        </thead>
        <tbody>
          @forelse($gestionesPropia as $g)
            <tr>
              <td class="text-nowrap">{{ $g->documento ?? $g->dni }}</td>
              <td>{{ $g->cliente }}</td>
              <td>{{ $g->nivel_3 }}</td>
              <td>{{ $g->contacto }}</td>
              <td>{{ $g->agente }}</td>
              <td class="text-nowrap">{{ $g->operacion }}</td>
              <td>{{ $g->entidad }}</td>
              <td>{{ $g->equipo }}</td>
              <td class="text-nowrap">{{ optional($g->fecha_gestion)->format('Y-m-d') }}</td>
              <td class="text-nowrap">{{ optional($g->fecha_cita)->format('Y-m-d H:i') }}</td>
              <td class="text-nowrap">{{ $g->telefono }}</td>
              <td class="text-end">{{ number_format((float)$g->monto_promesa, 2) }}</td>
              <td class="text-end">{{ (int)$g->nro_cuotas }}</td>
              <td class="text-nowrap">{{ optional($g->fecha_promesa)->format('Y-m-d') }}</td>
              <td>{{ $g->procedencia_llamada }}</td>
              <td class="text-truncate" style="max-width:320px">{{ $g->observacion }}</td>
            </tr>
          @empty
            <tr><td colspan="16" class="text-secondary">Sin datos para mostrar.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @endif
</div>

@push('scripts')
<script>
(function(){
  const HEADERS = [
    "DOCUMENTO","CLIENTE","NIVEL 3","CONTACTO","AGENTE","OPERACION","ENTIDAD","EQUIPO",
    "FECHA GESTION","FECHA CITA","TELEFONO","OBSERVACION","MONTO PROMESA","NRO CUOTAS",
    "FECHA PROMESA","PROCEDENCIA LLAMADA"
  ];
  const $file = document.getElementById('csvFileGestPropia');
  const $btn  = document.getElementById('btnImportGestPropia');
  const $box  = document.getElementById('precheckBoxGestPropia');
  const $hdr  = document.getElementById('hdrMsgGestPropia');
  const $typ  = document.getElementById('typeMsgGestPropia');
  const $wrap = document.getElementById('issuesWrapGestPropia');
  const $body = document.getElementById('issuesBodyGestPropia');

  function splitCSV(line, sep){ const out=[]; let cur=''; let q=false;
    for(let i=0;i<line.length;i++){ const c=line[i];
      if(c==='"'){ q=!q } else if(c===sep && !q){ out.push(cur); cur='' } else { cur+=c }
    } out.push(cur); return out.map(s=>s.replace(/^"|"$/g,'').trim());
  }
  function parseCSV(text){
    text=(text||'').replace(/\r/g,'');
    const lines=text.split(/\n+/).filter(Boolean);
    if(!lines.length) return {rows:[],sep:','};
    const sep=(lines[0].split(';').length > lines[0].split(',').length) ? ';' : ',';
    return {rows:lines.map(l=>splitCSV(l,sep)), sep};
  }

  const isNumber=v=>{ if(v===''||v==null) return true; const x=(v+'').replace(/\s/g,'').replace(/,/g,'.'); return /^-?\d+(\.\d+)?$/.test(x) }
  const isInteger=v=>{ if(v===''||v==null) return true; return /^-?\d+$/.test((v+'').trim()) }
  const parseDate=v=>{
    if(!v) return null; const s=(v+'').trim();
    let m=s.match(/^(\d{4})-(\d{2})-(\d{2})$/); if(m) return s;
    m=s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);   if(m) return `${m[3]}-${m[2]}-${m[1]}`;
    return null;
  };
  const parseDateTime=v=>{
    if(!v) return null; const s=(v+'').trim().replace('T',' ');
    let m=s.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})(?::\d{2})?$/); if(m) return `${m[1]}-${m[2]}-${m[3]} ${m[4]}:${m[5]}`;
    m=s.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})(?::\d{2})?$/);   if(m) return `${m[3]}-${m[2]}-${m[1]} ${m[4]}:${m[5]}`;
    return null;
  };
  const isDate=v=> v==='' || parseDate(v)!=null;
  const isDateTime=v=> v==='' || parseDateTime(v)!=null;
  const isPhone=v=>{ if(v===''||v==null) return true; return /^[0-9()+\-\s]+$/.test((v+'').trim()) }
  const isDni=v=>{ if(v===''||v==null) return true; return /^\d{8}$/.test((v+'').trim()) }

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
          if(h==='FECHA GESTION' || h==='FECHA PROMESA'){ ok=isDate(val); if(!ok) detail='Fecha inválida. Use YYYY-MM-DD o DD/MM/YYYY' }
          else if(h==='FECHA CITA'){ ok=isDateTime(val); if(!ok) detail='Fecha/hora inválida. Use YYYY-MM-DD HH:MM o DD/MM/YYYY HH:MM' }
          else if(h==='MONTO PROMESA'){ ok=isNumber(val); if(!ok) detail='Número inválido' }
          else if(h==='NRO CUOTAS'){ ok=isInteger(val); if(!ok) detail='Entero inválido' }
          else if(h==='TELEFONO'){ ok=isPhone(val); if(!ok) detail='Teléfono con caracteres inválidos' }
          else if(h==='DOCUMENTO'){ ok=isDni(val); if(!ok) detail='DNI debe tener 8 dígitos' }
          if(!ok){ issues.push({r:r+1,col:h,val,detail}) }
        });
      }

      $typ.textContent = `Tipos por muestra: ${sampled} fila(s) verificadas, ${issues.length} posible(s) problema(s)`;
      if(issues.length){
        $wrap.classList.remove('d-none');
        $body.innerHTML = issues.slice(0,120).map(it=>`<tr><td>${it.r}</td><td>${it.col}</td><td>${(it.val||'').replace(/</g,'&lt;')}</td><td>${it.detail}</td></tr>`).join('');
      }
      $btn.disabled = !headerOk; // Cambia a (!headerOk || issues.length>0) si quieres bloquear por tipos
    };
    reader.readAsText(file,'UTF-8');
  });
})();
</script>
@endpush
