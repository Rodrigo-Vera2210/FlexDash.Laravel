@extends('layouts.app')

@section('title', 'Detalle de Caja Chica')
@section('page-title', 'Detalle de Sesión #' . $cashBox->id)
@section('page-subtitle', 'Consulta histórica de movimientos y cuadre final')

@section('header-actions')
    <a href="{{ route('cashbox.export', $cashBox->id) }}" class="btn-secondary" style="color: #16A34A; border-color: rgba(22,163,74,0.15);">
        <i class="fa-solid fa-file-excel"></i> Exportar Excel
    </a>
    <a href="{{ route('cashbox.history') }}" class="btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver al Historial
    </a>
@endsection

@section('content')
<div class="page-fade space-y-6">

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
        {{-- Saldo Inicial --}}
        <div class="kpi-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg bg-slate-100 dark:bg-slate-800" style="color: var(--text-secondary);">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Saldo Inicial</p>
                <h3 class="text-sm font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($openingBalance, 2) }}</h3>
            </div>
        </div>

        {{-- Ingresos --}}
        <div class="kpi-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg bg-green-50 dark:bg-green-950/20 text-green-600">
                <i class="fa-solid fa-arrow-down-long"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Ingresos</p>
                <h3 class="text-sm font-bold font-mono text-green-600">S/ {{ number_format($inflows, 2) }}</h3>
            </div>
        </div>

        {{-- Egresos --}}
        <div class="kpi-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg bg-red-50 dark:bg-red-950/20 text-red-600">
                <i class="fa-solid fa-arrow-up-long"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Egresos</p>
                <h3 class="text-sm font-bold font-mono text-red-600">S/ {{ number_format($outflows, 2) }}</h3>
            </div>
        </div>

        {{-- Saldo Esperado --}}
        <div class="kpi-card flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg bg-sky-50 dark:bg-sky-950/20" style="color: var(--primary);">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">S. Esperado</p>
                <h3 class="text-sm font-bold font-mono text-sky-600">S/ {{ number_format($expectedBalance, 2) }}</h3>
            </div>
        </div>

        {{-- Saldo Real --}}
        <div class="kpi-card flex items-center gap-3" style="border-left: 3px solid var(--primary);">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg bg-slate-100 dark:bg-slate-800" style="color: var(--primary);">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Saldo Real</p>
                <h3 class="text-sm font-bold font-mono" style="color: var(--text-main);">S/ {{ number_format($actualBalance, 2) }}</h3>
            </div>
        </div>

        {{-- Diferencia --}}
        <div class="kpi-card flex items-center gap-3" style="border-left: 3px solid {{ $difference == 0 ? '#16A34A' : ($difference > 0 ? '#16A34A' : '#DC2626') }}">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg {{ $difference >= 0 ? 'bg-green-50 dark:bg-green-950/20 text-green-600' : 'bg-red-50 dark:bg-red-950/20 text-red-600' }}">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider" style="color: var(--text-tertiary);">Diferencia</p>
                <h3 class="text-sm font-bold font-mono {{ $difference >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    @if ($difference == 0)
                        S/ 0.00
                    @elseif ($difference > 0)
                        +S/ {{ number_format($difference, 2) }}
                    @else
                        -S/ {{ number_format(abs($difference), 2) }}
                    @endif
                </h3>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Metadata Block --}}
        <div class="card-panel p-6 space-y-6 h-fit">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider" style="color: var(--text-main);">Información de Sesión</h3>
                <p class="text-xs" style="color: var(--text-tertiary);">Datos generales del registro de esta caja chica.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <span class="text-xs font-semibold" style="color: var(--text-tertiary);">Responsable de Apertura</span>
                    <p class="text-sm font-medium mt-1" style="color: var(--text-main);">
                        <i class="fa-solid fa-user-gear mr-1"></i> {{ $cashBox->user->name ?? 'Sistema' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs font-semibold" style="color: var(--text-tertiary);">Fecha Apertura</span>
                        <p class="text-xs font-mono mt-1" style="color: var(--text-secondary);">
                            <i class="fa-solid fa-calendar mr-1"></i> {{ $cashBox->opened_at ? $cashBox->opened_at->format('d/m/Y H:i') : '—' }}
                        </p>
                    </div>
                    <div>
                        <span class="text-xs font-semibold" style="color: var(--text-tertiary);">Fecha Cierre</span>
                        <p class="text-xs font-mono mt-1" style="color: var(--text-secondary);">
                            <i class="fa-solid fa-calendar-check mr-1"></i> {{ $cashBox->closed_at ? $cashBox->closed_at->format('d/m/Y H:i') : '—' }}
                        </p>
                    </div>
                </div>

                @if($cashBox->notes)
                    <hr style="border-color: var(--border-light);">
                    <div>
                        <span class="text-xs font-semibold" style="color: var(--text-tertiary);">Observaciones / Notas</span>
                        <div class="mt-2 text-xs p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border text-slate-600 dark:text-slate-400 whitespace-pre-line" style="border-color: var(--border-light);">
                            {{ $cashBox->notes }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Transactions Ledger --}}
        <div class="lg:col-span-2 card-panel overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-6 py-4 border-b" style="border-color: var(--border-light);">
                    <h3 class="text-sm font-bold uppercase tracking-wider" style="color: var(--text-main);">Historial de Movimientos de la Sesión</h3>
                </div>
                @if ($transactions->isEmpty())
                    <div class="p-8 text-center" style="color: var(--text-tertiary);">
                        No hay movimientos registrados en esta sesión.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="table-header">Fecha/Hora</th>
                                    <th class="table-header">Concepto</th>
                                    <th class="table-header">Usuario</th>
                                    <th class="table-header text-center">Tipo</th>
                                    <th class="table-header text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $tx)
                                    <tr>
                                        <td class="table-cell font-mono text-xs">
                                            {{ $tx->created_at->format('d/m H:i') }}
                                        </td>
                                        <td class="table-cell font-medium">
                                            {{ $tx->concept }}
                                            @if ($tx->payment_id)
                                                <span class="block text-xs font-mono" style="color: var(--text-tertiary);">Pago Ref #{{ $tx->payment_id }}</span>
                                            @endif
                                        </td>
                                        <td class="table-cell text-xs">
                                            {{ $tx->user->name ?? 'Usuario' }}
                                        </td>
                                        <td class="table-cell text-center">
                                            <span class="badge {{ $tx->type === 'ingreso' ? 'badge-success' : 'badge-danger' }} uppercase">
                                                {{ $tx->type }}
                                            </span>
                                        </td>
                                        <td class="table-cell text-right font-bold font-mono {{ $tx->type === 'ingreso' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $tx->type === 'ingreso' ? '+' : '-' }} S/ {{ number_format($tx->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @if ($transactions->hasPages())
                <div class="p-4 border-t" style="border-color: var(--border-light);">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
