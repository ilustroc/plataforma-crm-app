<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('promesas_pago')) return;

        Schema::table('promesas_pago', function (Blueprint $t) {
            if (!Schema::hasColumn('promesas_pago','monto_cuota')) {
                $t->decimal('monto_cuota', 18, 2)->nullable()->after('monto_convenio');
            }
            if (!Schema::hasColumn('promesas_pago','cuota_dia')) {
                $t->unsignedTinyInteger('cuota_dia')->nullable()->after('monto_cuota'); // 1..31
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('promesas_pago')) return;
        Schema::table('promesas_pago', function (Blueprint $t) {
            if (Schema::hasColumn('promesas_pago','monto_cuota')) $t->dropColumn('monto_cuota');
            if (Schema::hasColumn('promesas_pago','cuota_dia'))   $t->dropColumn('cuota_dia');
        });
    }
};
