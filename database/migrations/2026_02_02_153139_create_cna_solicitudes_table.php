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
        Schema::create('cna_solicitudes', function (Blueprint $table) {
            $table->id();

            // --- Identificación del Documento ---
            // Correlativo interno (ej: 001-2025) - Nullable porque se genera al aprobar
            $table->string('correlativo')->nullable()->index(); 
            $table->string('nro_carta')->nullable(); 

            // --- Datos del Cliente ---
            $table->string('dni')->index();
            $table->string('titular');
            $table->string('producto')->nullable();
            
            // Al ser cast 'array' en el modelo, usamos 'json' en la BD
            $table->json('operaciones')->nullable(); 

            // --- Datos del Pago ---
            $table->date('fecha_pago_realizado')->nullable();
            $table->decimal('monto_pagado', 15, 2)->default(0);

            // --- Notas y Observaciones ---
            $table->text('nota')->nullable();       // Nota interna del asesor
            $table->text('observacion')->nullable(); // Observación administrativa

            // --- Workflow / Estado ---
            $table->string('workflow_estado')->default('pendiente')->index(); 
            // pendiente, preaprobada, aprobada, rechazada

            // --- Relación: Creador ---
            $table->foreignId('user_id')->constrained('users');

            // --- Auditoría: Pre-Aprobación (Supervisor) ---
            $table->foreignId('pre_aprobado_por')->nullable()->constrained('users');
            $table->dateTime('pre_aprobado_at')->nullable();

            // --- Auditoría: Aprobación Final (Administrador) ---
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->dateTime('aprobado_at')->nullable();

            // --- Auditoría: Rechazo ---
            $table->foreignId('rechazado_por')->nullable()->constrained('users');
            $table->dateTime('rechazado_at')->nullable();
            $table->text('motivo_rechazo')->nullable();

            // --- Archivos Generados ---
            $table->string('docx_path')->nullable(); // Ruta al Word generado
            $table->string('pdf_path')->nullable();  // Ruta al PDF firmado/final

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cna_solicitudes');
    }
};