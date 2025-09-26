<?php

// database/migrations/2025_09_10_224456_create_clientes_cuentas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('clientes_cuentas', function (Blueprint $t) {
      $t->id();
      $t->string('cartera', 80)->nullable();
      $t->string('tipo_doc', 10)->nullable();
      $t->string('dni', 30)->nullable()->index();
      $t->string('operacion', 50)->nullable()->index();
      $t->string('concatenar', 120)->nullable()->unique(); // dni+operacion
      $t->string('agente', 120)->nullable();
      $t->string('titular', 180)->nullable();
      $t->smallInteger('anio_castigo')->nullable();
      $t->string('moneda', 10)->nullable();     // PEN / SOLES
      $t->string('entidad', 120)->nullable();
      $t->string('producto', 120)->nullable();
      $t->string('cosecha', 120)->nullable();
      $t->string('departamento', 80)->nullable();
      $t->string('zona', 80)->nullable();
      $t->string('ubicacion_geografica', 160)->nullable();
      $t->date('fecha_compra')->nullable();
      $t->smallInteger('edad')->nullable();
      $t->string('sexo', 5)->nullable();
      $t->string('estado_civil', 30)->nullable();
      $t->string('telf1', 40)->nullable();
      $t->string('telf2', 40)->nullable();
      $t->string('telf3', 40)->nullable();
      $t->decimal('saldo_capital', 18,2)->nullable();
      $t->decimal('interes', 18,2)->nullable();
      $t->decimal('deuda_total', 18,2)->nullable();
      $t->string('laboral', 10)->nullable();          // SI/NO
      $t->string('vehiculos', 10)->nullable();        // SI/NO
      $t->string('propiedades', 10)->nullable();      // SI/NO
      $t->string('consolidado_veh_prop', 30)->nullable(); // SI-NO, etc.
      $t->string('clasificacion', 40)->nullable();    // PERDIDA, etc.
      $t->integer('score')->nullable();
      $t->string('correo_electronico', 160)->nullable();
      $t->string('direccion', 200)->nullable();
      $t->string('provincia', 80)->nullable();
      $t->string('distrito', 80)->nullable();
      $t->timestamps();

      // único útil si viene (dni,operacion)
      $t->unique(['dni','operacion']);
    });
  }
  public function down(): void { Schema::dropIfExists('clientes_cuentas'); }
};
