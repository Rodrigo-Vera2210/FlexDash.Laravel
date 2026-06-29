@extends('layouts.app')

@section('title', 'Historial de Caja Chica')
@section('page-title', 'Historial de Caja Chica')
@section('page-subtitle', 'Consulta de sesiones anteriores y cuadres de caja')

@section('header-actions')
    <a href="{{ route('cashbox.index') }}" class="btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver a Caja Chica
    </a>
@endsection

@section('content')
<div class="page-fade space-y-6">

    {{-- Filtros de Búsqueda --}}
    <div class="card-panel p-6">
        <form method="GET" action="{{ route('cashbox.history') }}" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div>
                <label for="date_from" class="form-label">Desde</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-calendar"></i>
                    <input type="date" name="date_from" id="date_from" class="input-solid" 
                        value="{{ request('date_from') }}">
                </div>
            </div>

            <div>
                <label for="date_to" class="form-label">Hasta</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-calendar"></i>
                    <input type="date" name="date_to" id="date_to" class="input-solid" 
                        value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 justify-center py-2.5">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                @if(request()->filled('date_from') || request()->filled('date_to'))
                    <a href="{{ route('cashbox.history') }}" class="btn-secondary justify-center py-2.5">
                        <i class="fa-solid fa-xmark"></i> Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla de Sesiones Cerradas --}}
    <div class="card-panel overflow-hidden flex flex-col justify-between">
        <div>
            <div class="px-6 py-4 border-b" style="border-color: var(--border-light);">
                <h3 class="text-sm font-bold uppercase tracking-wider" style="color: var(--text-main);">Sesiones de Caja Cerradas</h3>
            </div>
            @if ($sessions->isEmpty())
                <div class="p-12 text-center space-y-3" style="color: var(--text-tertiary);">
                    <div class="text-4xl text-slate-300 dark:text-slate-600">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <p class="text-base font-medium">No se encontraron sesiones cerradas.</p>
                    <p class="text-xs">Ajuste los filtros de fecha o espere a cerrar su primera sesión activa.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="table-header">Fecha Apertura</th>
                                <th class="table-header">Fecha Cierre</th>
                                <th class="table-header">Responsable</th>
                                <th class="table-header">Local</th>
                                <th class="table-header text-right">Saldo Inicial</th>
                                <th class="table-header text-right">Saldo Esperado</th>
                                <th class="table-header text-right">Saldo Real</th>
                                <th class="table-header text-right">Diferencia</th>
                                <th class="table-header text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                <tr>
                                    <td class="table-cell font-mono text-xs">
                                        {{ $session->opened_at ? $session->opened_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td class="table-cell font-mono text-xs">
                                        {{ $session->closed_at ? $session->closed_at->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td class="table-cell text-sm">
                                        {{ $session->user->name ?? 'Sistema' }}
                                    </td>
                                    <td class="table-cell text-sm text-[color:var(--text-secondary)] font-medium">
                                        {{ $session->branch->name ?? 'N/A' }}
                                    </td>
                                    <td class="table-cell text-right font-mono text-xs font-semibold">
                                        S/ {{ number_format($session->opening_balance, 2) }}
                                    </td>
                                    <td class="table-cell text-right font-mono text-xs font-semibold" style="color: var(--text-secondary);">
                                        S/ {{ number_format($session->expected_closing_balance, 2) }}
                                    </td>
                                    <td class="table-cell text-right font-mono text-xs font-bold" style="color: var(--text-main);">
                                        S/ {{ number_format($session->actual_closing_balance, 2) }}
                                    </td>
                                    <td class="table-cell text-right font-mono text-xs font-bold">
                                        @if ($session->difference == 0)
                                            <span class="text-green-600">S/ 0.00</span>
                                        @elseif ($session->difference > 0)
                                            <span class="text-green-600">+S/ {{ number_format($session->difference, 2) }}</span>
                                        @else
                                            <span class="text-red-600">-S/ {{ number_format(abs($session->difference), 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-center">
                                        <div class="flex gap-2 justify-center">
                                            <a href="{{ route('cashbox.history.show', $session->id) }}" class="btn-icon" title="Ver Detalle">
                                                <i class="fa-solid fa-eye text-sky-600"></i>
                                            </a>
                                            <a href="{{ route('cashbox.export', $session->id) }}" class="btn-icon" title="Exportar Excel" style="color: #16A34A;">
                                                <i class="fa-solid fa-file-excel"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if ($sessions->hasPages())
            <div class="p-4 border-t" style="border-color: var(--border-light);">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
