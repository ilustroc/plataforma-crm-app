<?php
// database/migrations/2025_09_28_000000_add_fields_to_cna_solicitudes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cna_solicitudes', function (Blueprint $t) {
            // Correlativo numérico para ordenar / generar nro_carta
            if (!Schema::hasColumn('cna_solicitudes', 'correlativo')) {
                $t->unsignedBigInteger('correlativo')
                  ->nullable()
                  ->unique()
                  ->after('id');
            }

            // Nuevos datos solicitados en el formulario
            if (!Schema::hasColumn('cna_solicitudes', 'fecha_pago_realizado')) {
                $t->date('fecha_pago_realizado')->nullable()->after('nro_carta');
            }
            if (!Schema::hasColumn('cna_solicitudes', 'monto_pagado')) {
                $t->decimal('monto_pagado', 12, 2)->nullable()->after('fecha_pago_realizado');
            }
            if (!Schema::hasColumn('cna_solicitudes', 'observacion')) {
                $t->text('observacion')->nullable()->after('monto_pagado');
            }
        });

        // ------- Backfill opcional (seguro) -------
        // Asigna correlativos y nro_carta a filas antiguas que no lo tengan.
        DB::transaction(function () {
            // Si ya hay correlativos, partimos del máximo
            $next = (int) DB::table('cna_solicitudes')->max('correlativo');
            $rows = DB::table('cna_solicitudes')
                ->select('id', 'correlativo', 'nro_carta')
                ->orderBy('id')
                ->get();

            foreach ($rows as $r) {
                $needsCorr = empty($r->correlativo);
                $needsNro  = empty($r->nro_carta);

                if ($needsCorr || $needsNro) {
                    $next++;
                    $nro = str_pad($next, 6, '0', STR_PAD_LEFT);

                    DB::table('cna_solicitudes')
                        ->where('id', $r->id)
                        ->update([
                            'correlativo' => $needsCorr ? $next : $r->correlativo,
                            'nro_carta'   => $needsNro  ? $nro  : $r->nro_carta,
                        ]);
                }
            }
        });
        // ------- /Backfill opcional -------
    }

    public function down(): void
    {
        Schema::table('cna_solicitudes', function (Blueprint $t) {
            // Elimina en orden inverso
            if (Schema::hasColumn('cna_solicitudes', 'observacion')) {
                $t->dropColumn('observacion');
            }
            if (Schema::hasColumn('cna_solicitudes', 'monto_pagado')) {
                $t->dropColumn('monto_pagado');
            }
            if (Schema::hasColumn('cna_solicitudes', 'fecha_pago_realizado')) {
                $t->dropColumn('fecha_pago_realizado');
            }
            if (Schema::hasColumn('cna_solicitudes', 'correlativo')) {
                $t->dropColumn('correlativo');
            }
        });
    }
};
