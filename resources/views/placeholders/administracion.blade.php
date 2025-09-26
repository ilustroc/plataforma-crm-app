@extends('layouts.app')
@section('title','Administración')
@section('crumb','Administración')

@push('head')
<style>
  /* —— Compacto para empatar el layout (escala ~90%) —— */
  h5{font-size:1.05rem; margin-bottom:.75rem}
  .card.pad{padding:10px 12px}
  .chip .s{font-size:.88rem}
  .helper{color:var(--muted); font-size:.82rem}

  /* Tablas y tipografías un poco más pequeñas */
  .table thead th{font-size:.88rem}
  .table tbody td{font-size:.92rem}
  .table> :not(caption)>*>*{padding:.55rem .75rem}

  /* Inputs: fondo de superficie y foco rojo de marca */
  .form-control,.form-select{background:var(--surface); border-color:var(--border)}
  .form-control::placeholder{color:var(--muted)}
  .form-control:focus,.form-select:focus{
    background:var(--surface);
    border-color: color-mix(in oklab, var(--brand) 52%, var(--border));
    box-shadow: 0 0 0 .25rem color-mix(in oklab, var(--brand) 22%, transparent);
  }

  /* Badge suave que funciona en light/dark */
  .badge-soft{background:color-mix(in oklab, var(--brand) 10%, transparent); color:var(--brand); border:1px solid color-mix(in oklab, var(--brand) 22%, transparent)}
  [data-theme="dark"] .badge-soft{background:color-mix(in oklab, var(--brand) 18%, transparent); color:var(--brand)}

  /* Oscuro: asegurar contraste en controles */
  [data-theme="dark"] .form-control,[data-theme="dark"] .form-select{background:var(--surface); color:var(--ink)}

  /* === Ajustes Estructura (tabla) light/dark === */
  .struct .table thead th{ color: var(--muted); }
  .struct .text-secondary{ color: var(--muted) !important; }
  .struct i{ color: var(--muted); }
  [data-theme="dark"] .struct i{ color: color-mix(in oklab, var(--ink) 80%, var(--muted)); }
  [data-theme="dark"] .struct .text-secondary{ color: color-mix(in oklab, var(--ink) 82%, var(--muted)) !important; }
  /* === Ajustes Estructura (tabla) light/dark) — mejoras === */
  .struct .table thead th{ color: var(--muted); background: color-mix(in oklab, var(--surface-2) 55%, transparent); }
  [data-theme="dark"] .struct .table thead th{ background: color-mix(in oklab, var(--surface-2) 40%, transparent); }
  [data-theme="dark"] .struct i{ color: color-mix(in oklab, var(--ink) 88%, var(--muted)); }
  [data-theme="dark"] .struct .text-secondary{ color: color-mix(in oklab, var(--ink) 88%, var(--muted)) !important; }
  .struct .table tbody tr:nth-child(odd) td{ background: color-mix(in oklab, var(--surface-2) 35%, transparent); }
  [data-theme="dark"] .struct .table tbody tr:nth-child(odd) td{ background: color-mix(in oklab, var(--surface-2) 20%, transparent); }
  .struct .table tbody tr:hover td{ background: inherit; }
  [data-theme="dark"] .struct .badge-soft{ background: color-mix(in oklab, var(--brand) 23%, transparent); border-color: color-mix(in oklab, var(--brand) 36%, transparent); }
  /* Legibilidad: nombres e iconos en body */
  .struct tbody td{ color: var(--ink); }
  .struct tbody .fw-semibold{ color: var(--ink); }
  .struct .d-flex.align-items-center span.me-2{ color: var(--ink); }
  .struct tbody td .text-secondary{ color: var(--muted) !important; }

  /* Iconos asesor */
  .struct i.bi-person-badge{ color: var(--muted); }
  [data-theme="dark"] .struct i.bi-person-badge{ color: color-mix(in oklab, var(--ink) 86%, var(--muted)); }
</style>
@endpush

@section('content')
  {{-- ALERTAS --}}
  @if(session('ok'))
    <div class="alert alert-success d-flex align-items-center" role="alert">
      <i class="bi bi-check-circle me-2"></i>
      <div>{{ session('ok') }}</div>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger d-flex align-items-center" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>
      <div>{{ $errors->first() }}</div>
    </div>
  @endif

  {{-- KPIs rápidos --}}
  <div class="row g-3 mb-1">
    <div class="col-sm-6 col-lg-3">
      <div class="chip">
        <div class="t"><i class="bi bi-person-gear"></i><span>Supervisores</span></div>
        <div class="value fw-bold fs-5">{{ $supervisores->count() }}</div>
        <div class="s">Usuarios con rol de Supervisor</div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="chip">
        <div class="t"><i class="bi bi-people"></i><span>Asesores</span></div>
        <div class="value fw-bold fs-5">{{ $supervisores->sum('asesores_count') }}</div>
        <div class="s">Total de asesores registrados</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    {{-- Crear Supervisor --}}
    <div class="col-lg-6">
      <div class="card pad">
        <h5 class="mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-person-gear"></i> <span>Crear Supervisor</span>
        </h5>
        <form method="POST" action="{{ route('administracion.supervisores.store') }}" class="vstack gap-3" autocomplete="off">
          @csrf
          <div>
            <label class="form-label">Nombre</label>
            <input name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: Ana Pérez">
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="{{ old('email') }}" required placeholder="supervisor@empresa.com">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="form-label">Contraseña</label>
            <input name="password" type="password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
            <div class="helper">Se enviará al usuario o cámbiala luego desde Administración.</div>
            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div class="d-grid d-sm-block">
            <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Crear Supervisor</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Crear Asesor --}}
    <div class="col-lg-6">
      <div class="card pad">
        <h5 class="mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-person-plus"></i> <span>Crear Asesor</span>
        </h5>
        <form method="POST" action="{{ route('administracion.asesores.store') }}" class="vstack gap-3" autocomplete="off">
          @csrf
          <div>
            <label class="form-label">Nombre</label>
            <input name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: Carlos López">
            @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" value="{{ old('email') }}" required placeholder="asesor@empresa.com">
            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="form-label">Contraseña</label>
            <input name="password" type="password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div>
            <label class="form-label">Supervisor</label>
            <select name="supervisor_id" class="form-select" required>
              <option value="">Selecciona…</option>
              @foreach($supervisores as $sup)
                <option value="{{ $sup->id }}" @selected(old('supervisor_id')==$sup->id)>
                  {{ $sup->name }} — {{ $sup->email }}
                </option>
              @endforeach
            </select>
            @error('supervisor_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
          </div>
          <div class="d-grid d-sm-block">
            <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Crear Asesor</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Estructura: Supervisores y Asesores --}}
  <div class="card pad mt-3 struct">
    <h5 class="mb-3 d-flex align-items-center gap-2">
      <i class="bi bi-diagram-3"></i> <span>Estructura</span>
    </h5>

    @if($supervisores->isEmpty())
      <div class="text-secondary">Aún no hay supervisores creados.</div>
    @else
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Supervisor</th>
              <th>Email</th>
              <th class="text-center"># Asesores</th>
              <th>Asesores (reasignables)</th>
            </tr>
          </thead>
          <tbody>
            @foreach($supervisores as $sup)
              <tr>
                <td class="fw-semibold">{{ $sup->name }}</td>
                <td class="text-secondary">{{ $sup->email }}</td>
                <td class="text-center">
                  <span class="badge badge-soft" style="border-radius:20px">{{ $sup->asesores_count }}</span>
                </td>
                <td>
                  @if($sup->asesores->isEmpty())
                    <span class="text-secondary">—</span>
                  @else
                    <div class="vstack gap-2">
                      @foreach($sup->asesores as $asesor)
                        <div class="d-flex flex-wrap align-items-center gap-2 py-1">
                          <i class="bi bi-person-badge text-secondary"></i>
                          <span class="me-2">{{ $asesor->name }} <span class="text-secondary">({{ $asesor->email }})</span></span>
                          {{-- Reasignación en línea --}}
                          <form method="POST" action="{{ route('administracion.asesores.reassign', $asesor->id) }}" class="d-flex gap-2 ms-auto">
                            @csrf @method('PATCH')
                            <select name="supervisor_id" class="form-select form-select-sm" style="width:auto; min-width: 180px">
                              @foreach($todosSupervisores as $sid => $sname)
                                <option value="{{ $sid }}" @selected($asesor->supervisor_id == $sid)>{{ $sname }}</option>
                              @endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-primary" title="Reasignar" onclick="return confirm('¿Reasignar a este asesor?')">
                              <i class="bi bi-arrow-repeat"></i><span class="d-none d-md-inline ms-1">Reasignar</span>
                            </button>
                          </form>
                        </div>
                      @endforeach
                    </div>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection