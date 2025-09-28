<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CnaSolicitud extends Model
{
    protected $table = 'cna_solicitudes';

    protected $fillable = [
        'nro_carta','dni','titular','producto','operaciones','nota',
        'workflow_estado','user_id',
        'pre_aprobado_por','pre_aprobado_at',
        'aprobado_por','aprobado_at',
        'rechazado_por','rechazado_at','motivo_rechazo',
        'docx_path',
    ];

    protected $casts = [
        'operaciones'     => 'array',
        'pre_aprobado_at' => 'datetime',
        'aprobado_at'     => 'datetime',
        'rechazado_at'    => 'datetime',
    ];
}
