<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void
  {
    if (!Schema::hasTable('promesas_pago')) return;

    // Nuevos campos y separación de responsabilidades
    Schema::table('promesas_pago', function (Blueprint $t) {
      // === Autorización / Vista ===
      if (!Schema::hasColumn('promesas_pago','workflow_estado')) {
        $t->string('workflow_estado',20)->default('pendiente')->index()->after('monto'); // pendiente|preaprobada|aprobada|rechazada|rechazada_sup
      }

      // === Cumplimiento real ===
      if (!Schema::hasColumn('promesas_pago','cumplimiento_estado')) {
        $t->string('cumplimiento_estado',20)->default('pendiente')->after('workflow_estado'); // pendiente|cumplida|caida
      }

      // === Tipo de promesa ===
      if (!Schema::hasColumn('promesas_pago','tipo')) {
        $t->string('tipo',20)->default('parcial')->after('cumplimiento_estado'); // parcial|convenio|cancelacion|abono_libre
      }

      // === Campos plan (solo para convenio; null en otros tipos) ===
      if (!Schema::hasColumn('promesas_pago','nro_cuotas'))           $t->unsignedInteger('nro_cuotas')->nullable()->after('tipo');
      if (!Schema::hasColumn('promesas_pago','cuota_inicial'))        $t->decimal('cuota_inicial',18,2)->nullable()->after('nro_cuotas');
      if (!Schema::hasColumn('promesas_pago','monto_convenio'))       $t->decimal('monto_convenio',18,2)->nullable()->after('cuota_inicial');
      if (!Schema::hasColumn('promesas_pago','monto_primera_cuota'))  $t->decimal('monto_primera_cuota',18,2)->nullable()->after('monto_convenio');
      if (!Schema::hasColumn('promesas_pago','fecha_primera_cuota'))  $t->date('fecha_primera_cuota')->nullable()->after('monto_primera_cuota');

      // === Fecha de compromiso "final" para todos los tipos (convenio/cancelación/parcial/abono_libre) ===
      if (!Schema::hasColumn('promesas_pago','fecha_pago')) {
        $t->date('fecha_pago')->nullable()->after('fecha_promesa');
      }
    });

    // Migrar estado→workflow_estado si existía
    if (Schema::hasColumn('promesas_pago','estado')) {
      DB::statement("UPDATE promesas_pago SET workflow_estado = estado WHERE estado IS NOT NULL");
    }

    // Pivote multi-operación
    if (!Schema::hasTable('promesa_operaciones')) {
      Schema::create('promesa_operaciones', function (Blueprint $t) {
        $t->id();
        $t->foreignId('promesa_id')->constrained('promesas_pago')->cascadeOnDelete();
        $t->string('operacion',50)->index();
        $t->string('cartera',50)->nullable()->index();
        $t->timestamps();
        $t->unique(['promesa_id','operacion']);
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasTable('promesa_operaciones')) {
      Schema::drop('promesa_operaciones');
    }
    if (Schema::hasTable('promesas_pago')) {
      Schema::table('promesas_pago', function (Blueprint $t) {
        foreach ([
          'workflow_estado','cumplimiento_estado','tipo',
          'nro_cuotas','cuota_inicial','monto_convenio',
          'monto_primera_cuota','fecha_primera_cuota','fecha_pago'
        ] as $col) {
          if (Schema::hasColumn('promesas_pago',$col)) $t->dropColumn($col);
        }
      });
    }
  }
};
