@extends('layouts.app')
@section('title','Integración ▸ Data')

@section('content')
<h1 class="h4 mb-3">Integración ▸ Data</h1>

{{-- Avisos --}}
@if(session('ok'))   <div class="alert alert-success">{{ session('ok') }}</div>@endif
@if(session('warn')) <pre class="alert alert-warning small mb-3">{{ session('warn') }}</pre>@endif
@if($errors->any())  <div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card p-3">
  <div class="d-flex align-items-center justify-content-between">
    <div>
      <h2 class="h6 mb-1">Clientes (master)</h2>
      <div class="text-muted small">Cargar/actualizar la tabla única <code>clientes_cuentas</code>.</div>
    </div>
    <a class="btn btn-outline-secondary"
       href="{{ route('integracion.data.clientes.template') }}">
       Descargar plantilla CSV
    </a>
  </div>

  <hr>

  <form class="vstack gap-2" method="POST"
        action="{{ route('integracion.data.clientes.import') }}"
        enctype="multipart/form-data">
    @csrf
    <div>
      <label class="form-label">Archivo CSV</label>
      <input type="file" name="archivo" class="form-control" accept=".csv,text/csv" required>
      <div class="form-text">Encabezados: CARTERA, TIPO_DOC, DNI, OPERACIÓN, CONCATENAR, ...</div>
    </div>
    <button class="btn btn-danger">Subir y procesar</button>
  </form>
</div>
@endsection
