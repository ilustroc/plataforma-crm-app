<?php
// app/Models/CnaSolicitud.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CnaSolicitud extends Model
{
    protected $table = 'cna_solicitudes';
    protected $fillable = [
        'dni','operacion','nro_carta','fecha','monto_negociado',
        'workflow_estado','user_id',
        'pre_aprobado_por','pre_aprobado_at','nota_preaprobacion',
        'aprobado_por','aprobado_at','nota_aprobacion',
        'rechazado_por','rechazado_at','nota_rechazo',
    ];
    protected $casts = [
        'fecha'=>'date','pre_aprobado_at'=>'datetime','aprobado_at'=>'datetime','rechazado_at'=>'datetime',
        'monto_negociado'=>'decimal:2',
    ];
}

