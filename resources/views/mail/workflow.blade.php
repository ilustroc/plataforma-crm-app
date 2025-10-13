<!doctype html>
<html lang="es">
  <body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:#0f172a">
    <div style="max-width:640px;margin:24px auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden">
      <div style="background:#0f172a;color:#fff;padding:12px 16px;font-weight:700">
        {{ $titulo }}
      </div>
      <div style="padding:16px">
        <p>Estimado(a) usuario,</p>
        <p>Se requiere su atención para el siguiente caso:</p>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
          @foreach($datos as $k => $v)
            <tr>
              <td style="width:40%;padding:6px 8px;color:#6b7280">{{ $k }}</td>
              <td style="padding:6px 8px">{{ $v ?: '—' }}</td>
            </tr>
          @endforeach
        </table>

        @if($actionUrl)
          <p style="margin-top:16px">
            <a href="{{ $actionUrl }}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px">
              {{ $actionText ?? 'Abrir en plataforma' }}
            </a>
          </p>
        @endif

        <p style="color:#6b7280;font-size:12px;margin-top:18px">
          Inteligencia de Negocios — Plataforma CRM
        </p>
      </div>
    </div>
  </body>
</html>
