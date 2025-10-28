<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GestionLote extends Model
{
    protected $table = 'gestion_lotes';
    protected $fillable = ['tipo','archivo','usuario_id','total_registros'];
}
