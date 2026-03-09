<?php

namespace App\Application\Reportes\Pagos;

final class ExportedFile
{
    public function __construct(
        public readonly string $tempPath,
        public readonly string $downloadName
    ) {}
}
