<?php

// database/migrations/2025_09_01_000010_create_promesas_pago_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('promesas_pago', function (Blueprint $t) {
      $t->id();
      $t->string('dni', 30)->index();
      $t->string('operacion', 50)->nullable()->index();
      $t->date('fecha_promesa');
      $t->decimal('monto', 18, 2);
      $t->string('estado', 20)->default('pendiente'); // pendiente|cumplida|caida
      $t->string('nota', 500)->nullable();
      $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
      $t->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('promesas_pago');
  }
};