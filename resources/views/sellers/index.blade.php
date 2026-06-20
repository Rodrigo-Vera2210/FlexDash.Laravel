@extends('layouts.app')

@section('title', 'Vendedores')
@section('page-title', 'Vendedores')
@section('page-subtitle', 'Administración de vendedores de su empresa y límites del plan')

@section('header-actions')
    <a href="{{ route('sellers.create') }}" class="btn-primary">
        <i class="fa-solid fa-user-plus"></i> Nuevo Vendedor
    </a>
@endsection

@section('content')
    {{-- Alerts --}}
    @if (session('status'))
        <div class="mb-4 p-4 rounded-xl text-sm font-medium"
             style="background-color: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: var(--success);">
            {{ session('status') }}
        </div>
    @endif

    {{-- Plan limit warning banner --}}
    @php
        $limit = $company->subscription_plan === 'basic' ? 2 : ($company->subscription_plan === 'standard' ? 10 : PHP_INT_MAX);
        $count = $sellers->where('status', 'active')->count();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Plan de Suscripción</h3>
            <p class="text-2xl font-bold text-brand-blue" style="color: var(--primary);">{{ ucfirst($company->subscription_plan) }}</p>
        </div>
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Límite de Vendedores</h3>
            <p class="text-2xl font-bold" style="color: var(--text-main);">
                {{ $limit === PHP_INT_MAX ? 'Ilimitado' : $limit }}
            </p>
        </div>
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Vendedores Activos</h3>
            <p class="text-2xl font-bold {{ $count >= $limit ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300' }}">
                {{ $count }}
            </p>
        </div>
    </div>

    @if($count >= $limit)
        <div class="mb-6 p-4 rounded-xl text-sm font-medium bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Ha alcanzado el límite máximo de vendedores activos para el Plan {{ ucfirst($company->subscription_plan) }}. Para crear o activar más vendedores, mejore su plan en la sección de Suscripción.</span>
        </div>
    @endif

    {{-- Sellers Table --}}
    <div class="card-panel overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center">
            <h2 class="font-bold text-slate-800 dark:text-slate-100">Lista de Vendedores</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">Nombre</th>
                        <th class="px-6 py-4">Correo Electrónico</th>
                        <th class="px-6 py-4">Rol</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($sellers as $seller)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">{{ $seller->name }}</td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $seller->email }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    Vendedor
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($seller->status === 'active')
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Activo
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('sellers.toggle', $seller) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary text-xs py-1.5 px-3">
                                        @if($seller->status === 'active')
                                            Desactivar
                                        @else
                                            Activar
                                        @endif
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-users text-4xl mb-3 block opacity-30"></i>
                                No se encontraron vendedores registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
