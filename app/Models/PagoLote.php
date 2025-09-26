<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoLote extends Model
{
    protected $table = 'pagos_lotes';
    protected $fillable = ['tipo','archivo','usuario_id','total_registros'];

    public function pagosPropia() {
        return $this->hasMany(PagoPropia::class, 'lote_id');
    }
}
