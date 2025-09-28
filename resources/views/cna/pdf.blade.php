<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style> body{ font-family: DejaVu Sans, sans-serif; font-size:13px } h1{ font-size:18px; text-align:center } table{ width:100%; border-collapse:collapse } th,td{ border:1px solid #000; padding:6px } </style>
</head>
<body>
  <h1>CONSTANCIA DE NO ADEUDO N.º {{ $cna->nro_carta }}</h1>

  <p><strong>Cliente:</strong> {{ $cna->titular ?? '—' }}<br>
     <strong>DNI/RUC:</strong> {{ $cna->dni }}</p>

  <p>El abajo firmante certifica que el cliente antes descrito, a la fecha, NO MANTIENE DEUDA VIGENTE con nuestra institución, habiendo efectuado la cancelación de la(s) operación(es) siguiente(s):</p>

  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th>Operación(es)</th>
        <th>Origen de la deuda</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $cna->producto ?? '—' }}</td>
        <td>{{ implode(', ', (array)$cna->operaciones) }}</td>
        <td>{{ $cna->nro_carta }}</td>
      </tr>
    </tbody>
  </table>

  <p style="margin-top:18px">
    <strong>Fecha pago realizado:</strong>
    {{ optional($cna->fecha_pago_realizado)->format('d/m/Y') ?? '—' }}
    &nbsp; · &nbsp;
    <strong>Monto pagado:</strong> S/ {{ number_format((float)$cna->monto_pagado, 2) }}
  </p>

  @if(($cna->observacion ?? '') !== '')
    <p><strong>Observación:</strong> {{ $cna->observacion }}</p>
  @endif

  <p style="margin-top:32px">Lima, {{ now()->locale('es')->translatedFormat('d \\de F \\de Y') }}</p>
</body>
</html>
