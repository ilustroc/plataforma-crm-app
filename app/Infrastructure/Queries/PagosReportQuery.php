<?php

namespace App\Infrastructure\Queries;

use App\Application\Reportes\Pagos\PagosReportFilters;
use App\Models\Pagos;
use Illuminate\Database\Eloquent\Builder;

class PagosReportQuery
{
    public function base(): Builder
    {
        return Pagos::query()
            ->leftJoin('carteras', 'pagos.operacion', '=', 'carteras.operacion');
    }

    public function apply(Builder $q, PagosReportFilters $f): Builder
    {
        // Buscador
        if ($f->q) {
            $val = $f->q;
            $q->where(function ($sq) use ($val) {
                $sq->where('pagos.documento', 'like', "%{$val}%")
                   ->orWhere('pagos.operacion', 'like', "%{$val}%")
                   ->orWhere('carteras.nombre', 'like', "%{$val}%");
            });
        }

        // Fechas
        if ($f->from)   $q->whereDate('pagos.fecha', '>=', $f->from);
        if ($f->to)     $q->whereDate('pagos.fecha', '<=', $f->to);

        // Gestor
        if ($f->gestor) $q->where('pagos.gestor', 'like', "%{$f->gestor}%");

        // Cartera/Entidad
        if ($f->cartera) $q->where('carteras.entidad', 'like', "%{$f->cartera}%");

        return $q;
    }

    public function selectColumns(Builder $q): Builder
    {
        return $q->select(
            'pagos.*',
            'carteras.nombre as cliente_nombre',
            'carteras.producto as cliente_producto',
            'carteras.entidad as cliente_cartera'
        );
    }
}
