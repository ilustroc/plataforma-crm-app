<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestionPropia extends Model
{
    protected $table = 'tabla_gestiones_propias';
    protected $fillable = [
        'lote_id','documento','cliente','nivel_3','contacto','agente','operacion',
        'entidad','equipo','fecha_gestion','fecha_cita','telefono','observacion',
        'monto_promesa','nro_cuotas','fecha_promesa','procedencia_llamada'
    ];

    protected $casts = [
        'fecha_gestion'  => 'date:Y-m-d',
        'fecha_cita'     => 'datetime:Y-m-d H:i:s',
        'fecha_promesa'  => 'date:Y-m-d',
        'monto_promesa'  => 'decimal:2',
        'nro_cuotas'     => 'integer',
    ];
}
