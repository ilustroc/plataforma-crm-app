<?php
// database/migrations/2025_09_28_000000_add_fields_to_cna_solicitudes.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('cna_solicitudes', function (Blueprint $t) {
            // correlativo global para ordenar, y nro_carta (000001, 000002, …)
            $t->unsignedBigInteger('correlativo')->nullable()->unique()->after('id');
            $t->string('nro_carta', 32)->nullable()->unique()->after('correlativo');

            // nuevos campos del formulario
            $t->date('fecha_pago_realizado')->nullable()->after('nro_carta');
            $t->decimal('monto_pagado', 12, 2)->nullable()->after('fecha_pago_realizado');
            $t->text('observacion')->nullable()->after('monto_pagado');
        });
    }

    public function down(): void {
        Schema::table('cna_solicitudes', function (Blueprint $t) {
            $t->dropColumn(['correlativo','nro_carta','fecha_pago_realizado','monto_pagado','observacion']);
        });
    }
};
