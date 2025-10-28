<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos_caja_cusco_extrajudicial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote_id')->nullable()->index();

            // === columnas según tu Excel ===
            $table->string('region', 120)->nullable();
            $table->string('agencia', 120)->nullable();
            $table->string('titular', 255)->nullable();
            $table->string('dni', 20)->nullable()->index();
            $table->string('pagare', 40)->nullable()->index();
            $table->string('moneda', 20)->nullable();

            $table->string('tipo_de_recuperacion', 120)->nullable();
            $table->string('condicion', 120)->nullable();
            $table->string('demanda', 120)->nullable();

            // "Fecha" (la primera del archivo) -> fecha_de_pago
            $table->date('fecha_de_pago')->nullable();

            // "Pagado en Soles" (primera)
            $table->decimal('pagado_en_soles', 12, 2)->nullable();

            // "Monto Pagado"
            $table->decimal('monto_pagado', 12, 2)->nullable();

            // "VERIFICACION DE BPO"
            $table->string('verificacion_de_bpo', 255)->nullable();

            // "Estado final"
            $table->string('estado_final', 120)->nullable();

            // "CONCATENAR"
            $table->string('concatenar', 191)->nullable();

            // "Fecha" (segunda del archivo) -> fecha_alt
            $table->date('fecha_alt')->nullable();

            // "Pagado en Soles" (segundo) -> pagado_en_soles_alt
            $table->decimal('pagado_en_soles_alt', 12, 2)->nullable();

            // "Gestor"
            $table->string('gestor', 120)->nullable();

            // opcional, por si lo usas en filtros genéricos
            $table->string('status', 120)->nullable();

            $table->timestamps();

            $table->foreign('lote_id')->references('id')->on('pagos_lotes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_caja_cusco_extrajudicial');
    }
};
