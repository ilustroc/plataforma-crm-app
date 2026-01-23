<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tabla_gestiones_propias', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lote_id')->nullable()->constrained('gestion_lotes')->nullOnDelete();

            $t->string('documento', 20)->nullable()->index();      // DNI
            $t->string('cliente', 190)->nullable();
            $t->string('nivel_3', 120)->nullable();
            $t->string('contacto', 60)->nullable();
            $t->string('agente', 120)->nullable();
            $t->string('operacion', 60)->nullable()->index();
            $t->string('entidad', 120)->nullable();
            $t->string('equipo', 120)->nullable();
            $t->date('fecha_gestion')->nullable();
            $t->dateTime('fecha_cita')->nullable();
            $t->string('telefono', 25)->nullable();
            $t->text('observacion')->nullable();
            $t->decimal('monto_promesa', 12, 2)->nullable();
            $t->unsignedInteger('nro_cuotas')->nullable();
            $t->date('fecha_promesa')->nullable();
            $t->string('procedencia_llamada', 120)->nullable();

            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tabla_gestiones_propias'); }
};
