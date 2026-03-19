@extends('layouts.app')

@section('title', 'Cliente '.$dni)
@section('crumb', 'Clientes / Detalle')

@push('head')
    @vite(['resources/css/cliente.css', 'resources/js/cliente.js'])
@endpush

@section('content')

    @include('clientes.partials.show.header', [
        'dni' => $dni,
        'nombre' => $nombre,
        'cuentas' => $cuentas,
        'pagos' => $pagos,
    ])

    @include('clientes.partials.show.cartera', [
        'cuentas' => $cuentas,
    ])

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @include('clientes.partials.show.historial-promesas', [
            'promesas' => $promesas,
        ])

        @include('clientes.partials.show.pagos', [
            'pagos' => $pagos,
        ])
    </div>

    @include('clientes.partials.show.modals.propuesta', [
        'dni' => $dni,
    ])

    @include('clientes.partials.show.modals.cna', [
        'dni' => $dni,
    ])

@endsection