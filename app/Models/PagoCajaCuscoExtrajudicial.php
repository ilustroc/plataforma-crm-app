<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCajaCuscoExtrajudicial extends Model
{
    protected $table = 'pagos_caja_cusco_extrajudicial';

    protected $fillable = [
        'lote_id',
        'region','agencia','titular','dni','pagare','moneda',
        'tipo_de_recuperacion','condicion','demanda',
        'fecha_de_pago','pagado_en_soles','monto_pagado',
        'verificacion_de_bpo','estado_final','concatenar',
        'fecha_alt','pagado_en_soles_alt',
        'gestor','status',
    ];

    protected $casts = [
        'fecha_de_pago'        => 'date',
        'fecha_alt'            => 'date',
        'pagado_en_soles'      => 'decimal:2',
        'monto_pagado'         => 'decimal:2',
        'pagado_en_soles_alt'  => 'decimal:2',
    ];
}
