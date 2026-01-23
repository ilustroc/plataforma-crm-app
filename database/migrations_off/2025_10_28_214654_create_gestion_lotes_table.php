<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('gestion_lotes', function (Blueprint $t) {
            $t->id();
            $t->string('tipo', 40)->default('propia');     // por ahora solo 'propia'
            $t->string('archivo', 255)->nullable();
            $t->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('total_registros')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('gestion_lotes'); }
};
