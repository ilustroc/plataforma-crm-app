@extends('layouts.app')
@section('title','Panel | IMPULSE GO')
@section('content')
<div class="row g-3">
  <div class="col-12 col-md-4">
    <div class="p-3 border rounded-3 bg-white">
      <h6 class="mb-1">Buscar clientes</h6>
      <p class="text-muted mb-3">Consulta por DNI, código o titular.</p>
      <a href="#" class="btn btn-sm btn-danger">Entrar</a>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="p-3 border rounded-3 bg-white">
      <h6 class="mb-1">Gestiones</h6>
      <p class="text-muted mb-3">Carga y consulta de gestiones.</p>
      <button class="btn btn-sm btn-outline-secondary" disabled>Próximamente</button>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="p-3 border rounded-3 bg-white">
      <h6 class="mb-1">Reportes</h6>
      <p class="text-muted mb-3">KPIs y descargas.</p>
      <button class="btn btn-sm btn-outline-secondary" disabled>Próximamente</button>
    </div>
  </div>
</div>
@endsection