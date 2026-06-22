@extends('layouts.app')

@section('title', 'Caja de Suscripciones')
@section('page-title', 'Caja de Suscripciones')
@section('page-subtitle', 'Historial de ingresos y conciliación de comprobantes de pago de la plataforma')

@section('content')
    {{-- Alerts --}}
    @if (session('success'))
        <div class="mb-4 p-4 rounded-xl text-sm font-medium border border-emerald-500/20 bg-emerald-500/10 text-emerald-500">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 rounded-xl text-sm font-medium border border-rose-500/20 bg-rose-500/10 text-rose-500">
            {{ session('error') }}
        </div>
    @endif

    {{-- Cash Box Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="card-panel p-6 border-l-4 border-emerald-500">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Recaudado Estimado</h3>
            <p class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">${{ number_format($estimatedRevenue, 2) }}</p>
            <span class="text-[10px] text-slate-400">Total acumulado de pagos aprobados</span>
        </div>
        <div class="card-panel p-6 border-l-4 border-teal-500">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pagos Aprobados</h3>
            <p class="text-3xl font-bold text-teal-500">{{ $totalApproved }}</p>
            <span class="text-[10px] text-slate-400">Transacciones completadas</span>
        </div>
        <div class="card-panel p-6 border-l-4 border-amber-500">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pagos Pendientes</h3>
            <p class="text-3xl font-bold text-amber-500">{{ $totalPending }}</p>
            <span class="text-[10px] text-slate-400">Requieren conciliación manual</span>
        </div>
        <div class="card-panel p-6 border-l-4 border-rose-500">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pagos Rechazados</h3>
            <p class="text-3xl font-bold text-rose-500">{{ $totalRejected }}</p>
            <span class="text-[10px] text-slate-400">Transacciones inválidas</span>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="card-panel p-6 mb-6">
        <form method="GET" action="{{ route('superadmin.payments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="status" class="form-label text-xs">Filtrar por Estado</label>
                <select name="status" id="status" class="input-solid text-xs">
                    <option value="">Todos los Estados</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Aprobado</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rechazado</option>
                </select>
            </div>
            <div>
                <label for="plan" class="form-label text-xs">Filtrar por Plan</label>
                <select name="plan" id="plan" class="input-solid text-xs">
                    <option value="">Todos los Planes</option>
                    <option value="basic" {{ $plan === 'basic' ? 'selected' : '' }}>Basic</option>
                    <option value="standard" {{ $plan === 'standard' ? 'selected' : '' }}>Standard</option>
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn-primary text-xs py-2 px-4 flex-1">
                    <i class="fa-solid fa-filter mr-1.5"></i> Filtrar
                </button>
                <a href="{{ route('superadmin.payments.index') }}" class="btn-secondary text-xs py-2 px-4 flex-1 text-center justify-center">
                    <i class="fa-solid fa-eraser mr-1.5"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Payments Log Table --}}
    <div class="card-panel overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
            <h2 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-teal-500"></i> Registro Diario de Transacciones
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">ID Pago</th>
                        <th class="px-6 py-4">Empresa</th>
                        <th class="px-6 py-4">Plan</th>
                        <th class="px-6 py-4">Transacción</th>
                        <th class="px-6 py-4">Fecha Solicitud</th>
                        <th class="px-6 py-4">Banco Origen</th>
                        <th class="px-6 py-4">Cuenta Destino</th>
                        <th class="px-6 py-4">Comprobante</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">#{{ $payment->id }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">
                                @if($payment->company)
                                    <a href="{{ route('superadmin.companies.show', $payment->company_id) }}" 
                                       class="text-brand-blue dark:text-primary hover:underline font-bold">
                                        {{ $payment->company->name }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Empresa eliminada</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 capitalize text-slate-700 dark:text-slate-300 font-semibold">{{ $payment->plan }}</td>
                            <td class="px-6 py-4">
                                @if($payment->type === 'signup')
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Registro</span>
                                @elseif($payment->type === 'upgrade')
                                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">Mejora</span>
                                @else
                                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-500">Renovación</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-500">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $payment->bank_origin }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $payment->account_destination }}</td>
                            <td class="px-6 py-4">
                                @if($payment->receipt_path)
                                    <button type="button"
                                            onclick="Alpine.store('paymentModal').open({
                                                id: {{ $payment->id }},
                                                company_name: '{{ addslashes(e($payment->company->name)) }}',
                                                plan: '{{ $payment->plan }}',
                                                type: '{{ $payment->type }}',
                                                bank_origin: '{{ addslashes(e($payment->bank_origin)) }}',
                                                account_destination: '{{ addslashes(e($payment->account_destination)) }}',
                                                status: '{{ $payment->status }}',
                                                rejection_reason: '{{ addslashes(e($payment->rejection_reason)) }}',
                                                formatted_date: '{{ $payment->created_at->format('d/m/Y H:i') }}',
                                                receipt_url: '{{ route('receipts.show', basename($payment->receipt_path)) }}',
                                                approve_url: '{{ route('superadmin.companies.approve', $payment->company_id) }}',
                                                reject_url: '{{ route('superadmin.companies.reject', $payment->company_id) }}'
                                            })"
                                            class="inline-flex items-center gap-1.5 text-sm font-semibold hover:underline focus:outline-none"
                                            style="color: var(--primary);">
                                        <i class="fa-solid fa-receipt text-xs"></i> Ver Detalle
                                    </button>
                                @else
                                    <span class="text-slate-400">Sin archivo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($payment->status === 'approved')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Aprobado
                                    </span>
                                @elseif($payment->status === 'rejected')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                        Rechazado
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        Pendiente
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($payment->status === 'pending')
                                    <div class="flex items-center justify-center gap-2">
                                        <form action="{{ route('superadmin.companies.approve', $payment->company_id) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                            <button type="submit" class="btn-success text-xs py-1 px-3">
                                                Aprobar
                                            </button>
                                        </form>
                                        <form action="{{ route('superadmin.companies.reject', $payment->company_id) }}" method="POST" class="inline"
                                              onsubmit="const reason = prompt('Motivo de rechazo del pago:'); if (reason === null) return false; this.reason.value = reason;">
                                            @csrf
                                            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                            <input type="hidden" name="reason" value="">
                                            <button type="submit" class="btn-danger text-xs py-1 px-3">
                                                Rechazar
                                            </button>
                                        </form>
                                    </div>
                                @elseif($payment->status === 'approved')
                                    @if($payment->electronicInvoice && $payment->electronicInvoice->status === 'authorized')
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="{{ route('billing.invoices.xml', $payment->electronicInvoice->id) }}" class="btn-secondary !p-1.5 text-xs" title="Descargar XML SRI">
                                                <i class="fa-solid fa-file-code text-teal-600"></i>
                                            </a>
                                            <a href="{{ route('billing.invoices.pdf', $payment->electronicInvoice->id) }}" class="btn-secondary !p-1.5 text-xs" title="Descargar PDF RIDE">
                                                <i class="fa-solid fa-file-pdf text-rose-600"></i>
                                            </a>
                                        </div>
                                    @else
                                        <form action="{{ route('superadmin.payments.invoice', $payment->id) }}" method="POST" class="inline" onsubmit="return confirm('¿Deseas emitir la factura electrónica para esta suscripción?')">
                                            @csrf
                                            <button type="submit" class="btn-primary text-xs py-1 px-3" style="background-color: var(--primary);">
                                                Emitir Factura
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-8 text-center text-slate-400">
                                No se encontraron registros de pagos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Links --}}
        @if($payments->hasPages())
            <div class="p-6 border-t border-slate-200 dark:border-slate-800">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

@endsection

@push('modals')
    @include('superadmin.partials.payment-modal')
@endpush


