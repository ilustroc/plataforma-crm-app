<?php

namespace App\Application\Reportes\Pagos;

use App\Infrastructure\Exports\PagosReportExcelExporter;
use App\Infrastructure\Queries\PagosReportQuery;

class ExportPagosReport
{
    public function __construct(
        private readonly PagosReportQuery $query,
        private readonly PagosReportExcelExporter $exporter
    ) {
    }

    public function handle(PagosReportFilters $filters): ExportedFile
    {
        $q = $this->query->base();
        $q = $this->query->apply($q, $filters);
        $q = $this->query->selectColumns($q);

        return $this->exporter->export($q);
    }
}
