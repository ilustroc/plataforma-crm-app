{{-- resources/views/reportes/pagos/propia.blade.php --}}

{{-- Meta que usa el índice para los botones de export y el resumen --}}
<div id="pagMeta"
     data-page="{{ $rows->currentPage() }}"
     data-total="{{ $rows->total() }}"></div>

{{-- Estilos scoped a este parcial para que funcionen también cuando se carga por AJAX --}}
<style>
  .rpt-propia .table thead th{
    position: sticky; top: 0; z-index: 1;
    background: color-mix(in oklab, var(--surface-2) 55%, transparent);
    color: var(--ink);
  }
  [data-theme="dark"] .rpt-propia .table thead th{
    background: color-mix(in oklab, var(--surface-2) 40%, transparent);
  }
  .rpt-propia .table tbody td{ color: var(--ink) }
  .rpt-propia .table tbody tr:nth-child(even){
    background: color-mix(in oklab, var(--surface-2) 22%, transparent);
  }
  .rpt-propia .table tbody tr:hover{
    background: color-mix(in oklab, var(--brand) 10%, transparent);
  }

  /* Paginación sin el azul de Bootstrap */
  .rpt-propia .pagination{ margin-bottom: 0 }
  .rpt-propia .page-link{
    border-color: var(--border);
    background: var(--surface);
    color: var(--ink);
  }
  .rpt-propia .page-link:hover{
    background: color-mix(in oklab, var(--brand) 10%, transparent);
    border-color: color-mix(in oklab, var(--brand) 30%, transparent);
    color: var(--ink);
  }
  .rpt-propia .page-item.active .page-link{
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
  }
  .rpt-propia .page-item.disabled .page-link{
    color: var(--muted);
    background: var(--surface);
  }

  .rpt-propia .pager-meta{ color: var(--muted) }
</style>

<div class="rpt-propia">
  {{-- Tabla: Propia (10 por página) --}}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>DNI</th>
          <th>Operación</th>
          <th>Entidad</th>
          <th>Equipos</th>
          <th>Cliente</th>
          <th>Producto</th>
          <th>Moneda</th>
          <th>F. Pago</th>
          <th class="text-end">Monto Pagado</th>
          <th>Gestor</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr>
            <td class="text-nowrap">{{ $r->dni }}</td>
            <td class="text-nowrap">{{ $r->operacion }}</td>
            <td>{{ $r->entidad }}</td>
            <td>{{ $r->equipos }}</td>
            <td>{{ $r->nombre_cliente }}</td>
            <td>{{ $r->producto }}</td>
            <td>{{ $r->moneda }}</td>
            <td class="text-nowrap">{{ optional($r->fecha_de_pago)->format('Y-m-d') }}</td>
            <td class="text-end">{{ number_format((float)$r->pagado_en_soles, 2) }}</td>
            <td>{{ $r->gestor }}</td>
          </tr>
        @empty
          <tr><td colspan="12" class="text-secondary">Sin resultados.</td></tr>
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
