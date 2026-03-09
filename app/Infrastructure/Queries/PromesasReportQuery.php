<?php

namespace App\Infrastructure\Queries;

use App\Application\Reportes\Promesas\PromesasReportFilters;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PromesasReportQuery
{
    public function __construct(
        private readonly string $table = 'promesas_pago',
        private readonly string $opsTable = 'promesa_operaciones',
    ) {}

    public function base(): Builder
    {
        return DB::table("{$this->table} as p")
            ->leftJoin("{$this->opsTable} as po", 'po.promesa_id', '=', 'p.id')
            ->leftJoin('carteras as c', DB::raw('COALESCE(po.operacion, p.operacion)'), '=', 'c.operacion')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('users as su', 'su.id', '=', 'u.supervisor_id');
    }

    public function selectColumns(Builder $qb): Builder
    {
        return $qb->selectRaw("
            COALESCE(p.dni, c.documento)                                      as documento,
            COALESCE(c.nombre, '')                                            as cliente,
            COALESCE(u.name, '')                                              as agente,
            COALESCE(po.operacion, p.operacion, '')                           as operacion,
            COALESCE(c.entidad, '')                                           as entidad,
            DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s')                    as fecha_gestion,
            COALESCE(p.nota, '')                                              as observacion,
            CASE
                WHEN p.monto > 0 THEN p.monto
                WHEN p.tipo = 'convenio' THEN p.monto_convenio
                ELSE NULL
            END                                                               AS monto_promesa,
            CASE WHEN IFNULL(p.nro_cuotas,0) > 0 THEN p.nro_cuotas ELSE NULL END as nro_cuotas,
            DATE_FORMAT(COALESCE(p.fecha_pago, p.fecha_promesa), '%Y-%m-%d')  as fecha_promesa,
            COALESCE(su.name, '')                                             as gestor,
            p.created_at                                                      as p_created_at
        ");
    }

    public function apply(Builder $qb, PromesasReportFilters $f): Builder
    {
        if ($f->from) $qb->whereDate('p.created_at', '>=', $f->from);
        if ($f->to)   $qb->whereDate('p.created_at', '<=', $f->to);

        // Estado (solo si existe columna)
        if ($f->estado !== '' && Schema::hasColumn($this->table, 'workflow_estado')) {
            $qb->where('p.workflow_estado', 'like', "%{$f->estado}%");
        }

        if ($f->gestor !== '') {
            $qb->where('su.name', 'like', "%{$f->gestor}%");
        }

        if ($f->q !== '') {
            $q = $f->q;
            $qb->where(function ($w) use ($q) {
                $w->where('p.dni', 'like', "%{$q}%")
                  ->orWhere(DB::raw('COALESCE(po.operacion, p.operacion)'), 'like', "%{$q}%")
                  ->orWhere('c.nombre', 'like', "%{$q}%")
                  ->orWhere('p.nota', 'like', "%{$q}%");
            });
        }

        return $qb;
    }

    public function ordered(Builder $qb): Builder
    {
        return $qb->orderByDesc('p.created_at');
    }
}
