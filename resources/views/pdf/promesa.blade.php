<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  body{ font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#111 }
  .h1{ font-weight:700; font-size:18px; margin-bottom:2px }
  .muted{ color:#666 }
  .box{ border:1px solid #ddd; border-radius:8px; padding:12px; margin-top:10px }
  table{ width:100%; border-collapse: collapse; margin-top:8px }
  th,td{ border:1px solid #ddd; padding:6px }
  th{ background:#f3f6fb; text-transform:uppercase; font-size:11px; letter-spacing:.3px }
</style>
</head>
<body>
  <div class="h1">Propuesta de Pago</div>
  <div class="muted">Fecha: {{ now()->format('d/m/Y H:i') }}</div>

  <div class="box">
    <strong>DNI:</strong> {{ $p->dni }}<br>
    <strong>Operación:</strong> {{ $p->operacion ?: '—' }}<br>
    <strong>Fecha promesa:</strong> {{ optional($p->fecha_promesa)->format('d/m/Y') }}<br>
    <strong>Monto (S/):</strong> {{ number_format((float)$p->monto,2) }}<br>
    <strong>Estado:</strong> APROBADA
  </div>

  @if($p->nota)
  <div class="box">
    <strong>Nota:</strong><br>
    {{ $p->nota }}
  </div>
  @endif

  <div class="box">
    <table>
      <thead><tr><th>Supervisor</th><th>Fecha</th><th>Administrador</th><th>Fecha</th></tr></thead>
      <tbody>
        <tr>
          <td>{{ $p->supervisor->name ?? '—' }}</td>
          <td>{{ optional($p->pre_aprobado_at)->format('d/m/Y H:i') }}</td>
          <td>{{ $p->administrador->name ?? '—' }}</td>
          <td>{{ optional($p->aprobado_at)->format('d/m/Y H:i') }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>