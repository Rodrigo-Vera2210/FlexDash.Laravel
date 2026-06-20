@extends('layouts.app')

@section('title', 'Ventas')
@section('page-title', 'Facturación de Ventas')
@section('page-subtitle', 'Administra comprobantes de venta, aprobaciones y cobros de clientes')

@section('header-actions')
    <a href="{{ route('sales.create') }}" class="btn-primary">
        <i class="fa-solid fa-plus"></i>
        Nueva Venta
    </a>
@endsection

@section('content')
    <div class="mt-2 space-y-6 page-fade">

        {{-- Filtros --}}
        <div class="card-panel p-6">
            <form method="GET" action="{{ route('sales.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="search" class="form-label">Buscar Cliente / Documento</label>
                    <input type="text" name="search" id="search" value="{{ $search }}"
                        placeholder="Número de factura o Razón social..." class="input-solid">
                </div>

                <div>
                    <label for="status" class="form-label">Estado</label>
                    <select name="status" id="status" class="input-solid">
                        <option value="">Todos los estados</option>
                        <option value="BORRADOR" {{ $status === 'BORRADOR' ? 'selected' : '' }}>Borrador</option>
                        <option value="APROBADO" {{ $status === 'APROBADO' ? 'selected' : '' }}>Aprobado</option>
                        <option value="PAGADO" {{ $status === 'PAGADO' ? 'selected' : '' }}>Pagado</option>
                        <option value="ANULADO" {{ $status === 'ANULADO' ? 'selected' : '' }}>Anulado</option>
                    </select>
                </div>

                <div>
                    <label for="date_from" class="form-label">Desde</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" class="input-solid">
                </div>

                <div>
                    <label for="date_to" class="form-label">Hasta</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" class="input-solid">
                </div>

                <div class="md:col-span-5 flex justify-end gap-2">
                    <a href="{{ route('sales.index') }}" class="btn-outline">
                        <i class="fa-solid fa-xmark"></i> Limpiar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-filter"></i> Filtrar Ventas
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabla de Ventas --}}
        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Nro Documento</th>
                            <th class="table-header">Fecha Emisión</th>
                            <th class="table-header">Cliente</th>
                            <th class="table-header text-right">Total Facturado</th>
                            <th class="table-header text-right">Cobrado</th>
                            <th class="table-header text-right">Saldo Pendiente</th>
                            <th class="table-header">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td class="table-cell">
                                    <a href="{{ route('sales.show', $sale) }}" class="font-bold transition-colors"
                                        style="color: var(--primary);">
                                        {{ $sale->series }}-{{ $sale->number }}
                                    </a>
                                    <span class="text-xs block mt-0.5" style="color: var(--text-tertiary);">Vendedor:
                                        {{ $sale->user->name ?? 'Sistema' }}</span>
                                </td>
                                <td class="table-cell text-xs font-mono" style="color: var(--text-tertiary);">
                                    {{ $sale->issue_date->format('d/m/Y') }}</td>
                                <td class="table-cell">
                                    <div class="font-bold" style="color: var(--text-main);">
                                        {{ $sale->partner->business_name }}</div>
                                    <div class="text-xs font-mono mt-0.5" style="color: var(--text-tertiary);">
                                        {{ $sale->partner->document_type }}: {{ $sale->partner->document_number }}</div>
                                </td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--text-main);">S/
                                    {{ number_format($sale->total, 2) }}</td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--success);">S/
                                    {{ number_format($sale->paid_amount, 2) }}</td>
                                <td class="table-cell text-right font-bold font-mono">
                                    @if ($sale->pending_balance > 0 && $sale->status !== 'ANULADO')
                                        <span class="badge badge-warning">
                                            S/ {{ number_format($sale->pending_balance, 2) }}
                                        </span>
                                    @else
                                        <span style="color: var(--text-tertiary);">S/ 0.00</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @switch($sale->status)
                                        @case('BORRADOR')
                                            <span class="badge badge-info">Borrador</span>
                                        @break

                                        @case('APROBADO')
                                            <span class="badge badge-success">Aprobado</span>
                                        @break

                                        @case('PAGADO')
                                            <span class="badge badge-success">Pagado</span>
                                        @break

                                        @case('ANULADO')
                                            <span class="badge badge-danger">Anulado</span>
                                        @break
                                    @endswitch
                                </td>
                                <td class="table-cell text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('sales.show', $sale) }}"
                                            class="btn-outline py-1 px-3 justify-center gap-1.5 text-xs font-bold"
                                            style="color: var(--primary);">
                                            <i class="fa-solid fa-gear"></i>
                                            Gestionar
                                        </a>
                                        <a href="{{ route('sales.pdf', $sale) }}"
                                            class="btn-primary py-1 px-3 justify-center text-xs font-bold"
                                            title="Descargar PDF" style="background-color: var(--primary);">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                                style="background-color: var(--primary-light);">
                                                <i class="fa-solid fa-receipt text-xl" style="color: var(--primary);"></i>
                                            </div>
                                            <p class="text-sm font-medium" style="color: var(--text-tertiary);">
                                                No se encontraron documentos de venta.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($sales->hasPages())
                    <div class="px-6 py-4 border-t" style="border-color: var(--border-light); background-color: var(--bg);">
                        {{ $sales->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endsection
