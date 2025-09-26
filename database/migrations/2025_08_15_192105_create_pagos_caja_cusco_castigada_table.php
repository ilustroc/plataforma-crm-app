<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pagos_caja_cusco_castigada', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote_id')->nullable()->index();

            $table->string('abogado', 200)->nullable();
            $table->string('region', 120)->nullable();
            $table->string('agencia', 120)->nullable();
            $table->string('titular', 180)->nullable();
            $table->string('dni', 32)->nullable();
            $table->string('pagare', 80)->nullable();
            $table->string('moneda', 10)->nullable();
            $table->string('tipo_de_recuperacion', 80)->nullable();
            $table->string('condicion', 80)->nullable();
            $table->string('cartera', 120)->nullable();
            $table->string('demanda', 80)->nullable();
            $table->date('fecha_de_pago')->nullable();
            $table->decimal('pago_en_soles', 15, 2)->nullable();
            $table->string('concatenar', 255)->nullable();
            $table->date('fecha')->nullable();
            $table->decimal('pagado_en_soles', 15, 2)->nullable();
            $table->string('gestor', 120)->nullable();
            $table->string('status', 60)->nullable();

            $table->timestamps();

            $table->index(['dni','fecha_de_pago'], 'pp_dni_fecha_idx');
            $table->index(['gestor','status'], 'pp_gestor_status_idx');
        });
    }
    public function down(): void {
        Schema::dropIfExists('pagos_caja_cusco_castigada');
    }
};
