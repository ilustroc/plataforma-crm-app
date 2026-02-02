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
        // Crear la nueva tabla 'carteras'
        Schema::create('carteras', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('cuenta')->nullable()->index();      // Index para búsquedas rápidas
            $table->string('operacion')->nullable()->index();   // Index para cruzar con Pagos
            $table->string('documento')->index();               // DNI/RUC
            $table->string('nombre');                           // Nombre del Cliente

            // Ubicación
            $table->string('departamento')->nullable();
            $table->string('provincia')->nullable();
            $table->string('distrito')->nullable();
            $table->text('direccion')->nullable();

            // Datos de la Deuda
            $table->date('fecha_compra')->nullable();
            $table->string('entidad')->nullable();              // Ej: CAJA AREQUIPA
            $table->string('cosecha')->nullable();              // Ej: CAJAAQP2
            $table->date('fecha_castigo')->nullable();
            $table->string('producto')->nullable();             // Ej: Créd. Microempresas
            $table->string('moneda')->default('SOLES');

            // Importes (Double según tu requerimiento)
            $table->double('saldo_capital', 15, 2)->default(0);
            $table->double('intereses', 15, 2)->default(0);
            $table->double('deuda_total', 15, 2)->default(0);

            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at (Papelera)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carteras');
    }
};