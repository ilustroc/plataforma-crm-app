<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cartera extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carteras';

    protected $fillable = [
        'cuenta',
        'operacion',
        'documento',
        'nombre',
        'departamento',
        'provincia',
        'distrito',
        'direccion',
        'fecha_compra',
        'entidad',
        'cosecha',
        'fecha_castigo',
        'producto',
        'moneda',
        'saldo_capital',
        'intereses',
        'deuda_total',
    ];

    // Casts para asegurar tipos de datos correctos al usarlos en Laravel
    protected $casts = [
        'fecha_compra'  => 'date',
        'fecha_castigo' => 'date',
        'saldo_capital' => 'double',
        'intereses'     => 'double',
        'deuda_total'   => 'double',
    ];
}