@extends('layouts.app')

@section('title', $plan ? 'Editar Plan' : 'Crear Plan')
@section('page-title')
    <a href="{{ route('superadmin.plans.index') }}" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors mr-2">
        <i class="fa-solid fa-arrow-left text-base"></i>
    </a>
    {{ $plan ? 'Editar Plan: ' . $plan->name : 'Crear Plan de Suscripción' }}
@endsection
@section('page-subtitle', 'Defina las variables de límite y módulos activos por defecto para este plan.')

@section('content')
    <div class="max-w-2xl">
        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl text-sm font-medium border border-rose-500/20 bg-rose-500/10 text-rose-500">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-panel p-8">
            <form action="{{ $plan ? route('superadmin.plans.update', $plan) : route('superadmin.plans.store') }}" method="POST">
                @csrf
                @if($plan)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="name" class="form-label font-semibold text-slate-700 dark:text-slate-200">Nombre del Plan</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $plan?->name) }}" 
                               required placeholder="Ej: Plan Standard" class="input-solid">
                    </div>
                    <div>
                        <label for="code" class="form-label font-semibold text-slate-700 dark:text-slate-200">Código Único (Slug)</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $plan?->code) }}" 
                               required placeholder="Ej: standard" class="input-solid" {{ $plan ? 'readonly style=opacity:0.6;' : '' }}>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="price" class="form-label font-semibold text-slate-700 dark:text-slate-200">Precio Mensual ($)</label>
                        <input type="number" step="0.01" name="price" id="price" value="{{ old('price', $plan?->price) }}" 
                               required placeholder="Ej: 59.00" class="input-solid">
                    </div>
                    <div>
                        <label for="max_monthly_transactions" class="form-label font-semibold text-slate-700 dark:text-slate-200">Límite Transacciones Mensuales</label>
                        <input type="number" name="max_monthly_transactions" id="max_monthly_transactions" value="{{ old('max_monthly_transactions', $plan?->max_monthly_transactions) }}" 
                               required placeholder="Ej: 500" class="input-solid">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="max_admins" class="form-label font-semibold text-slate-700 dark:text-slate-200">Límite Usuarios Administradores</label>
                        <input type="number" name="max_admins" id="max_admins" value="{{ old('max_admins', $plan?->max_admins) }}" 
                               required placeholder="Ej: 2" class="input-solid">
                    </div>
                    <div>
                        <label for="max_sellers" class="form-label font-semibold text-slate-700 dark:text-slate-200">Límite Usuarios Vendedores</label>
                        <input type="number" name="max_sellers" id="max_sellers" value="{{ old('max_sellers', $plan?->max_sellers) }}" 
                               required placeholder="Ej: 10" class="input-solid">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="form-label font-semibold text-slate-700 dark:text-slate-200 mb-2 block">Módulos Habilitados</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                        @php
                            $availableModules = [
                                'ventas' => 'Ventas (Facturación)',
                                'clientes' => 'Clientes (Directorio)',
                                'caja_chica' => 'Caja Chica (Flujo financiero)',
                                'settings' => 'Configuración y Vendedores',
                                'kardex' => 'Kardex (Inventario Manual)',
                                'compras' => 'Compras (Registro de adquisiciones)',
                                'proveedores' => 'Proveedores (Catálogo)',
                            ];
                            $planModules = old('modules', $plan?->modules ?? []);
                        @endphp
                        @foreach($availableModules as $code => $label)
                            <label class="flex items-center gap-2.5 text-sm cursor-pointer p-1 text-slate-700 dark:text-slate-300">
                                <input type="checkbox" name="modules[]" value="{{ $code }}" 
                                       {{ in_array($code, $planModules) ? 'checked' : '' }}
                                       class="rounded border-slate-300 dark:border-slate-700 text-brand-blue focus:ring-brand-blue">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mb-8">
                    <label class="flex items-center gap-2.5 text-sm cursor-pointer text-slate-700 dark:text-slate-300">
                        <input type="checkbox" name="is_active" value="1" 
                               {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}
                               class="rounded border-slate-300 dark:border-slate-700 text-brand-blue focus:ring-brand-blue">
                        <span class="font-semibold">Plan Activo y Disponible para contratación</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-slate-200 dark:border-slate-800 pt-6">
                    <a href="{{ route('superadmin.plans.index') }}" class="btn-secondary text-sm py-2 px-5">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary text-sm py-2 px-5">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
