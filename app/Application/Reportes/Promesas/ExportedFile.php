<?php

namespace App\Application\Reportes\Promesas;

final class ExportedFile
{
    public function __construct(
        public readonly string $tempPath,
        public readonly string $downloadName
    ) {}
}
