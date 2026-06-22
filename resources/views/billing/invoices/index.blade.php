@extends('layouts.app')

@section('title', 'Facturas Electrónicas')
@section('page-title', 'Facturas Electrónicas')
@section('page-subtitle', 'Monitorea y descarga los comprobantes electrónicos emitidos ante el SRI.')

@section('content')
<div class="card-panel overflow-hidden page-fade">
    
    {{-- Filtros y Buscador --}}
    <div class="p-6 border-b border-light flex flex-col md:flex-row md:items-center justify-between gap-4" style="border-color: var(--border-light);">
        <form method="GET" action="{{ route('billing.invoices.index') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="w-full md:w-64">
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" value="{{ request('search') }}" class="input-solid" placeholder="Buscar por número o clave...">
                </div>
            </div>

            <div class="w-full md:w-48">
                <select name="status" class="input-solid" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador (Draft)</option>
                    <option value="signed" {{ request('status') === 'signed' ? 'selected' : '' }}>Firmado (Signed)</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Recibido SRI (Received)</option>
                    <option value="authorized" {{ request('status') === 'authorized' ? 'selected' : '' }}>Autorizado (Authorized)</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido (Failed)</option>
                </select>
            </div>

            <button type="submit" class="btn-secondary">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
            
            @if(request()->anyFilled(['search', 'status']))
                <a href="{{ route('billing.invoices.index') }}" class="btn-outline">
                    <i class="fa-solid fa-xmark"></i> Limpiar
                </a>
            @endif
        </form>
    </div>

    {{-- Tabla de Facturas --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th class="table-header">Secuencial</th>
                    <th class="table-header">Fecha de Emisión</th>
                    <th class="table-header">Asociado A</th>
                    <th class="table-header">Clave de Acceso</th>
                    <th class="table-header">Estado SRI</th>
                    <th class="table-header">Errores / Detalles</th>
                    <th class="table-header text-right">Descargas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="table-cell font-mono font-bold" style="color: var(--text-main);">
                            {{ $invoice->sequence }}
                        </td>
                        <td class="table-cell">
                            {{ $invoice->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="table-cell">
                            @if($invoice->invoicable_type === 'App\Modules\Sale\Models\Sale')
                                <a href="{{ route('sales.show', $invoice->invoicable_id) }}" class="text-primary hover:underline font-semibold">
                                    Venta #{{ $invoice->invoicable?->number ?? $invoice->invoicable_id }}
                                </a>
                            @elseif($invoice->invoicable_type === 'App\Models\SubscriptionPayment')
                                <span class="text-secondary font-semibold">
                                    Suscripción {{ strtoupper($invoice->invoicable?->plan ?? '') }}
                                </span>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="table-cell">
                            <span class="font-mono text-xs text-gray-400 select-all cursor-pointer truncate block max-w-[200px]" title="Doble clic para copiar">
                                {{ $invoice->access_key }}
                            </span>
                        </td>
                        <td class="table-cell">
                            @if($invoice->status === 'draft')
                                <span class="badge badge-draft">
                                    <i class="fa-solid fa-file-pen"></i> Borrador
                                </span>
                            @elseif($invoice->status === 'signed')
                                <span class="badge badge-info">
                                    <i class="fa-solid fa-file-signature"></i> Firmado
                                </span>
                            @elseif($invoice->status === 'received')
                                <span class="badge badge-warning">
                                    <i class="fa-solid fa-paper-plane"></i> Recibido SRI
                                </span>
                            @elseif($invoice->status === 'authorized')
                                <span class="badge badge-success">
                                    <i class="fa-solid fa-circle-check"></i> Autorizado
                                </span>
                            @elseif($invoice->status === 'failed')
                                <span class="badge badge-danger">
                                    <i class="fa-solid fa-circle-xmark"></i> Fallido
                                </span>
                            @else
                                <span class="badge badge-draft">{{ $invoice->status }}</span>
                            @endif
                        </td>
                        <td class="table-cell max-w-xs truncate" title="{{ $invoice->sri_error_details }}">
                            @if($invoice->status === 'failed')
                                <span class="text-xs text-red-500 font-medium">
                                    {{ $invoice->sri_error_details }}
                                </span>
                            @elseif($invoice->status === 'authorized')
                                <span class="text-xs text-green-500 font-medium">
                                    Autorizado el {{ $invoice->authorized_at?->format('d/m/Y H:i') }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="table-cell text-right space-x-1 whitespace-nowrap">
                            @if($invoice->xml_path)
                                <a href="{{ route('billing.invoices.xml', $invoice->id) }}" class="btn-secondary !p-2" title="Descargar XML Autorizado">
                                    <i class="fa-solid fa-file-code text-teal-600"></i> <span class="hidden md:inline">XML</span>
                                </a>
                            @else
                                <button disabled class="btn-secondary !p-2 opacity-50 cursor-not-allowed" title="XML no generado">
                                    <i class="fa-solid fa-file-code"></i> <span class="hidden md:inline">XML</span>
                                </button>
                            @endif

                            @if($invoice->pdf_path)
                                <a href="{{ route('billing.invoices.pdf', $invoice->id) }}" class="btn-secondary !p-2" title="Descargar PDF RIDE">
                                    <i class="fa-solid fa-file-pdf text-rose-600"></i> <span class="hidden md:inline">PDF</span>
                                </a>
                            @else
                                <button disabled class="btn-secondary !p-2 opacity-50 cursor-not-allowed" title="RIDE no generado">
                                    <i class="fa-solid fa-file-pdf"></i> <span class="hidden md:inline">PDF</span>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="table-cell text-center py-8 text-gray-500">
                            <i class="fa-solid fa-inbox text-3xl mb-2 block text-gray-400"></i>
                            No se encontraron comprobantes electrónicos emitidos.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if($invoices->hasPages())
        <div class="p-6 border-t border-light" style="border-color: var(--border-light);">
            {{ $invoices->links() }}
        </div>
    @endif

</div>
@endsection
