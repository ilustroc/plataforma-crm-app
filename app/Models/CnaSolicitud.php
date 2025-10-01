<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CnaSolicitud extends Model
{
    protected $table = 'cna_solicitudes';

    protected $fillable = [
    'correlativo','nro_carta','dni','titular','producto',
    'operaciones','nota','observacion','fecha_pago_realizado','monto_pagado',
    'workflow_estado','user_id',
    'pre_aprobado_por','pre_aprobado_at',
    'aprobado_por','aprobado_at',
    'rechazado_por','rechazado_at','motivo_rechazo',
    'docx_path','pdf_path',
    ];

    protected $casts = [
        'operaciones'          => 'array',
        'fecha_pago_realizado' => 'date',
        'monto_pagado'         => 'decimal:2',
        'pre_aprobado_at'      => 'datetime',
        'aprobado_at'          => 'datetime',
        'rechazado_at'         => 'datetime',
    ];

    // Relaciones útiles
    public function solicitante()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
    public function preAprobadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'pre_aprobado_por');
    }
    public function aprobadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'aprobado_por');
    }
    public function rechazadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'rechazado_por');
    }
}
