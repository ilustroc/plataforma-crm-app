<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncatePromesas extends Command
{
    protected $signature = 'kpi:truncate-promesas {--with-cna}';
    protected $description = 'TRUNCATE promesas_pago + hijas (y cna_solicitudes opcional)';

    public function handle(): int
    {
        $this->warn('¡Esto borrará todo!');

        if (! $this->confirm('¿Seguro que deseas continuar?')) {
            $this->info('Cancelado.');
            return self::SUCCESS;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        DB::table('promesa_cuotas')->truncate();
        DB::table('promesa_operaciones')->truncate();
        DB::table('promesas_pago')->truncate();

        if ($this->option('with-cna')) {
            DB::table('cna_solicitudes')->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->info('Listo. Tablas truncadas.');
        return self::SUCCESS;
    }
}
