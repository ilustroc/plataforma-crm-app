<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>CNA {{ $cna->nro_carta }} - {{ $cna->dni }}</title>
  <style>
    @page { margin: 40px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; }
    h1 { font-size: 18px; text-align:center; text-transform:uppercase; margin: 0 0 30px; }
    .muted { color:#444; }
    .bold { font-weight:700; }
    .mt-32 { margin-top:32px; }
    .mt-48 { margin-top:48px; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding:8px 10px; }
    .table { border:1px solid #001b44; }
    .table th { background:#0d2f7a; color:#fff; font-weight:700; }
    .table td, .table th { border:1px solid #001b44; }
    .foot { margin-top:60px; }
  </style>
</head>
<body>

<h1>CONSTANCIA DE NO ADEUDO N° {{ $cna->nro_carta }}</h1>

<p><span class="bold">Cliente:</span> {{ $cna->titular }}</p>
<p><span class="bold">DNI/RUC:</span> {{ $cna->dni }}</p>

<div class="mt-32">
  <p>
    Quien suscribe la presente constancia en representación de
    <span class="bold">CONSORCIO DE ABOGADOS DEL PERÚ EIRL (IMPULSE GROUP)</span>,
    certifica que el cliente antes descrito, a la fecha
    <span class="bold">NO MANTIENE DEUDA VIGENTE</span> con nuestra institución;
    habiendo efectuado la cancelación de la(s) operación(es) siguiente(s):
  </p>
</div>

<div class="mt-32">
  <table class="table">
    <thead>
      <tr>
        <th style="width:35%">Producto</th>
        <th style="width:35%">Operación</th>
        <th style="width:30%">Origen de la deuda</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $cna->producto }}</td>
        <td>{{ $cna->operacion }}</td>
        <td>{{ $cna->nro_carta }}</td>
      </tr>
    </tbody>
  </table>
</div>

<p class="mt-32">
  Se expide la presente constancia a solicitud del interesado para los fines que estime conveniente.
</p>

<div class="foot">
  @php
    $months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    $d = \Carbon\Carbon::now();
    $fecha = 'Lima, '.$d->day.' de '.$months[$d->month-1].' de '.$d->year;
  @endphp
  <p class="bold">{{ $fecha }}</p>
</div>

</body>
</html>
