<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cna_solicitudes', function (Blueprint $t) {
            $t->id();

            // Contenido de la solicitud
            $t->string('nro_carta')->unique();   // correlativo CNA
            $t->string('dni', 30)->index();
            $t->string('titular')->nullable();
            $t->string('producto')->nullable();
            $t->json('operaciones');             // ["6799186", ...]
            $t->text('nota')->nullable();

            // Flujo de autorización (igual que promesas)
            $t->string('workflow_estado')->default('pendiente'); // pendiente|preaprobada|aprobada|rechazada_sup|rechazada

            // Auditoría
            $t->unsignedBigInteger('user_id')->nullable();        // quien solicita
            $t->unsignedBigInteger('pre_aprobado_por')->nullable();
            $t->timestamp('pre_aprobado_at')->nullable();
            $t->unsignedBigInteger('aprobado_por')->nullable();
            $t->timestamp('aprobado_at')->nullable();
            $t->unsignedBigInteger('rechazado_por')->nullable();
            $t->timestamp('rechazado_at')->nullable();
            $t->text('motivo_rechazo')->nullable();

            // Documento final (cuando se genere)
            $t->string('docx_path')->nullable(); // "CNA {nro_carta} - {dni}.docx"

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cna_solicitudes');
    }
};
