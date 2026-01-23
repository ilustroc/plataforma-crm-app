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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            
            // Datos del Cliente y Operación
            $table->string('documento', 20)->index(); // Indexado para búsquedas rápidas por DNI
            $table->string('operacion')->nullable()->index(); // Código de operación o crédito
            
            // Detalles del Pago
            $table->string('moneda', 5)->default('PEN'); // PEN, USD, S/, etc.
            $table->date('fecha')->index(); // Indexado para filtrar rangos en la gráfica
            $table->decimal('monto', 15, 2); // 15 dígitos en total, 2 decimales (ej: 1234567890123.99)
            
            // Responsable
            $table->string('gestor')->nullable(); // Guardamos el nombre o código del gestor
            
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};