<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla Principal: promesas_pago
        Schema::create('promesas_pago', function (Blueprint $table) {
            $table->id();

            // Datos básicos
            $table->string('dni')->index(); // Indexado para búsquedas rápidas
            $table->string('operacion')->nullable()->index(); // Operación principal
            $table->date('fecha_promesa');
            $table->decimal('monto', 15, 2); // Total a pagar
            $table->text('nota')->nullable();
            
            // Creador (Usuario)
            $table->foreignId('user_id')->constrained('users'); 

            // Estados
            $table->string('workflow_estado')->default('pendiente')->index(); // pendiente, preaprobada, aprobada, rechazada
            $table->string('cumplimiento_estado')->default('pendiente'); // pendiente, cumplida, caida
            $table->string('estado')->nullable(); // Campo legacy espejo

            // Tipo y detalles del convenio
            $table->string('tipo')->default('convenio'); // convenio | cancelacion
            $table->integer('nro_cuotas')->default(1);
            $table->decimal('monto_cuota', 15, 2)->nullable();
            $table->decimal('monto_convenio', 15, 2)->nullable();
            $table->date('fecha_pago')->nullable(); // Fecha primer pago
            $table->integer('cuota_dia')->nullable(); // Día de vencimiento mensual

            // Auditoría: Pre-Aprobación
            $table->foreignId('pre_aprobado_por')->nullable()->constrained('users');
            $table->dateTime('pre_aprobado_at')->nullable();
            $table->text('nota_preaprobacion')->nullable();

            // Auditoría: Aprobación
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->dateTime('aprobado_at')->nullable();
            $table->text('nota_aprobacion')->nullable();

            // Auditoría: Rechazo
            $table->foreignId('rechazado_por')->nullable()->constrained('users');
            $table->dateTime('rechazado_at')->nullable();
            $table->text('nota_rechazo')->nullable();

            $table->timestamps();
        });

        // 2. Tabla Hija: promesa_operaciones (Para cuando una promesa abarca varias operaciones)
        Schema::create('promesa_operaciones', function (Blueprint $table) {
            $table->id();
            
            // Relación con padre. Si se borra la promesa, se borran sus operaciones.
            $table->foreignId('promesa_id')
                  ->constrained('promesas_pago')
                  ->onDelete('cascade');

            $table->string('operacion')->index();
            $table->string('cartera')->nullable();

            $table->timestamps();
        });

        // 3. Tabla Hija: promesa_cuotas (Desglose de pagos)
        Schema::create('promesa_cuotas', function (Blueprint $table) {
            $table->id();

            // Relación con padre. Si se borra la promesa, se borran sus cuotas.
            $table->foreignId('promesa_id')
                  ->constrained('promesas_pago')
                  ->onDelete('cascade');

            $table->integer('nro'); // Número de cuota (1, 2, 3...)
            $table->date('fecha'); // Fecha vencimiento
            $table->decimal('monto', 15, 2);
            $table->boolean('es_balon')->default(false); // Si es cuota final grande

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // El orden inverso es importante por las claves foráneas
        Schema::dropIfExists('promesa_cuotas');
        Schema::dropIfExists('promesa_operaciones');
        Schema::dropIfExists('promesas_pago');
    }
};