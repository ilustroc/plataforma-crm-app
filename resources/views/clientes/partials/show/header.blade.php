@php
    $totCapital = (float) $cuentas->sum('saldo_capital');
    $totDeuda   = (float) $cuentas->sum('deuda_total');
    $totPagos   = (float) $pagos->sum('monto');
@endphp

<div class="client-header">

    <div class="header-profile">
        <div class="header-avatar bg-brand/10 text-brand border-brand/20">
            {{ mb_substr($nombre, 0, 1) }}
        </div>

        <div class="header-info">
            <h1 class="text-xl font-bold text-slate-800">{{ $nombre }}</h1>

            <div class="header-meta flex flex-wrap gap-x-4 gap-y-1 mt-1">
                <button
                    type="button"
                    class="flex items-center gap-1.5 hover:text-brand transition-colors group"
                    data-copy="{{ $dni }}"
                    title="Copiar DNI"
                >
                    <svg class="h-4 w-4 text-slate-400 group-hover:text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .884-.896 1.79-2.25 2.025m4.5 0c1.354-.235 2.25-1.14 2.25-2.025"/>
                    </svg>
                    <span class="font-mono font-medium text-slate-600">{{ $dni }}</span>
                </button>

                @if($cuentas->isNotEmpty() && $cuentas->first()->departamento)
                    <span class="text-slate-300">|</span>

                    <div class="flex items-center gap-1 text-slate-500">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-xs uppercase">
                            {{ $cuentas->first()->departamento }} - {{ $cuentas->first()->provincia }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="header-stats">
        <div class="stat-item">
            <span class="stat-label">Saldo Capital</span>
            <span class="stat-value text-slate-700">S/ {{ number_format($totCapital, 2) }}</span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Deuda Total</span>
            <span class="stat-value text-rose-600">S/ {{ number_format($totDeuda, 2) }}</span>
        </div>

        <div class="stat-item pl-6 border-l border-slate-100">
            <span class="stat-label">Recuperado</span>
            <span class="stat-value text-emerald-600">S/ {{ number_format($totPagos, 2) }}</span>
        </div>
    </div>
</div>