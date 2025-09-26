<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoPropia extends Model
{
    protected $table = 'pagos_propia';

    protected $fillable = [
        'lote_id',
        'dni',
        'operacion',
        'entidad',
        'equipos',
        'nombre_cliente',
        'producto',
        'moneda',
        'fecha_de_pago',
        'monto_pagado',
        'concatenar',
        'fecha',
        'pagado_en_soles',
        'gestor',
        'status',
    ];

    protected $casts = [
        'fecha_de_pago'   => 'date',
        'fecha'           => 'date',
        'monto_pagado'    => 'decimal:2',
        'pagado_en_soles' => 'decimal:2',
    ];
    
    public function lote() {
        return $this->belongsTo(PagoLote::class, 'lote_id');
    }
}
