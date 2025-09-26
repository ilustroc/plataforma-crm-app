{{-- resources/views/reportes/pagos/extrajudicial.blade.php --}}

{{-- Meta que usa el índice para los botones de export y el resumen --}}
<div id="pagMeta"
     data-page="{{ $rows->currentPage() }}"
     data-total="{{ $rows->total() }}"></div>

{{-- Estilos scoped a este parcial para que funcionen también cuando se carga por AJAX --}}
<style>
  .rpt-extrajudicial .table thead th{
    position: sticky; top: 0; z-index: 1;
    background: color-mix(in oklab, var(--surface-2) 55%, transparent);
    color: var(--ink);
  }
  [data-theme="dark"] .rpt-extrajudicial .table thead th{
    background: color-mix(in oklab, var(--surface-2) 40%, transparent);
  }
  .rpt-extrajudicial .table tbody td{ color: var(--ink) }
  .rpt-extrajudicial .table tbody tr:nth-child(even){
    background: color-mix(in oklab, var(--surface-2) 22%, transparent);
  }
  .rpt-extrajudicial .table tbody tr:hover{
    background: color-mix(in oklab, var(--brand) 10%, transparent);
  }

  /* Paginación sin el azul de Bootstrap */
  .rpt-extrajudicial .pagination{ margin-bottom: 0 }
  .rpt-extrajudicial .page-link{
    border-color: var(--border);
    background: var(--surface);
    color: var(--ink);
  }
  .rpt-extrajudicial .page-link:hover{
    background: color-mix(in oklab, var(--brand) 10%, transparent);
    border-color: color-mix(in oklab, var(--brand) 30%, transparent);
    color: var(--ink);
  }
  .rpt-extrajudicial .page-item.active .page-link{
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
  }
  .rpt-extrajudicial .page-item.disabled .page-link{
    color: var(--muted);
    background: var(--surface);
  }

  .rpt-extrajudicial .pager-meta{ color: var(--muted) }
</style>

<div class="rpt-extrajudicial">
  {{-- Tabla: Caja Cusco ▸ Extrajudicial (10 por página) --}}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>DNI</th>
          <th>Pagaré</th>
          <th>Titular</th>
          <th>Moneda</th>
          <th>Tipo Recup.</th>
          <th>Cartera</th>
          <th>F. Pago</th>
          <th class="text-end">Pago S/</th>
          <th class="text-end">Pagado en S/</th>
          <th>Gestor</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr>
            <td class="text-nowrap">{{ $r->dni }}</td>
            <td class="text-nowrap">{{ $r->pagare }}</td>
            <td>{{ $r->titular }}</td>
            <td>{{ $r->moneda }}</td>
            <td>{{ $r->tipo_de_recuperacion }}</td>
            <td>{{ $r->cartera }}</td>
            <td class="text-nowrap">{{ optional($r->fecha_de_pago)->format('Y-m-d') }}</td>
            <td class="text-end">{{ number_format((float)$r->pagado_en_soles, 2) }}</td>
            <td>{{ $r->gestor }}</td>
            <td>{{ $r->status }}</td>
          </tr>
        @empty
          <tr><td colspan="11" class="text-secondary">Sin resultados.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-2">
    <div class="small pager-meta">
      Mostrando {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} de {{ $rows->total() }}.
    </div>
    {{-- Paginación con Bootstrap 5 para heredar nuestros estilos pill --}}
    {{ $rows->onEachSide(1)->withQueryString()->links('pagination::bootstrap-5') }}
  </div>
</div>