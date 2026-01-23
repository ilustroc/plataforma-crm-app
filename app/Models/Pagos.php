<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagos extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'documento',
        'operacion',
        'moneda',
        'fecha',
        'monto',
        'gestor',
    ];

    protected $dates = [
        'fecha',
    ];
}