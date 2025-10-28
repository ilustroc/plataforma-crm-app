<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_caja_cusco_extrajudicial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote_id')->index();

            $table->string('region')->nullable();
            $table->string('agencia')->nullable();
            $table->string('titular')->nullable();

            $table->string('dni', 20)->nullable()->index();
            $table->string('pagare', 40)->nullable()->index();

            $table->string('moneda', 10)->nullable();
            $table->string('tipo_de_recuperacion')->nullable();
            $table->string('condicion')->nullable();
            $table->string('demanda')->nullable();

            $table->date('fecha_de_pago')->nullable()->index();

            $table->decimal('pagado_en_soles', 15, 2)->nullable();
            $table->decimal('monto_pagado', 15, 2)->nullable();

            $table->string('verificacion_de_bpo')->nullable();
            $table->string('estado_final')->nullable();

            $table->string('concatenar', 120)->nullable()->index();
            $table->string('gestor')->nullable()->index();
            $table->string('status')->nullable()->index();

            $table->timestamps();

            // Ajusta el nombre de la tabla si tu tabla de lotes se llama distinto.
            $table->foreign('lote_id')
                  ->references('id')->on('pago_lotes')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_caja_cusco_extrajudicial');
    }
};
