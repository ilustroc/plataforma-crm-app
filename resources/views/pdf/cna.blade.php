<!doctype html><html lang="es"><meta charset="utf-8">
<title>CNA {{ $dni }}</title>
<style>
  body{font-family: DejaVu Sans, Arial, sans-serif; font-size:13px}
  h1{font-size:22px;margin:0 0 10px}
  .row{display:flex;justify-content:space-between;margin:4px 0}
  .lbl{width:180px;color:#555}
  .box{border:1px solid #999;padding:14px;border-radius:6px}
</style>
<body>
  <h1>Carta de No Adeudo (CNA)</h1>
  <div class="box">
    <div class="row"><div class="lbl">DNI:</div><div><strong>{{ $dni }}</strong></div></div>
    <div class="row"><div class="lbl">N° Carta:</div><div>{{ $nro_carta ?? '—' }}</div></div>
    <div class="row"><div class="lbl">Fecha:</div><div>{{ $fecha ?? date('Y-m-d') }}</div></div>
    <div class="row"><div class="lbl">Monto negociado:</div><div>{{ $monto_negociado ? 'S/ '.number_format($monto_negociado,2) : '—' }}</div></div>
    <div class="row"><div class="lbl">Estado:</div><div>{{ $estado ?? 'APROBADA' }}</div></div>
  </div>
  <p style="margin-top:12px">
    Se deja constancia que el titular identificado con DNI <strong>{{ $dni }}</strong>
    no mantiene adeudos pendientes con la entidad a la fecha indicada.
  </p>
  <p style="margin-top:24px">__________________________<br>Firma y sello</p>
</body></html>
