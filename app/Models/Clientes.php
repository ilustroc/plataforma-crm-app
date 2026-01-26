<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    protected $table = 'clientes_cuentas';

    protected $fillable = [
        'cartera',
        'tipo_doc',
        'dni',
        'operacion',
        'concatenar',
        'agente',
        'nombre',
        'anio_castigo',
        'moneda',
        'entidad',
        'producto',
        'cosecha',
        'departamento',
        'zona',
        'ubicacion_geografica',
        'fecha_compra',
        'edad',
        'sexo',
        'estado_civil',
        'telf1',
        'telf2',
        'telf3',
        'saldo_capital',
        'interes',
        'deuda_total',
        'laboral',
        'vehiculos',
        'propiedades',
        'consolidado_veh_prop',
        'clasificacion',
        'score',
        'correo_electronico',
        'direccion',
        'provincia',
        'distrito',
        'porcentaje_campana', 
        'capital_descuento',
        'monto_campana' 
    ];

    protected $casts = [
        'saldo_capital' => 'decimal:2',
        'interes' => 'decimal:2',
        'deuda_total' => 'decimal:2',
        'fecha_compra' => 'date',
        'porcentaje_campana' => 'decimal:4',
        'monto_campana' => 'decimal:2',
    ];
}