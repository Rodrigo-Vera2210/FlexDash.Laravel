@extends('layouts.app')

@section('title', 'Socio Comercial — ' . $partner->business_name)
@section('page-title', 'Ficha del Socio Comercial')
@section('page-subtitle', 'Historial de transacciones y datos generales')

@section('header-actions')
    <a href="{{ route('partners.edit', $partner) }}" class="btn-outline">
        <i class="fa-solid fa-pen-to-square"></i>
        Editar Socio
    </a>
@endsection

@section('content')
    <div class="mt-2 space-y-6 page-fade">
        <div class="mb-4 flex justify-between">
            <a href="{{ route('partners.index', ['type' => $partner->type === 'proveedor' ? 'proveedor' : 'cliente']) }}"
                class="btn-outline">
                <i class="fa-solid fa-arrow-left"></i>
                Volver al listado
            </a>
        </div>

        {{-- Cuadrícula de Información --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Ficha Principal --}}
            <div class="card-panel p-6 md:col-span-2 space-y-4">
                <div class="flex items-start justify-between border-b pb-4" style="border-color: var(--border-light);">
                    <div>
                        <h2 class="text-xl font-bold" style="color: var(--text-main);">{{ $partner->business_name }}</h2>
                        @if ($partner->trade_name)
                            <p class="text-sm font-medium mt-0.5" style="color: var(--text-tertiary);">
                                {{ $partner->trade_name }}</p>
                        @endif
                    </div>
                    <div>
                        @switch($partner->type)
                            @case('cliente')
                                <span class="badge badge-info"><i class="fa-solid fa-user"></i> Cliente</span>
                            @break

                            @case('proveedor')
                                <span class="badge badge-gold"><i class="fa-solid fa-truck"></i> Proveedor</span>
                            @break

                            @case('ambos')
                                <span class="badge badge-magenta"><i class="fa-solid fa-handshake"></i> Cliente / Proveedor</span>
                            @break
                        @endswitch
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="block font-semibold" style="color: var(--text-tertiary);">Documento de Identidad</span>
                        <span class="font-mono font-bold" style="color: var(--text-main);">{{ $partner->document_type }}:
                            {{ $partner->document_number }}</span>
                    </div>
                    <div>
                        <span class="block font-semibold" style="color: var(--text-tertiary);">Límite de Crédito
                            Autorizado</span>
                        <span class="font-bold font-mono" style="color: var(--text-main);">
                            @if ($partner->credit_limit > 0)
                                S/ {{ number_format($partner->credit_limit, 2) }}
                            @else
                                <span class="text-xs font-normal" style="color: var(--text-tertiary);">Sin límite</span>
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="block font-semibold" style="color: var(--text-tertiary);">Correo Electrónico</span>
                        <span class="font-medium"
                            style="color: var(--text-secondary);">{{ $partner->email ?? 'No registrado' }}</span>
                    </div>
                    <div>
                        <span class="block font-semibold" style="color: var(--text-tertiary);">Teléfono / Celular</span>
                        <span class="font-medium"
                            style="color: var(--text-secondary);">{{ $partner->phone ?? 'No registrado' }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block font-semibold" style="color: var(--text-tertiary);">Dirección Fiscal /
                            Despacho</span>
                        <span class="font-medium" style="color: var(--text-secondary);">
                            {{ $partner->address ?? 'No registrado' }}
                            @if ($partner->city)
                                ({{ $partner->city }})
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Observaciones e Internos --}}
            <div class="card-panel p-6 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider border-b pb-2"
                    style="color: var(--text-main); border-color: var(--border-light);">Notas Internas</h3>
                @if ($partner->notes)
                    <p class="text-sm whitespace-pre-line text-justify" style="color: var(--text-secondary);">
                        {{ $partner->notes }}</p>
                @else
                    <p class="text-sm italic" style="color: var(--text-tertiary);">Sin notas ni observaciones registradas.
                    </p>
                @endif
            </div>
        </div>

        {{-- Transacciones Recientes (Ventas / Compras) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Ventas Recientes (si aplica) --}}
            @if ($partner->type === 'cliente' || $partner->type === 'ambos')
                <div class="card-panel overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between"
                        style="border-color: var(--border-light);">
                        <h3 class="font-bold text-sm" style="color: var(--text-main);">Últimas Ventas a este Cliente</h3>
                        <a href="{{ route('sales.index', ['search' => $partner->document_number]) }}"
                            class="text-xs font-bold" style="color: var(--primary);">Ver todas →</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-custom w-full">
                            <thead>
                                <tr>
                                    <th class="table-header">Número</th>
                                    <th class="table-header">Fecha</th>
                                    <th class="table-header text-right">Total</th>
                                    <th class="table-header text-right">Saldo</th>
                                    <th class="table-header">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partner->sales as $sale)
                                    <tr>
                                        <td class="table-cell">
                                            <a href="{{ route('sales.show', $sale) }}" class="font-bold transition-colors"
                                                style="color: var(--primary);">
                                                {{ $sale->number }}
                                            </a>
                                        </td>
                                        <td class="table-cell text-xs font-mono" style="color: var(--text-tertiary);">
                                            {{ $sale->issue_date->format('d/m/Y') }}</td>
                                        <td class="table-cell text-right font-bold font-mono"
                                            style="color: var(--text-main);">S/ {{ number_format($sale->total, 2) }}</td>
                                        <td class="table-cell text-right font-bold font-mono">
                                            @if ($sale->pending_balance > 0)
                                                <span class="badge badge-warning">S/
                                                    {{ number_format($sale->pending_balance, 2) }}</span>
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
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="table-cell text-center py-6"
                                                style="color: var(--text-tertiary);">Sin ventas registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Compras Recientes (si aplica) --}}
                @if ($partner->type === 'proveedor' || $partner->type === 'ambos')
                    <div class="card-panel overflow-hidden">
                        <div class="px-6 py-4 border-b flex items-center justify-between"
                            style="border-color: var(--border-light);">
                            <h3 class="font-bold text-sm" style="color: var(--text-main);">Últimas Compras a este Proveedor</h3>
                            <a href="{{ route('purchases.index', ['search' => $partner->document_number]) }}"
                                class="text-xs font-bold" style="color: var(--primary);">Ver todas →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="table-custom w-full">
                                <thead>
                                    <tr>
                                        <th class="table-header">Número</th>
                                        <th class="table-header">Fecha</th>
                                        <th class="table-header text-right">Total</th>
                                        <th class="table-header text-right">Saldo</th>
                                        <th class="table-header">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($partner->purchases as $purchase)
                                        <tr>
                                            <td class="table-cell">
                                                <a href="{{ route('purchases.show', $purchase) }}"
                                                    class="font-bold transition-colors" style="color: var(--primary);">
                                                    {{ $purchase->number }}
                                                </a>
                                            </td>
                                            <td class="table-cell text-xs font-mono" style="color: var(--text-tertiary);">
                                                {{ $purchase->issue_date->format('d/m/Y') }}</td>
                                            <td class="table-cell text-right font-bold font-mono"
                                                style="color: var(--text-main);">S/ {{ number_format($purchase->total, 2) }}
                                            </td>
                                            <td class="table-cell text-right font-bold font-mono">
                                                @if ($purchase->pending_balance > 0)
                                                    <span class="badge badge-warning">S/
                                                        {{ number_format($purchase->pending_balance, 2) }}</span>
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
                                        </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="table-cell text-center py-6"
                                                    style="color: var(--text-tertiary);">Sin compras registradas.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endsection
