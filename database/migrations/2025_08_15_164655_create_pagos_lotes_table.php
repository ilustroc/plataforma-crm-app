<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pagos_lotes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50); // 'propia', 'caja-arequipa-castigada', etc.
            $table->string('archivo')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedInteger('total_registros')->default(0);
            $table->timestamps();

            $table->index(['tipo','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('pagos_lotes');
    }
};
