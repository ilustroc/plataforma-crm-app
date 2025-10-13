<?php

namespace App\Support;

use App\Models\User;
use App\Models\PromesaPago;
use App\Models\CnaSolicitud;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WorkflowMailer
{
    /* ========================= PROMESAS ========================= */

    /** Al crear (pendiente): supervisor + asesor (acuso). */
    public static function promesaPendiente(PromesaPago $p): void
    {
        [$asesor, $supervisor, $admins, $data] = self::promesaContext($p);

        // → Supervisor: Pre-aprobación pendiente
        if ($supervisor && filter_var($supervisor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $supervisor->email, [
                'banner' => '.: CRM :. Pre-aprobación pendiente',
                'cta'    => 'Abrir Autorización',
            ] + $data, ".: CRM :. Pre-aprobación pendiente — DNI {$p->dni} - Cliente: {$data['cliente']}");
        }

        // → Asesor: acuse “enviado — esperando a Supervisor”
        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $asesor->email, [
                'banner' => 'Tu propuesta fue ENVIADA — esperando a Supervisor',
                'cta'    => 'Ver estado',
            ] + $data, 'Propuesta enviada — esperando a Supervisor');
        }
    }

    /** Al pre-aprobar (supervisor): admins + asesor. */
    public static function promesaPreaprobada(PromesaPago $p): void
    {
        [$asesor, $supervisor, $admins, $data] = self::promesaContext($p);

        // → Administración
        foreach ($admins as $email) {
            self::send('mail.promesa', $email, [
                'banner' => '.: CRM :. Revisión de Administración',
                'cta'    => 'Abrir Autorización',
            ] + $data, ".: CRM :. Revisión de Administración — DNI {$p->dni} - Cliente: {$data['cliente']}");
        }

        // → Asesor
        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $asesor->email, [
                'banner' => 'Tu propuesta fue PRE-APROBADA — esperando a Administración',
                'cta'    => 'Ver estado',
            ] + $data, 'Tu propuesta fue PRE-APROBADA — esperando a Administración');
        }
    }

    /** Al resolver en Administración: asesor (+ cc supervisor opcional). */
    public static function promesaResuelta(PromesaPago $p, bool $aprobada, ?string $nota = null): void
    {
        [$asesor, $supervisor, $admins, $data] = self::promesaContext($p);
        $data['nota'] = trim((string)$nota);

        $titulo = $aprobada ? 'APROBADA' : 'RECHAZADA por Administración';
        $banner = 'Tu propuesta fue ' . $titulo;

        if ($asesor && filter_var($asesor->email, FILTER_VALIDATE_EMAIL)) {
            self::send('mail.promesa', $asesor->email, [
                'banner' => $banner, 'cta' => 'Ver estado',
            ] + $data, $banner);
        }
        // si quieres copiar al supervisor:
        // if ($supervisor && filter_var($supervisor->email, FILTER_VALIDATE_EMAIL)) {
        //     self::send('mail.promesa', $supervisor->email, [
        //         'banner' => $banner, 'cta' => 'Ver estado',
        //     ] + $data, '[Copia] ' . $banner);
        // }
    }

    /* ========================= CNA (opcional, mismo patrón) ========================= */
    // public static function cnaPendiente(CnaSolicitud $c) { ... }
    // public static function cnaPreaprobada(CnaSolicitud $c) { ... }
    // public static function cnaResuelta(CnaSolicitud $c, bool $aprobada) { ... }

    /* ========================= Helpers ========================= */

    private static function promesaContext(PromesaPago $p): array
    {
        $asesor      = User::find($p->user_id);
        $supervisor  = $asesor?->supervisor ?: User::where('role','supervisor')->first();
        $admins      = User::where('role','administrador')->pluck('email')->all();

        // cliente y operaciones
        $cliente = $p->titular ?: (string) DB::table('clientes_cuentas')
            ->where('dni', $p->dni)->value('titular');

        $ops = $p->relationLoaded('operaciones') && $p->operaciones->count()
            ? $p->operaciones->pluck('operacion')->implode(', ')
            : (string)($p->operacion ?? '');

        $data = [
            'tipo'        => $p->tipo === 'cancelacion' ? 'Cancelación' : 'Convenio',
            'dni'         => $p->dni,
            'cliente'     => $cliente ?: '—',
            'nro'         => $p->id,                  // “N° Propuesta”
            'operacion'   => $ops ?: '—',
            'procede'     => $supervisor?->name ?: '—', // 👈 Procede de: supervisor asignado
            'link'        => route('autorizacion'),
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
