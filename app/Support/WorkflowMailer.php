<?php

namespace App\Support;

use App\Models\User;
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WorkflowMailer
{
    /* ======================== PROMESAS ======================== */

    public static function promesaPendiente(PromesaPago $p): void
    {
        [$asesor, $supervisor, $admins, $data] = self::promesaContext($p);

        // Supervisor
        if ($supervisor && filter_var($supervisor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $supervisor->email, [
                'banner'     => '.: CRM :. Pre-aprobación pendiente',
                'cta'        => 'Abrir Autorización',
                'label_nro'  => 'N° Propuesta',
            ] + $data, ".: CRM :. Pre-aprobación pendiente — DNI {$p->dni} - Cliente: {$data['cliente']}");
        }

        // Asesor — acuse
        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $asesor->email, [
                'banner'     => 'Tu propuesta fue ENVIADA — esperando a Supervisor',
                'cta'        => 'Ver estado',
                'label_nro'  => 'N° Propuesta',
            ] + $data, 'Propuesta enviada — esperando a Supervisor');
        }
    }

    public static function promesaPreaprobada(PromesaPago $p): void
    {
        [$asesor, $supervisor, $admins, $data] = self::promesaContext($p);

        // Administración
        foreach ($admins as $email) {
            self::send('mail.promesa', $email, [
                'banner'     => '.: CRM :. Revisión de Administración',
                'cta'        => 'Abrir Autorización',
                'label_nro'  => 'N° Propuesta',
            ] + $data, ".: CRM :. Revisión de Administración — DNI {$p->dni} - Cliente: {$data['cliente']}");
        }

        // Asesor
        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $asesor->email, [
                'banner'     => 'Tu propuesta fue PRE-APROBADA — esperando a Administración',
                'cta'        => 'Ver estado',
                'label_nro'  => 'N° Propuesta',
            ] + $data, 'Tu propuesta fue PRE-APROBADA — esperando a Administración');
        }
    }

    /** Rechazada por SUPERVISOR */
    public static function promesaRechazadaSup(PromesaPago $p, ?string $nota = null): void
    {
        [$asesor, $supervisor, $admins, $data] = self::promesaContext($p);
        $data['nota'] = trim((string)$nota);

        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $asesor->email, [
                'banner'     => 'Tu propuesta fue RECHAZADA por Supervisor',
                'cta'        => 'Ver estado',
                'label_nro'  => 'N° Propuesta',
            ] + $data, 'Tu propuesta fue RECHAZADA por Supervisor');
        }
    }

    /** Resuelta por ADMIN: aprobada/rechazada */
    public static function promesaResuelta(PromesaPago $p, bool $aprobada, ?string $nota = null): void
    {
        [$asesor, $supervisor, $admins, $data] = self::promesaContext($p);
        $data['nota'] = trim((string)$nota);
        $titulo = $aprobada ? 'APROBADA' : 'RECHAZADA por Administración';

        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $asesor->email, [
                'banner'     => 'Tu propuesta fue ' . $titulo,
                'cta'        => 'Ver estado',
                'label_nro'  => 'N° Propuesta',
            ] + $data, 'Tu propuesta fue ' . $titulo);
        }
    }

    /* ========================== CNA =========================== */

    public static function cnaPendiente(CnaSolicitud $c): void
    {
        [$asesor, $supervisor, $admins, $data] = self::cnaContext($c);

        // Supervisor
        if ($supervisor && filter_var($supervisor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.cna', $supervisor->email, [
                'banner' => '.: CRM :. Solicitud de CNA — Pre-aprobación',
                'cta'    => 'Abrir Autorización',
            ] + $data, ".: CRM :. Solicitud de CNA — DNI {$c->dni} - Cliente: {$data['cliente']}");
        }

        // Asesor — acuse
        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.cna', $asesor->email, [
                'banner' => 'Tu solicitud de CNA fue ENVIADA — esperando a Supervisor',
                'cta'    => 'Ver estado',
            ] + $data, 'CNA enviada — esperando a Supervisor');
        }
    }

    public static function cnaPreaprobada(CnaSolicitud $c): void
    {
        [$asesor, $supervisor, $admins, $data] = self::cnaContext($c);

        foreach ($admins as $email) {
            self::send('mail.cna', $email, [
                'banner' => '.: CRM :. Solicitud de CNA — Revisión de Administración',
                'cta'    => 'Abrir Autorización',
            ] + $data, ".: CRM :. CNA para revisión — DNI {$c->dni}");
        }

        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.cna', $asesor->email, [
                'banner' => 'Tu CNA fue PRE-APROBADA — esperando a Administración',
                'cta'    => 'Ver estado',
            ] + $data, 'CNA PRE-APROBADA — esperando a Administración');
        }
    }

    public static function cnaRechazadaSup(CnaSolicitud $c, ?string $nota = null): void
    {
        [$asesor, $supervisor, $admins, $data] = self::cnaContext($c);
        $data['nota'] = trim((string)$nota);

        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.cna', $asesor->email, [
                'banner' => 'Tu CNA fue RECHAZADA por Supervisor',
                'cta'    => 'Ver estado',
            ] + $data, 'CNA RECHAZADA por Supervisor');
        }
    }

    public static function cnaResuelta(CnaSolicitud $c, bool $aprobada, ?string $nota = null): void
    {
        [$asesor, $supervisor, $admins, $data] = self::cnaContext($c);
        $data['nota'] = trim((string)$nota);
        $titulo = $aprobada ? 'APROBADA' : 'RECHAZADA por Administración';

        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.cna', $asesor->email, [
                'banner' => 'Tu CNA fue ' . $titulo,
                'cta'    => 'Ver estado',
            ] + $data, 'Tu CNA fue ' . $titulo);
        }
    }

    /* ========================= Helpers ======================= */

    private static function promesaContext(PromesaPago $p): array
    {
        $asesor     = User::find($p->user_id);
        $supervisor = $asesor?->supervisor ?: User::where('role','supervisor')->first();
        $admins     = User::where('role','administrador')->pluck('email')->all();

        $cliente = $p->titular ?: (string) DB::table('clientes_cuentas')
             ->where('dni', $p->dni)->value('titular');

        $ops = $p->relationLoaded('operaciones') && $p->operaciones->count()
            ? $p->operaciones->pluck('operacion')->implode(', ')
            : (string)($p->operacion ?? '');

        $data = [
            'tipo'        => $p->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio',
            'dni'         => $p->dni,
            'cliente'     => $cliente ?: '—',
            'nro'         => $p->id,
            'operacion'   => $ops ?: '—',
            'procede'     => $supervisor?->name ?: '—',
            'link'        => route('autorizacion'),
        ];

        return [$asesor, $supervisor, $admins, $data];
    }

    private static function cnaContext(CnaSolicitud $c): array
    {
        $asesor     = User::find($c->user_id);
        $supervisor = $asesor?->supervisor ?: User::where('role','supervisor')->first();
        $admins     = User::where('role','administrador')->pluck('email')->all();

        $cliente = $c->titular ?: (string) DB::table('clientes_cuentas')
            ->where('dni', $c->dni)->value('titular');

        $ops = collect((array)($c->operaciones ?? []))->filter()->implode(', ');

        $data = [
            'dni'        => $c->dni,
            'cliente'    => $cliente ?: '—',
            'nro'        => $c->nro_carta,
            'operacion'  => $ops ?: '—',
            'procede'    => $supervisor?->name ?: '—',
            'observa'    => (string)($c->observacion ?? ''),
            'link'       => route('autorizacion') . '#cna',
        ];

        return [$asesor, $supervisor, $admins, $data];
    }

    private static function send(string $view, string $to, array $data, string $subject): void
    {
        Mail::send($view, $data, function ($m) use ($to, $subject) {
            $m->to($to)->subject($subject);
        });
    }
}
