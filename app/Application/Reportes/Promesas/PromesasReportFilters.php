<?php

namespace App\Application\Reportes\Promesas;

final class PromesasReportFilters
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $estado,
        public readonly string $gestor,
        public readonly string $q,
    ) {}
}
