<?php

// database/migrations/2025_09_23_000000_create_promesa_cuotas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('promesa_cuotas', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('promesa_id');
            $t->unsignedInteger('nro');           // 1..N
            $t->date('fecha');
            $t->decimal('monto', 12, 2);
            $t->boolean('es_balon')->default(false);
            $t->timestamps();

            $t->foreign('promesa_id')->references('id')->on('promesas_pago')->onDelete('cascade');
            $t->index(['promesa_id','nro']);
        });
    }
    public function down(): void { Schema::dropIfExists('promesa_cuotas'); }
};
