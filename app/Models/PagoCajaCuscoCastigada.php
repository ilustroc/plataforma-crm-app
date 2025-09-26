<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCajaCuscoCastigada extends Model
{
    protected $table = 'pagos_caja_cusco_castigada';

    protected $fillable = [
        'lote_id','abogado','region','agencia','titular','dni','pagare','moneda',
        'tipo_de_recuperacion','condicion','cartera','demanda','fecha_de_pago','pago_en_soles',
        'concatenar','fecha','pagado_en_soles','gestor','status',
    ];

    protected $casts = [
        'fecha_de_pago'    => 'date',
        'fecha'            => 'date',
        'pago_en_soles'    => 'decimal:2',
        'pagado_en_soles'  => 'decimal:2',
    ];

    public function lote() { return $this->belongsTo(PagoLote::class, 'lote_id'); }
}
