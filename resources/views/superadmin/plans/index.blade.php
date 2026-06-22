@extends('layouts.app')

@section('title', 'Administración de Planes')
@section('page-title', 'Administración de Planes')
@section('page-subtitle', 'Configure y gestione los planes de suscripción de la plataforma y sus límites.')

@section('content')
    {{-- Alerts --}}
    @if (session('success'))
        <div class="mb-6 p-4 rounded-xl text-sm font-medium border border-emerald-500/20 bg-emerald-500/10 text-emerald-500">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex justify-end">
        <a href="{{ route('superadmin.plans.create') }}" class="btn-primary text-sm py-2 px-4 inline-flex items-center gap-2">
            <i class="fa-solid fa-circle-plus"></i> Crear Nuevo Plan
        </a>
    </div>

    <div class="card-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">Nombre del Plan</th>
                        <th class="px-6 py-4">Código</th>
                        <th class="px-6 py-4">Precio Mensual</th>
                        <th class="px-6 py-4">Límite Admins</th>
                        <th class="px-6 py-4">Límite Vendedores</th>
                        <th class="px-6 py-4">Límite Transacciones</th>
                        <th class="px-6 py-4">Módulos Incluidos</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">
                                {{ $plan->name }}
                                @if(!$plan->is_active)
                                    <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-350">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-500 uppercase">{{ $plan->code }}</td>
                            <td class="px-6 py-4 font-bold text-slate-700 dark:text-slate-200">${{ number_format($plan->price, 2) }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $plan->max_admins }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $plan->max_sellers }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $plan->max_monthly_transactions }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach($plan->modules ?? [] as $mod)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 capitalize">
                                            {{ $mod }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('superadmin.plans.edit', $plan) }}" class="btn-secondary text-xs py-1.5 px-3">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar
                                    </a>
                                    <form action="{{ route('superadmin.plans.destroy', $plan) }}" method="POST" class="inline"
                                          onsubmit="return confirm('¿Está seguro de que desea eliminar este plan?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger text-xs py-1.5 px-3">
                                            <i class="fa-solid fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                No hay planes registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
