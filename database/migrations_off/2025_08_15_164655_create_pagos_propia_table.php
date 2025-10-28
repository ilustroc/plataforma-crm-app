<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pagos_propia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lote_id')->nullable()->index();

            $table->string('dni', 32)->nullable();
            $table->string('operacion', 64)->nullable();
            $table->string('entidad', 100)->nullable();
            $table->string('equipos', 100)->nullable();
            $table->string('nombre_cliente', 150)->nullable();
            $table->string('producto', 100)->nullable();
            $table->string('moneda', 10)->nullable();
            $table->date('fecha_de_pago')->nullable();
            $table->decimal('monto_pagado', 15, 2)->nullable();
            $table->string('concatenar', 255)->nullable();
            $table->date('fecha')->nullable();
            $table->decimal('pagado_en_soles', 15, 2)->nullable();
            $table->string('gestor', 120)->nullable();
            $table->string('status', 50)->nullable();

            $table->timestamps();

            $table->index('dni');
            $table->index('operacion');
            $table->index('gestor');
            $table->index('status');
            $table->index('fecha_de_pago');
        });
    }
    public function down(): void {
        Schema::dropIfExists('pagos_propia');
    }
};
