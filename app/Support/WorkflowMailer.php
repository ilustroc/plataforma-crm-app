<?php

namespace App\Support;

use App\Mail\WorkflowMail;
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;

class WorkflowMailer
{
    /* ========= UTILIDADES ========= */

    protected static function titularPorDni(string $dni): string
    {
        return (string) DB::table('clientes_cuentas')
            ->where('dni', $dni)
            ->whereNotNull('titular')
            ->value('titular');
    }

    protected static function correosAdmins(): array
    {
        return User::where('role','administrador')->pluck('email')->filter()->values()->all();
    }

    protected static function correoSupervisorDe(User $asesor): ?string
    {
        if (!$asesor?->supervisor_id) return null;
        return User::where('id', $asesor->supervisor_id)->value('email');
    }

    protected static function enviar($to, string $subject, array $datos, ?string $url=null, ?string $cta=null): void
    {
        $to = Arr::wrap($to);
        $to = array_values(array_filter(array_unique($to)));
        if (empty($to)) return;

        Mail::to($to)->send(new WorkflowMail($subject, $datos, $url, $cta));
    }

    /* ========= PROMESAS ========= */

    // Asesor crea promesa ➜ supervisor
    public static function promesaSolicitada(PromesaPago $p, User $actor): void
    {
        $titular = $p->titular ?? self::titularPorDni($p->dni);
        $ops     = $p->relationLoaded('operaciones') && $p->operaciones->count()
            ? $p->operaciones->pluck('operacion')->implode(', ')
            : (string) $p->operacion;

        $subject = ".: CRM :. Solicitud de Aprobación - DNI {$p->dni} - Cliente: ".($titular ?: '—');

        $datos = [
            'N° Propuesta'  => $p->id,
            'Cliente'       => $titular,
            'Documento'     => $p->dni,
            'Operación(es)' => $ops,
            'Tipo'          => $p->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio',
            'Monto'         => 'S/ '.number_format((float)($p->monto > 0 ? $p->monto : $p->monto_convenio),2),
            'Procede de'    => 'CRM',
        ];

        $super = self::correoSupervisorDe($actor);
        self::enviar($super, $subject, $datos, route('autorizacion'), 'Revisar en Autorización');
    }

    // Supervisor pre-aprueba ➜ admins y asesor
    public static function promesaPreaprobada(PromesaPago $p): void
    {
        $titular = $p->titular ?? self::titularPorDni($p->dni);
        $subjectAdmin = ".: CRM :. Pre-aprobación pendiente — DNI {$p->dni} - Cliente: ".($titular ?: '—');
        $subjectAsesor = "Tu propuesta fue PRE-APROBADA — esperando a Administración";

        $datos = [
            'N° Propuesta' => $p->id,
            'Cliente'      => $titular,
            'Documento'    => $p->dni,
            'Fecha'        => optional($p->fecha_promesa)->format('Y-m-d'),
        ];

        self::enviar(self::correosAdmins(), $subjectAdmin, $datos, route('autorizacion'), 'Abrir Autorización');
        if ($p->user_id && ($asesor = User::find($p->user_id))) {
            self::enviar($asesor->email, $subjectAsesor, $datos, route('autorizacion'), 'Ver estado');
        }
    }

    // Admin aprueba/rechaza ➜ asesor
    public static function promesaResuelta(PromesaPago $p, bool $aprobada, ?string $nota=null): void
    {
        if (!$p->user_id) return;
        $asesor = User::find($p->user_id);
        if (!$asesor?->email) return;

        $titular = $p->titular ?? self::titularPorDni($p->dni);
        $subject = $aprobada
            ? "Tu propuesta fue APROBADA"
            : "Tu propuesta fue RECHAZADA";

        $datos = [
            'N° Propuesta' => $p->id,
            'Cliente'      => $titular,
            'Documento'    => $p->dni,
            'Resultado'    => $aprobada ? 'APROBADA' : 'RECHAZADA',
            'Observación'  => (string)$nota,
        ];

        self::enviar($asesor->email, $subject, $datos, route('autorizacion'), 'Ver detalle');
    }

    /* ========= CNA ========= */

    // Asesor solicita CNA ➜ supervisor
    public static function cnaSolicitada(CnaSolicitud $c, User $actor): void
    {
        $titular = $c->titular ?: self::titularPorDni($c->dni);
        $ops = implode(', ', array_values(array_filter(is_array($c->operaciones) ? $c->operaciones : (json_decode($c->operaciones ?? '[]', true) ?: []))));

        $subject = ".: CRM :. Solicitud de Aprobación Carta de no Adeudo - DNI {$c->dni} - Cliente: ".($titular ?: '—');

        $datos = [
            'N° Carta'      => $c->nro_carta ?: '—',
            'Cliente'       => $titular,
            'Documento'     => $c->dni,
            'Operación(es)' => $ops ?: '—',
            'Procede de'    => 'CRM',
        ];

        $super = self::correoSupervisorDe($actor);
        self::enviar($super, $subject, $datos, route('autorizacion'), 'Revisar en Autorización');
    }

    // Supervisor pre-aprueba CNA ➜ admins y asesor
    public static function cnaPreaprobada(CnaSolicitud $c): void
    {
        $titular = $c->titular ?: self::titularPorDni($c->dni);

        $datos = [
            'N° Carta'  => $c->nro_carta ?: '—',
            'Cliente'   => $titular,
            'Documento' => $c->dni,
            'Fecha'     => optional($c->created_at)->format('Y-m-d'),
        ];

        self::enviar(self::correosAdmins(),
            ".: CRM :. CNA PRE-APROBADA pendiente de Administración — DNI {$c->dni}",
            $datos, route('autorizacion'), 'Abrir Autorización');

        if ($c->user_id && ($asesor = User::find($c->user_id))) {
            self::enviar($asesor->email,
                "Tu solicitud de CNA fue PRE-APROBADA — esperando a Administración",
                $datos, route('autorizacion'), 'Ver estado');
        }
    }

    // Admin resuelve CNA ➜ asesor
    public static function cnaResuelta(CnaSolicitud $c, bool $aprobada, ?string $nota=null): void
    {
        if (!$c->user_id) return;
        $asesor = User::find($c->user_id);
        if (!$asesor?->email) return;

        $titular = $c->titular ?: self::titularPorDni($c->dni);

        $datos = [
            'N° Carta'   => $c->nro_carta ?: '—',
            'Cliente'    => $titular,
            'Documento'  => $c->dni,
            'Resultado'  => $aprobada ? 'APROBADA' : 'RECHAZADA',
            'Observación'=> (string)$nota,
        ];

        self::enviar($asesor->email,
            $aprobada ? "Tu CNA fue APROBADA" : "Tu CNA fue RECHAZADA",
            $datos, route('autorizacion'), 'Ver detalle');
    }
}
