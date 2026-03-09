<?php

namespace App\Application\Reportes\Promesas;

use App\Infrastructure\Queries\PromesasReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPromesasReport
{
    public function __construct(private readonly PromesasReportQuery $query)
    {
    }

    public function handle(PromesasReportFilters $filters, int $perPage = 25): LengthAwarePaginator
    {
        $qb = $this->query->base();
        $qb = $this->query->selectColumns($qb);
        $qb = $this->query->apply($qb, $filters);
        $qb = $this->query->ordered($qb);

        return $qb->paginate($perPage)->withQueryString();
    }
}
