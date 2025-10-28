<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // si la tabla no existe, no hacemos nada
        if (!Schema::hasTable('promesas_pago')) return;

        Schema::table('promesas_pago', function (Blueprint $t) {
            // Estado del flujo
            if (!Schema::hasColumn('promesas_pago','estado')) {
                $t->string('estado',20)->default('pendiente')->index();
            }

            // Supervisor (pre-aprueba)
            if (!Schema::hasColumn('promesas_pago','pre_aprobado_por')) {
                $t->foreignId('pre_aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('promesas_pago','pre_aprobado_at')) {
                $t->timestamp('pre_aprobado_at')->nullable();
            }

            // Administrador (aprueba)
            if (!Schema::hasColumn('promesas_pago','aprobado_por')) {
                $t->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('promesas_pago','aprobado_at')) {
                $t->timestamp('aprobado_at')->nullable();
            }

            // Rechazos (sup o admin)
            if (!Schema::hasColumn('promesas_pago','rechazado_por')) {
                $t->foreignId('rechazado_por')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('promesas_pago','rechazado_at')) {
                $t->timestamp('rechazado_at')->nullable();
            }
            if (!Schema::hasColumn('promesas_pago','motivo_rechazo')) {
                $t->string('motivo_rechazo',500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('promesas_pago')) return;

        Schema::table('promesas_pago', function (Blueprint $t) {
            $cols = [
                'estado','pre_aprobado_por','pre_aprobado_at',
                'aprobado_por','aprobado_at',
                'rechazado_por','rechazado_at','motivo_rechazo',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('promesas_pago',$col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
