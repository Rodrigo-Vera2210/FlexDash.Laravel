@extends('layouts.app')

@section('title', 'Venta ' . $sale->series . '-' . $sale->number)
@section('page-title', 'Detalle de Comprobante de Venta')
@section('page-subtitle', 'Consulta de estados, registro de pagos e historial del documento')

@section('content')
    <div class="mt-2 space-y-6 page-fade">

        {{-- Barra de Navegación y Acciones --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('sales.index') }}" class="btn-outline">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver al listado
                </a>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('sales.pdf', $sale) }}" class="btn-secondary">
                    <i class="fa-solid fa-file-pdf"></i>
                    Descargar PDF
                </a>
                @if ($sale->status === 'BORRADOR')
                    {{-- Botones para Borrador --}}
                    <form method="POST" action="{{ route('sales.approve', $sale) }}" class="inline"
                        onsubmit="return confirm('¿Aprobar esta venta? Se descontará stock de inventario y el documento será inmutable.')">
                        @csrf
                        <button type="submit" class="btn-primary" style="background-color: var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                            Aprobar Venta
                        </button>
                    </form>

                    <button onclick="openCancelModal()" class="btn-primary" style="background-color: var(--danger);">
                        <i class="fa-solid fa-ban"></i>
                        Anular Venta
                    </button>
                @endif

                @if ($sale->status === 'APROBADO')
                    {{-- Botones para Aprobado --}}
                    <button onclick="openPaymentModal()" class="btn-primary">
                        <i class="fa-solid fa-cash-register"></i>
                        Registrar Pago
                    </button>

                    <button onclick="openCancelModal()" class="btn-primary" style="background-color: var(--danger);">
                        <i class="fa-solid fa-ban"></i>
                        Anular Venta
                    </button>
                @endif
            </div>
        </div>

        {{-- Datos Principales de Factura --}}
        <div class="card-panel p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Info ERP y Nro --}}
                <div class="space-y-2 md:border-r pr-6" style="border-color: var(--border-light);">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded flex items-center justify-center text-white text-xs font-bold"
                            style="background-color: var(--primary);">F</div>
                        <span class="font-bold text-sm" style="color: var(--text-main);">FlexDash ERP</span>
                    </div>
                    <div class="pt-2">
                        <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Tipo
                            Documento</span>
                        <span class="font-bold" style="color: var(--text-main);">FACTURA ELECTRÓNICA</span>
                    </div>
                    <div>
                        <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Número</span>
                        <span class="font-mono text-lg font-bold"
                            style="color: var(--primary);">{{ $sale->series }}-{{ $sale->number }}</span>
                    </div>
                </div>

                {{-- Fechas y Estado --}}
                <div class="space-y-2 md:border-r pr-6" style="border-color: var(--border-light);">
                    <div>
                        <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Fecha
                            Emisión</span>
                        <span class="font-bold"
                            style="color: var(--text-secondary);">{{ $sale->issue_date->format('d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="text-xs block uppercase font-bold"
                            style="color: var(--text-tertiary);">Vencimiento</span>
                        <span class="font-bold"
                            style="color: var(--text-secondary);">{{ $sale->due_date ? $sale->due_date->format('d/m/Y') : 'Al contado' }}</span>
                    </div>
                    <div>
                        <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Estado de
                            Comprobante</span>
                        @switch($sale->status)
                            @case('BORRADOR')
                                <span class="badge badge-info"><i class="fa-solid fa-file-lines"></i> Borrador (Sin Afectar
                                    Stock)</span>
                            @break

                            @case('APROBADO')
                                <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Aprobado (Saldo
                                    Pendiente)</span>
                            @break

                            @case('PAGADO')
                                <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Pagado Total</span>
                            @break

                            @case('ANULADO')
                                <span class="badge badge-danger"><i class="fa-solid fa-circle-xmark"></i> Anulado (Liberado)</span>
                            @break
                        @endswitch
                    </div>
                </div>

                {{-- Info Cliente --}}
                <div class="space-y-2">
                    <span class="text-xs block uppercase font-bold" style="color: var(--text-tertiary);">Datos del
                        Cliente</span>
                    <div class="font-bold" style="color: var(--text-main);">{{ $sale->partner->business_name }}</div>
                    <div class="text-xs font-mono" style="color: var(--text-tertiary);">{{ $sale->partner->document_type }}:
                        {{ $sale->partner->document_number }}</div>
                    @if ($sale->partner->phone)
                        <div class="text-xs" style="color: var(--text-secondary);">Tel: {{ $sale->partner->phone }}</div>
                    @endif
                    @if ($sale->partner->address)
                        <div class="text-xs" style="color: var(--text-tertiary);">{{ $sale->partner->address }}
                            {{ $sale->partner->city ? '(' . $sale->partner->city . ')' : '' }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detalle de Artículos --}}
        <div class="card-panel overflow-hidden">
            <div class="px-6 py-4 border-b bg-slate-50"
                style="border-color: var(--border-light); background-color: var(--bg);">
                <h3 class="font-bold text-xs uppercase tracking-wider" style="color: var(--text-main);">Detalle del
                    Comprobante</h3>
            </div>
            <table class="table-custom w-full">
                <thead>
                    <tr>
                        <th class="table-header">Código</th>
                        <th class="table-header">Descripción Producto</th>
                        <th class="table-header text-right">Cantidad</th>
                        <th class="table-header text-right">Precio Unitario</th>
                        <th class="table-header text-right">Desc. Línea</th>
                        <th class="table-header text-right">Total Línea</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->details as $detail)
                        <tr>
                            <td class="table-cell font-mono text-xs" style="color: var(--text-tertiary);">
                                {{ $detail->product->code }}</td>
                            <td class="table-cell">
                                <a href="{{ route('products.show', $detail->product_id) }}"
                                    class="font-bold transition-colors" style="color: var(--text-main);"
                                    onmouseover="this.style.color='var(--primary)'"
                                    onmouseout="this.style.color='var(--text-main)'">
                                    {{ $detail->product->name }}
                                </a>
                            </td>
                            <td class="table-cell text-right font-bold font-mono" style="color: var(--text-secondary);">
                                {{ number_format($detail->quantity, 2) }} {{ $detail->product->unit }}</td>
                            <td class="table-cell text-right font-semibold font-mono" style="color: var(--text-secondary);">
                                S/ {{ number_format($detail->unit_price, 2) }}</td>
                            <td class="table-cell text-right font-bold font-mono" style="color: var(--danger);">
                                @if ($detail->discount > 0)
                                    -S/ {{ number_format($detail->discount, 2) }}
                                @else
                                    <span style="color: var(--text-tertiary);">—</span>
                                @endif
                            </td>
                            <td class="table-cell text-right font-bold font-mono" style="color: var(--text-main);">S/
                                {{ number_format($detail->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totales --}}
            <div class="border-t p-6 flex justify-end"
                style="border-color: var(--border-light); background-color: var(--bg);">
                <div class="w-80 space-y-2 text-sm" style="color: var(--text-secondary);">
                    <div class="flex justify-between">
                        <span>Subtotal Neto:</span>
                        <span class="font-bold font-mono" style="color: var(--text-main);">S/
                            {{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Impuesto ({{ $sale->tax->name ?? 'Exento' }}
                            {{ $sale->tax ? number_format($sale->tax->rate, 0) . '%' : '' }}):</span>
                        <span class="font-bold font-mono" style="color: var(--text-main);">S/
                            {{ number_format($sale->tax_amount, 2) }}</span>
                    </div>
                    @if ($sale->discount > 0)
                        <div class="flex justify-between">
                            <span>Descuento global:</span>
                            <span class="font-bold font-mono" style="color: var(--danger);">-S/
                                {{ number_format($sale->discount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t pt-2 text-base font-bold"
                        style="border-color: var(--border-light);">
                        <span style="color: var(--text-main);">Total General:</span>
                        <span class="font-mono">S/ {{ number_format($sale->total, 2) }}</span>
                    </div>

                    <div class="border-t pt-2 space-y-1.5 text-xs" style="border-color: var(--border-light);">
                        <div class="flex justify-between" style="color: var(--success);">
                            <span>Monto Cobrado:</span>
                            <span class="font-bold font-mono">S/ {{ number_format($sale->paid_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between {{ $sale->pending_balance > 0 ? 'font-bold' : '' }}"
                            style="color: {{ $sale->pending_balance > 0 ? 'var(--warning)' : 'var(--text-tertiary)' }}">
                            <span>Saldo Pendiente:</span>
                            <span class="font-mono">S/ {{ number_format($sale->pending_balance, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sección de Pagos --}}
        <div class="card-panel overflow-hidden">
            <div class="px-6 py-4 border-b bg-slate-50 flex items-center justify-between"
                style="border-color: var(--border-light); background-color: var(--bg);">
                <h3 class="font-bold text-xs uppercase tracking-wider" style="color: var(--text-main);">Historial de
                    Cobros</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Fecha Pago</th>
                            <th class="table-header">Método Pago</th>
                            <th class="table-header">Referencia</th>
                            <th class="table-header">Registrado Por</th>
                            <th class="table-header text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sale->payments as $pay)
                            <tr>
                                <td class="table-cell font-mono text-xs" style="color: var(--text-tertiary);">
                                    {{ $pay->payment_date->format('d/m/Y') }}</td>
                                <td class="table-cell font-bold" style="color: var(--text-secondary);">
                                    {{ $pay->paymentMethod->name }}</td>
                                <td class="table-cell text-xs font-mono" style="color: var(--text-tertiary);">
                                    {{ $pay->reference ?? '—' }}</td>
                                <td class="table-cell" style="color: var(--text-secondary);">
                                    {{ $pay->user->name ?? 'Sistema' }}</td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--success);">S/
                                    {{ number_format($pay->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="table-cell text-center" style="color: var(--text-tertiary);">
                                    Ningún cobro registrado en esta factura.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL REGISTRAR PAGO --}}
    <div id="payment-modal" class="fixed inset-0 hidden flex items-center justify-center z-50"
        style="background-color: rgba(13,30,54,0.65); backdrop-filter: blur(4px);"
        onclick="if(event.target===this) closePaymentModal()">
        <div class="card-panel w-full max-w-md overflow-hidden p-0" style="border-radius: 20px;">
            <div class="flex items-center justify-between px-6 py-5 border-b" style="border-color: var(--border-light);">
                <h3 class="text-base font-bold" style="color: var(--text-main);">Registrar Cobro de Cliente</h3>
                <button onclick="closePaymentModal()" class="btn-icon">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @php
                $paymentMethods = \App\Models\PaymentMethod::where('is_active', true)->get();
            @endphp

            <form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label for="payment_date" class="form-label">Fecha de Cobro</label>
                    <input type="date" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}"
                        class="input-solid" required>
                </div>

                <div>
                    <label for="payment_method_id" class="form-label">Medio de Pago</label>
                    <select name="payment_method_id" id="payment_method_id" class="input-solid" required>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="amount" class="form-label">Monto a Cobrar (S/)</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0.01"
                        max="{{ $sale->pending_balance }}" value="{{ $sale->pending_balance }}"
                        class="input-solid font-bold" required>
                    <p class="text-xs mt-1" style="color: var(--text-tertiary);">Saldo pendiente máximo: S/
                        {{ number_format($sale->pending_balance, 2) }}</p>
                </div>

                <div>
                    <label for="reference" class="form-label">Referencia / Código Operación</label>
                    <input type="text" name="reference" id="reference" class="input-solid"
                        placeholder="Ej: Transf. Nro 12938">
                </div>

                <div class="border-t pt-4 flex justify-end gap-2" style="border-color: var(--border-light);">
                    <button type="button" onclick="closePaymentModal()" class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background-color: var(--success);">Guardar
                        Cobro</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ANULAR DOCUMENTO --}}
    <div id="cancel-modal" class="fixed inset-0 hidden flex items-center justify-center z-50"
        style="background-color: rgba(13,30,54,0.65); backdrop-filter: blur(4px);"
        onclick="if(event.target===this) closeCancelModal()">
        <div class="card-panel w-full max-w-md overflow-hidden p-0" style="border-radius: 20px;">
            <div class="flex items-center justify-between px-6 py-5 border-b" style="border-color: var(--border-light);">
                <h3 class="text-base font-bold" style="color: var(--danger);">Anular Comprobante de Venta</h3>
                <button onclick="closeCancelModal()" class="btn-icon">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="p-6 space-y-4"
                onsubmit="return confirm('¿Seguro que deseas anular esta venta? Esta operación es irreversible.')">
                @csrf
                <div>
                    <label for="reason" class="form-label">Motivo de Anulación <span
                            class="text-red-500">*</span></label>
                    <textarea name="reason" id="reason" rows="3" class="input-solid"
                        placeholder="Ej: Error en los productos facturados, solicitud del cliente, etc." required></textarea>
                </div>

                <div class="border-t pt-4 flex justify-end gap-2" style="border-color: var(--border-light);">
                    <button type="button" onclick="closeCancelModal()" class="btn-outline">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background-color: var(--danger);">Anular
                        Documento</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openPaymentModal() {
            document.getElementById('payment-modal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('payment-modal').classList.add('hidden');
        }

        function openCancelModal() {
            document.getElementById('cancel-modal').classList.remove('hidden');
        }

        function closeCancelModal() {
            document.getElementById('cancel-modal').classList.add('hidden');
        }
    </script>
@endpush
