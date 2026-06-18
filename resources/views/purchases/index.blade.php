@extends('layouts.app')

@section('title', 'Compras')
@section('page-title', 'Órdenes y Facturas de Compra')
@section('page-subtitle', 'Registra compras a proveedores y controla las cuentas por pagar')

@section('header-actions')
    <a href="{{ route('purchases.create') }}" class="btn-primary">
        <i class="fa-solid fa-plus"></i>
        Nueva Compra
    </a>
@endsection

@section('content')
    <div class="mt-2 space-y-6 page-fade">

        {{-- Filtros --}}
        <div class="card-panel p-6">
            <form method="GET" action="{{ route('purchases.index') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="search" class="form-label">Buscar Proveedor / Documento</label>
                    <input type="text" name="search" id="search" value="{{ $search }}"
                        placeholder="Número de compra, Razón social, RUC..." class="input-solid">
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

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('purchases.index') }}" class="btn-outline justify-center">
                        <i class="fa-solid fa-xmark"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabla de Compras --}}
        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Nro Compra</th>
                            <th class="table-header">Factura Proveedor</th>
                            <th class="table-header">Fecha Emisión</th>
                            <th class="table-header">Proveedor</th>
                            <th class="table-header text-right">Total Facturado</th>
                            <th class="table-header text-right">Pagado</th>
                            <th class="table-header text-right">Saldo Pendiente</th>
                            <th class="table-header">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td class="table-cell">
                                    <a href="{{ route('purchases.show', $purchase) }}" class="font-bold transition-colors"
                                        style="color: var(--primary);">
                                        {{ $purchase->series }}-{{ $purchase->number }}
                                    </a>
                                    <span class="text-xs block mt-0.5" style="color: var(--text-tertiary);">Registrado:
                                        {{ $purchase->user->name ?? 'Sistema' }}</span>
                                </td>
                                <td class="table-cell text-xs font-mono font-bold" style="color: var(--text-secondary);">
                                    {{ $purchase->supplier_invoice ?? '—' }}</td>
                                <td class="table-cell text-xs font-mono" style="color: var(--text-tertiary);">
                                    {{ $purchase->issue_date->format('d/m/Y') }}</td>
                                <td class="table-cell">
                                    <div class="font-bold" style="color: var(--text-main);">
                                        {{ $purchase->partner->business_name }}</div>
                                    <div class="text-xs font-mono mt-0.5" style="color: var(--text-tertiary);">
                                        {{ $purchase->partner->document_type }}: {{ $purchase->partner->document_number }}
                                    </div>
                                </td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--text-main);">S/
                                    {{ number_format($purchase->total, 2) }}</td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--success);">S/
                                    {{ number_format($purchase->paid_amount, 2) }}</td>
                                <td class="table-cell text-right font-bold font-mono">
                                    @if ($purchase->pending_balance > 0 && $purchase->status !== 'ANULADO')
                                        <span class="badge badge-danger">
                                            S/ {{ number_format($purchase->pending_balance, 2) }}
                                        </span>
                                    @else
                                        <span style="color: var(--text-tertiary);">S/ 0.00</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @switch($purchase->status)
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
                                        <a href="{{ route('purchases.show', $purchase) }}"
                                            class="btn-outline py-1 px-3 justify-center gap-1.5 text-xs font-bold"
                                            style="color: var(--primary);">
                                            <i class="fa-solid fa-gear"></i>
                                            Gestionar
                                        </a>
                                        <a href="{{ route('purchases.pdf', $purchase) }}"
                                            class="btn-primary py-1 px-3 justify-center text-xs font-bold"
                                            title="Descargar PDF" style="background-color: var(--primary);">
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                                style="background-color: var(--primary-light);">
                                                <i class="fa-solid fa-cart-shopping text-xl" style="color: var(--primary);"></i>
                                            </div>
                                            <p class="text-sm font-medium" style="color: var(--text-tertiary);">
                                                No se encontraron documentos de compra.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($purchases->hasPages())
                    <div class="px-6 py-4 border-t" style="border-color: var(--border-light); background-color: var(--bg);">
                        {{ $purchases->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endsection
