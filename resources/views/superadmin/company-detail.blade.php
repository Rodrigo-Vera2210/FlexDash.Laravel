@extends('layouts.app')

@section('title', 'Detalle de SuscripciÃ³n')
@section('page-title')
    <a href="{{ route('superadmin.dashboard') }}" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors mr-2">
        <i class="fa-solid fa-arrow-left text-base"></i>
    </a>
    Detalle de Empresa: {{ $company->name }}
@endsection
@section('page-subtitle', 'Monitoreo de usuarios activos, plan y registro histÃ³rico de pagos')

@section('content')
    {{-- Alerts --}}
    @if (session('success'))
        <div class="mb-6 p-4 rounded-xl text-sm font-medium border border-emerald-500/20 bg-emerald-500/10 text-emerald-500">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 p-4 rounded-xl text-sm font-medium border border-rose-500/20 bg-rose-500/10 text-rose-500">
            {{ session('error') }}
        </div>
    @endif

    {{-- Company Overview Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Subscription Info Card --}}
        <div class="card-panel p-6 lg:col-span-2">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-brand-blue dark:text-primary"></i> InformaciÃ³n de SuscripciÃ³n
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                <div>
                    <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider mb-1">Plan Actual</span>
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-bold text-slate-800 dark:text-slate-100 capitalize">
                            Plan {{ $company->subscription_plan }}
                        </span>
                        <form action="{{ route('superadmin.companies.change-plan', $company) }}" method="POST" class="inline">
                            @csrf
                            <select name="plan" onchange="this.form.submit()" class="text-xs px-2 py-1 rounded border border-slate-350 dark:border-slate-700 bg-transparent text-slate-800 dark:text-slate-100 focus:outline-none">
                                <option value="basic" {{ $company->subscription_plan === 'basic' ? 'selected' : '' }}>Basic</option>
                                <option value="standard" {{ $company->subscription_plan === 'standard' ? 'selected' : '' }}>Standard</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider mb-1">Estado de SuscripciÃ³n</span>
                    <div>
                        @if($company->subscription_status === 'active')
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                Activa
                            </span>
                        @elseif($company->subscription_status === 'pending_approval')
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                Pendiente de AprobaciÃ³n
                            </span>
                        @elseif($company->subscription_status === 'rejected')
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-150 text-rose-800 dark:bg-rose-900/30 dark:text-rose-350">
                                Rechazada
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                Inactiva
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider mb-1">Fecha de Inicio</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">
                        {{ $startDate ? $startDate->format('d/m/Y H:i') : 'No registrada' }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider mb-1">Fecha de Fin (Vencimiento)</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar-check text-slate-400"></i>
                        {{ $company->subscription_expires_at ? $company->subscription_expires_at->format('d/m/Y H:i') : 'Sin lÃ­mite' }}
                        
                        @if($company->subscription_expires_at)
                            @if(now()->greaterThan($company->subscription_expires_at))
                                <span class="text-xs font-bold text-rose-500 uppercase tracking-wide ml-1">(Expirado)</span>
                            @else
                                <span class="text-xs text-slate-400">({{ now()->diffInDays($company->subscription_expires_at) }} dÃ­as restantes)</span>
                            @endif
                        @endif
                    </span>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                <form action="{{ route('superadmin.companies.toggle-status', $company) }}" method="POST" class="inline"
                      @if($company->subscription_status === 'active')
                          onsubmit="const reason = prompt('Motivo de desactivaciÃ³n:'); if (reason === null) return false; this.reason.value = reason;"
                      @endif>
                    @csrf
                    <input type="hidden" name="reason" value="">
                    <button type="submit" class="btn-secondary text-sm py-2 px-4 inline-flex items-center gap-2">
                        <i class="fa-solid fa-power-off text-xs"></i>
                        {{ $company->subscription_status === 'active' ? 'Suspender SuscripciÃ³n' : 'Activar SuscripciÃ³n' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Entity Info Card --}}
        <div class="card-panel p-6">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-building text-brand-blue dark:text-primary"></i> Datos de la Empresa
            </h3>
            <div class="space-y-3.5 text-sm">
                <div>
                    <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider">RazÃ³n Social</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-150">{{ $company->name }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider">Tipo de Contribuyente</span>
                    <span class="capitalize text-slate-700 dark:text-slate-300">{{ str_replace('_', ' ', $company->company_type) }}</span>
                </div>
                @if($company->tax_id)
                    <div>
                        <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider">IdentificaciÃ³n Tributaria (RUC/NIT)</span>
                        <span class="font-mono text-slate-700 dark:text-slate-300">{{ $company->tax_id }}</span>
                    </div>
                @endif
                <div>
                    <span class="text-slate-400 block font-semibold text-xs uppercase tracking-wider">DirecciÃ³n</span>
                    <span class="text-slate-700 dark:text-slate-300">
                        {{ $company->address ?? $company->legal_address ?? 'No registrada' }}, {{ $company->city }}, {{ $company->country }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Custom Overrides Panel --}}
    <div class="card-panel p-6 mb-6">
        <h3 class="font-bold text-slate-800 dark:text-slate-100 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-sliders text-brand-blue dark:text-primary"></i> PersonalizaciÃ³n de SuscripciÃ³n (Overrides)
        </h3>
        <form action="{{ route('superadmin.companies.custom-limits', $company) }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                {{-- Override Admins --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                    <label class="flex items-center gap-2 font-semibold text-sm text-slate-700 dark:text-slate-200 mb-2 cursor-pointer">
                        <input type="checkbox" name="override_admins" value="1" {{ !is_null($company->getRawOriginal('max_admins')) ? 'checked' : '' }}
                               onchange="document.getElementById('max_admins_input').disabled = !this.checked; if(!this.checked) document.getElementById('max_admins_input').value = '{{ $company->plan?->max_admins }}';"
                               class="rounded border-slate-350 dark:border-slate-700 text-brand-blue">
                        <span>Personalizar LÃ­mite de Admins</span>
                    </label>
                    <input type="number" name="max_admins" id="max_admins_input" 
                           value="{{ $company->max_admins }}"
                           {{ is_null($company->getRawOriginal('max_admins')) ? 'disabled' : '' }}
                           class="input-solid text-xs py-1.5" min="1">
                    <span class="text-[10px] text-slate-400 block mt-1">Por defecto del plan: {{ $company->plan?->max_admins ?? 1 }}</span>
                </div>

                {{-- Override Sellers --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                    <label class="flex items-center gap-2 font-semibold text-sm text-slate-700 dark:text-slate-200 mb-2 cursor-pointer">
                        <input type="checkbox" name="override_sellers" value="1" {{ !is_null($company->getRawOriginal('max_sellers')) ? 'checked' : '' }}
                               onchange="document.getElementById('max_sellers_input').disabled = !this.checked; if(!this.checked) document.getElementById('max_sellers_input').value = '{{ $company->plan?->max_sellers }}';"
                               class="rounded border-slate-350 dark:border-slate-700 text-brand-blue">
                        <span>Personalizar LÃ­mite de Vendedores</span>
                    </label>
                    <input type="number" name="max_sellers" id="max_sellers_input" 
                           value="{{ $company->max_sellers }}"
                           {{ is_null($company->getRawOriginal('max_sellers')) ? 'disabled' : '' }}
                           class="input-solid text-xs py-1.5" min="0">
                    <span class="text-[10px] text-slate-400 block mt-1">Por defecto del plan: {{ $company->plan?->max_sellers ?? 2 }}</span>
                </div>

                {{-- Override Transactions --}}
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                    <label class="flex items-center gap-2 font-semibold text-sm text-slate-700 dark:text-slate-200 mb-2 cursor-pointer">
                        <input type="checkbox" name="override_transactions" value="1" {{ !is_null($company->getRawOriginal('max_monthly_transactions')) ? 'checked' : '' }}
                               onchange="document.getElementById('max_transactions_input').disabled = !this.checked; if(!this.checked) document.getElementById('max_transactions_input').value = '{{ $company->plan?->max_monthly_transactions }}';"
                               class="rounded border-slate-350 dark:border-slate-700 text-brand-blue">
                        <span>Personalizar Transacciones/mes</span>
                    </label>
                    <input type="number" name="max_monthly_transactions" id="max_transactions_input" 
                           value="{{ $company->max_monthly_transactions }}"
                           {{ is_null($company->getRawOriginal('max_monthly_transactions')) ? 'disabled' : '' }}
                           class="input-solid text-xs py-1.5" min="1">
                    <span class="text-[10px] text-slate-400 block mt-1">Por defecto del plan: {{ $company->plan?->max_monthly_transactions ?? 100 }}</span>
                </div>
            </div>

            {{-- Override Modules --}}
            <div class="p-5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 mb-6">
                <label class="flex items-center gap-2 font-bold text-sm text-slate-700 dark:text-slate-200 mb-4 cursor-pointer">
                    <input type="checkbox" name="override_modules" value="1" id="override_modules_checkbox"
                           {{ !is_null($company->getRawOriginal('active_modules')) ? 'checked' : '' }}
                           onchange="const inputs = document.querySelectorAll('.module-checkbox'); inputs.forEach(el => el.disabled = !this.checked);"
                           class="rounded border-slate-350 dark:border-slate-700 text-brand-blue">
                    <span>Personalizar MÃ³dulos Habilitados (Ignorar por defecto del plan)</span>
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @php
                        $availableModules = [
                            'ventas' => 'Ventas (FacturaciÃ³n)',
                            'clientes' => 'Clientes (Directorio)',
                            'caja_chica' => 'Caja Chica',
                            'settings' => 'ConfiguraciÃ³n/Vendedores',
                            'kardex' => 'Kardex (Inventario)',
                            'compras' => 'Compras',
                            'proveedores' => 'Proveedores',
                        ];
                        $companyModules = $company->active_modules;
                    @endphp
                    @foreach($availableModules as $code => $label)
                        <label class="flex items-center gap-2 text-xs font-semibold cursor-pointer text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="active_modules[]" value="{{ $code }}" 
                                   {{ in_array($code, $companyModules) ? 'checked' : '' }}
                                   {{ is_null($company->getRawOriginal('active_modules')) ? 'disabled' : '' }}
                                   class="module-checkbox rounded border-slate-300 dark:border-slate-700 text-brand-blue">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary text-sm py-2 px-5">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar PersonalizaciÃ³n
                </button>
            </div>
        </form>
    </div>

    {{-- Users Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        {{-- Active Administrators --}}
        <div class="card-panel overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center justify-between">
                    <span><i class="fa-solid fa-user-shield text-indigo-500 mr-2"></i> Administradores Activos</span>
                    <span class="px-2 py-0.5 rounded text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold">
                        {{ $activeAdmins->count() }}
                    </span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Correo</th>
                            <th class="px-6 py-3">Rol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($activeAdmins as $admin)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                                <td class="px-6 py-3 font-semibold text-slate-800 dark:text-slate-250">{{ $admin->name }}</td>
                                <td class="px-6 py-3 text-slate-500">{{ $admin->email }}</td>
                                <td class="px-6 py-3 capitalize text-xs font-semibold text-indigo-500">{{ str_replace('_', ' ', $admin->role) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">
                                    No hay administradores activos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Active Sellers --}}
        <div class="card-panel overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center justify-between">
                    <span><i class="fa-solid fa-user-tag text-teal-500 mr-2"></i> Vendedores Activos</span>
                    <span class="px-2 py-0.5 rounded text-xs bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 font-bold">
                        {{ $activeSellers->count() }}
                    </span>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Correo</th>
                            <th class="px-6 py-3">Rol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($activeSellers as $seller)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                                <td class="px-6 py-3 font-semibold text-slate-800 dark:text-slate-250">{{ $seller->name }}</td>
                                <td class="px-6 py-3 text-slate-500">{{ $seller->email }}</td>
                                <td class="px-6 py-3 text-xs font-semibold text-teal-500">Vendedor</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-6 text-center text-slate-400">
                                    No hay vendedores activos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Historical Payments Section --}}
    <div class="card-panel overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-receipt text-amber-500"></i> Registro HistÃ³rico de Pagos
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">TransacciÃ³n</th>
                        <th class="px-6 py-4">Plan</th>
                        <th class="px-6 py-4">Fecha Solicitud</th>
                        <th class="px-6 py-4">Banco Origen</th>
                        <th class="px-6 py-4">Cuenta Destino</th>
                        <th class="px-6 py-4">Comprobante</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($company->subscriptionPayments as $payment)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">#{{ $payment->id }}</td>
                            <td class="px-6 py-4">
                                @if($payment->type === 'signup')
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Registro</span>
                                @elseif($payment->type === 'upgrade')
                                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-500">Mejora</span>
                                @else
                                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-500">RenovaciÃ³n</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 capitalize text-slate-700 dark:text-slate-300 font-semibold">{{ $payment->plan }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $payment->bank_origin }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $payment->account_destination }}</td>
                            <td class="px-6 py-4">
                                @if($payment->receipt_path)
                                    <button type="button"
                                            onclick="Alpine.store('paymentModal').open({
                                                id: {{ $payment->id }},
                                                company_name: '{{ addslashes(e($company->name)) }}',
                                                plan: '{{ $payment->plan }}',
                                                type: '{{ $payment->type }}',
                                                bank_origin: '{{ addslashes(e($payment->bank_origin)) }}',
                                                account_destination: '{{ addslashes(e($payment->account_destination)) }}',
                                                status: '{{ $payment->status }}',
                                                rejection_reason: '{{ addslashes(e($payment->rejection_reason)) }}',
                                                formatted_date: '{{ $payment->created_at->format('d/m/Y H:i') }}',
                                                receipt_url: '{{ route('receipts.show', basename($payment->receipt_path)) }}',
                                                approve_url: '{{ route('superadmin.companies.approve', $company->id) }}',
                                                reject_url: '{{ route('superadmin.companies.reject', $company->id) }}'
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
                                        <form action="{{ route('superadmin.companies.approve', $company) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                            <button type="submit" class="btn-success text-xs py-1 px-3">
                                                Aprobar
                                            </button>
                                        </form>
                                        <form action="{{ route('superadmin.companies.reject', $company) }}" method="POST" class="inline"
                                               onsubmit="const reason = prompt('Motivo de rechazo del pago:'); if (reason === null) return false; this.reason.value = reason;">
                                             @csrf
                                             <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                             <input type="hidden" name="reason" value="">
                                             <button type="submit" class="btn-danger text-xs py-1 px-3">
                                                 Rechazar
                                             </button>
                                         </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">â€”</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                No se registran pagos en el historial para esta empresa.
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
