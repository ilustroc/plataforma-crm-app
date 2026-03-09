<?php

namespace App\Application\Reportes\Pagos;

use App\Infrastructure\Queries\PagosReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPagosReport
{
    public function __construct(private readonly PagosReportQuery $query)
    {
    }

    public function handle(PagosReportFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = $this->query->base();
        $q = $this->query->apply($q, $filters);
        $q = $this->query->selectColumns($q);

        return $q->orderByDesc('pagos.fecha')
            ->paginate($perPage)
            ->withQueryString();
    }
}
