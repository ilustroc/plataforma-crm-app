<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('promesas_pago')) return;

        Schema::table('promesas_pago', function (Blueprint $t) {
            foreach (['cuota_inicial','monto_primera_cuota','fecha_primera_cuota'] as $col) {
                if (Schema::hasColumn('promesas_pago', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('promesas_pago')) return;

        Schema::table('promesas_pago', function (Blueprint $t) {
            if (!Schema::hasColumn('promesas_pago','cuota_inicial')) {
                $t->decimal('cuota_inicial', 18, 2)->nullable()->after('nro_cuotas');
            }
            if (!Schema::hasColumn('promesas_pago','monto_primera_cuota')) {
                $t->decimal('monto_primera_cuota', 18, 2)->nullable()->after('monto_cuota');
            }
            if (!Schema::hasColumn('promesas_pago','fecha_primera_cuota')) {
                $t->date('fecha_primera_cuota')->nullable()->after('monto_primera_cuota');
            }
        });
    }
};
