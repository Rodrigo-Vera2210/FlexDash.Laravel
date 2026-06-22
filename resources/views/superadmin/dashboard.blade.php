@extends('layouts.app')

@section('title', 'Portal Superadministrador')
@section('page-title', 'Portal de Control Global')
@section('page-subtitle', 'Monitoreo de suscripciones y aprobaciones de pagos de empresas')

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

    {{-- Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Empresas</h3>
            <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">{{ $metrics['total'] }}</p>
        </div>
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Empresas Activas</h3>
            <p class="text-2xl font-bold text-emerald-500">{{ $metrics['active'] }}</p>
        </div>
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Pendientes de Aprobación</h3>
            <p class="text-2xl font-bold text-amber-500">{{ $metrics['pending'] }}</p>
        </div>
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Suspendidas / Inactivas</h3>
            <p class="text-2xl font-bold text-rose-500">{{ $metrics['blocked'] }}</p>
        </div>
    </div>

    {{-- Pending Approvals Section --}}
    <div class="card-panel overflow-hidden mb-6">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
            <h2 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> Pagos Pendientes de Aprobación
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">Empresa</th>
                        <th class="px-6 py-4">Plan Solicitado</th>
                        <th class="px-6 py-4">Transacción</th>
                        <th class="px-6 py-4">Banco de Origen</th>
                        <th class="px-6 py-4">Cuenta Destino</th>
                        <th class="px-6 py-4">Comprobante</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($pendingPayments as $payment)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">
                                {{ $payment->company->name }}
                            </td>
                            <td class="px-6 py-4 capitalize">{{ $payment->plan }}</td>
                            <td class="px-6 py-4 uppercase text-xs font-bold text-slate-500">{{ $payment->type }}</td>
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
                            <td class="px-6 py-4 text-center flex items-center justify-center gap-2">
                                <form action="{{ route('superadmin.companies.approve', $payment->company_id) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                    <button type="submit" class="btn-success text-xs py-1 px-3">
                                        Aprobar
                                    </button>
                                </form>
                                <form action="{{ route('superadmin.companies.reject', $payment->company_id) }}" method="POST" class="inline"
                                       onsubmit="const reason = prompt('Motivo de rechazo del pago:'); if (reason === null || reason.trim() === '') return false; this.reason.value = reason;">
                                     @csrf
                                     <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                     <input type="hidden" name="reason" value="">
                                     <button type="submit" class="btn-danger text-xs py-1 px-3">
                                         Rechazar
                                     </button>
                                 </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-circle-check text-4xl mb-3 block opacity-30 text-emerald-500"></i>
                                Todo al día. No existen solicitudes de pago pendientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Registered Companies Table --}}
    <div class="card-panel overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h2 class="font-bold text-slate-800 dark:text-slate-100">Empresas Registradas</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">Empresa</th>
                        <th class="px-6 py-4">Tipo</th>
                        <th class="px-6 py-4">Plan Actual</th>
                        <th class="px-6 py-4">Administradores</th>
                        <th class="px-6 py-4">Vendedores</th>
                        <th class="px-6 py-4">Vencimiento</th>
                        <th class="px-6 py-4">Estado Suscripción</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($companies as $company)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">
                                <a href="{{ route('superadmin.companies.show', $company) }}"
                                   class="hover:underline font-bold" style="color: var(--primary);">
                                    {{ $company->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 capitalize text-slate-500">{{ str_replace('_', ' ', $company->company_type) }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('superadmin.companies.change-plan', $company) }}" method="POST" class="flex items-center gap-1.5">
                                    @csrf
                                    <select name="plan" onchange="this.form.submit()"
                                            class="text-xs px-2 py-1 rounded border border-slate-350 dark:border-slate-700 bg-transparent text-slate-800 dark:text-slate-100 focus:outline-none">
                                        <option value="basic" {{ $company->subscription_plan === 'basic' ? 'selected' : '' }}>Basic</option>
                                        <option value="standard" {{ $company->subscription_plan === 'standard' ? 'selected' : '' }}>Standard</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-slate-500">{{ $company->admins_count }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $company->sellers_count }}</td>
                            <td class="px-6 py-4 text-slate-500">
                                {{ $company->subscription_expires_at ? $company->subscription_expires_at->format('d/m/Y') : 'Sin Límite' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($company->subscription_status === 'active')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Activa
                                    </span>
                                @elseif($company->subscription_status === 'pending_approval')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        Pendiente
                                    </span>
                                @elseif($company->subscription_status === 'rejected')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                        Rechazada
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                        Inactiva
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('superadmin.companies.show', $company) }}"
                                       class="btn-secondary text-xs py-1.5 px-3 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-eye"></i> Detalle
                                    </a>
                                    <form action="{{ route('superadmin.companies.toggle-status', $company) }}" method="POST" class="inline"
                                           @if($company->subscription_status === 'active')
                                               onsubmit="const reason = prompt('Motivo de desactivación:'); if (reason === null) return false; this.reason.value = reason;"
                                           @endif>
                                         @csrf
                                         <input type="hidden" name="reason" value="">
                                         <button type="submit" class="btn-secondary text-xs py-1.5 px-3">
                                             {{ $company->subscription_status === 'active' ? 'Desactivar' : 'Activar' }}
                                         </button>
                                     </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                No hay empresas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('modals')
    @include('superadmin.partials.payment-modal')
@endpush
