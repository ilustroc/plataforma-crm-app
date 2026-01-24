<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;
use App\Models\Pagos;

class PanelController extends Controller
{
    public function index(Request $r)
    {
        $user  = Auth::user();
        $role  = strtolower($user->role ?? '');
        $isAsesor     = ($role === 'asesor');
        $isSupervisor = ($role === 'supervisor');
        $isAdmin      = ($role === 'administrador' || $role === 'sistemas');
        $hoy = Carbon::today()->toDateString();

        // Promesas creadas hoy (asesor solo ve las suyas)
        $kpiPromHoy = PromesaPago::when($isAsesor, fn($q)=>$q->where('user_id',$user->id))
                                 ->whereDate('created_at',$hoy)
                                 ->count();
        
        // Pagos de Hoy
        $kpiPagosHoy = Pagos::whereDate('fecha',$hoy)
            ->when($isAsesor, fn($q)=>$q->where(function($w) use ($user){
                $w->where('gestor',$user->name)->orWhere('equipos',$user->name);
            }))
            ->sum('monto'); 

        /* ======= Actividades por ROL ======= */
        // Supervisor/Admin: pendientes/preaprobadas para aprobar
        $ppPendCount = 0; $cnaPendCount = 0;
        $ppPend = collect(); $cnaPend = collect();

        if (!$isAsesor) {
            $ppBase = PromesaPago::query()
                ->select('id','dni','operacion','fecha_promesa','monto','monto_convenio','nota','tipo')
                ->orderByDesc('created_at')
                ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                                   fn($q)=>$q->where('workflow_estado','preaprobada'));

            $ppPendCount = (clone $ppBase)->count();
            $ppPend = (clone $ppBase)->limit(5)->get()
                        ->map(function($p){
                            $p->monto_mostrar = (float)($p->monto > 0 ? $p->monto : $p->monto_convenio);
                            return $p;
                        });

            $cnaBase = CnaSolicitud::query()
                ->orderByDesc('created_at')
                ->when($isSupervisor, fn($q)=>$q->where('workflow_estado','pendiente'),
                                   fn($q)=>$q->where('workflow_estado','preaprobada'));

            $cnaPendCount = (clone $cnaBase)->count();
            $cnaPend = (clone $cnaBase)
                ->select('id','dni','nro_carta','operaciones','observacion','created_at')
                ->limit(5)->get();
        }

        // Asesor: sus propias solicitudes por estado
        $misSup = $misPre = $misRes = $cnaSup = $cnaPre = $cnaRes = collect();
        if ($isAsesor) {
            // Promesas
            $misSup = PromesaPago::where('user_id',$user->id)
                        ->where('workflow_estado','pendiente')
                        ->latest()->limit(5)->get();

            $misPre = PromesaPago::where('user_id',$user->id)
                        ->where('workflow_estado','preaprobada')
                        ->latest()->limit(5)->get();

            $misRes = PromesaPago::where('user_id',$user->id)
                        ->whereIn('workflow_estado',['aprobada','rechazada','rechazada_sup'])
                        ->latest()->limit(5)->get();

            // CNA del asesor (si tu tabla tiene user_id)
            $cnaSup = CnaSolicitud::where('user_id',$user->id)
                        ->where('workflow_estado','pendiente')
                        ->latest()->limit(5)->get();

            $cnaPre = CnaSolicitud::where('user_id',$user->id)
                        ->where('workflow_estado','preaprobada')
                        ->latest()->limit(5)->get();

            $cnaRes = CnaSolicitud::where('user_id',$user->id)
                        ->whereIn('workflow_estado',['aprobada','rechazada','rechazada_sup'])
                        ->latest()->limit(5)->get();
        }

        /* ======= Cuotas próximos 7 días (solo sup/admin) ======= */
        $venc = collect(); $vencCount = 0;
        if (!$isAsesor && Schema::hasTable('promesa_cuotas')) {
            $desde = Carbon::today()->toDateString();
            $hasta = Carbon::today()->addDays(7)->toDateString();

            $venc = DB::table('promesa_cuotas as c')
                ->join('promesas_pago as p','p.id','=','c.promesa_id')
                ->where('p.workflow_estado','aprobada')
                ->whereBetween('c.fecha', [$desde, $hasta])
                ->select('p.dni','p.operacion','p.tipo','c.promesa_id','c.nro','c.fecha','c.monto')
                ->orderBy('c.fecha')
                ->limit(8)
                ->get();

            $vencCount = DB::table('promesa_cuotas as c')
                ->join('promesas_pago as p','p.id','=','c.promesa_id')
                ->where('p.workflow_estado','aprobada')
                ->whereBetween('c.fecha', [$desde, $hasta])
                ->count();
        }

        /* ================= Gráfico Pagos del mes ================= */
        $mes = $r->input('mes', Carbon::today()->format('Y-m'));
        try {
            $ini = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
        } catch (\Exception $e) {
            $ini = Carbon::today()->startOfMonth();
            $mes = $ini->format('Y-m');
        }
        $fin = (clone $ini)->endOfMonth();

        $chartLabels = [];
        $chartData   = [];
        $sumMes      = 0.0;

        if (Schema::hasTable('pagos')) {

            $base = Pagos::query()
                ->whereBetween('fecha', [$ini->toDateString(), $fin->toDateString()])
                ->when($isAsesor, fn($q) => $q->where('gestor', $user->name));

            $rows = (clone $base)
                ->selectRaw('DATE(fecha) as f, SUM(monto) as s')
                ->groupBy('f')
                ->orderBy('f')
                ->get()
                ->keyBy('f');

            $days = $ini->daysInMonth;

            for ($d = 1; $d <= $days; $d++) {
                $date = $ini->copy()->day($d)->toDateString();
                $chartLabels[] = str_pad($d, 2, '0', STR_PAD_LEFT);

                $val = (float)($rows[$date]->s ?? 0);
                $chartData[] = round($val, 2);
                $sumMes += $val;
            }
        }

        /* ===== Tabla de pagos ===== */
        $busq = trim($r->input('q',''));
        $propias = collect(); $propiasTotal = 0.0;

        if (Schema::hasTable('pagos')) {
            $basePropias = DB::table('pagos')
                ->whereBetween('fecha', [$ini->toDateString(), $fin->toDateString()])
                ->when($isAsesor, fn($q) => $q->where('gestor', $user->name))
                ->when($busq !== '', function($q) use ($busq){
                    $q->where(function($w) use ($busq){
                        $w->where('documento','like',"%{$busq}%")
                        ->orWhere('operacion','like',"%{$busq}%")
                        ->orWhere('moneda','like',"%{$busq}%")
                        ->orWhere('gestor','like',"%{$busq}%");
                    });
                });

            $propias = (clone $basePropias)
                ->select('documento','operacion','moneda','fecha','monto','gestor')
                ->orderByDesc('fecha')
                ->paginate(25)->withQueryString();

            $propiasTotal = (float) (clone $basePropias)->sum('monto');
        }


        return view('panel.resumen', compact(
            'role','isAsesor','isSupervisor','isAdmin',
            'kpiPromHoy','kpiPagosHoy',
            // sup/admin
            'ppPendCount','ppPend','cnaPendCount','cnaPend','venc','vencCount',
            // asesor
            'misSup','misPre','misRes','cnaSup','cnaPre','cnaRes',
            // gráfico/tabla
            'mes','chartLabels','chartData','sumMes',
            'propias','propiasTotal','busq'
        ));
    }
}
