<?php

namespace App\Application\Reportes\Pagos;

final class PagosReportFilters
{
    public function __construct(
        public readonly ?string $q,
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly ?string $gestor,
        public readonly ?string $cartera,
    ) {}
}
