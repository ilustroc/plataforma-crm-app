<?php

// app/Models/PromesaCuota.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromesaCuota extends Model
{
    protected $table = 'promesa_cuotas';
    protected $fillable = ['promesa_id','nro','fecha','monto','es_balon'];
    protected $casts = ['fecha'=>'date','monto'=>'decimal:2','es_balon'=>'boolean'];

    public function promesa() { return $this->belongsTo(PromesaPago::class, 'promesa_id'); }
}
