@extends('layouts.app')

@section('title', 'Locales / Sucursales')
@section('page-title', 'Locales / Sucursales')
@section('page-subtitle', 'Administra los puntos de venta y códigos de establecimiento SRI de tu empresa')

@section('header-actions')
    <a href="{{ route('branches.create') }}" class="btn-primary">
        <i class="fa-solid fa-store"></i> Nuevo Local
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-4 p-4 rounded-xl text-sm font-medium"
             style="background-color: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: var(--success);">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-4 rounded-xl text-sm font-medium"
             style="background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: var(--danger);">
            {{ session('error') }}
        </div>
    @endif

    @php
        $company = auth()->user()->company;
        $activeCount = $branches->where('is_active', true)->count();
        $limit = $company->max_branches;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Plan de Suscripción</h3>
            <p class="text-2xl font-bold" style="color: var(--primary);">{{ ucfirst($company->subscription_plan) }}</p>
        </div>
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Límite de Locales</h3>
            <p class="text-2xl font-bold" style="color: var(--text-main);">
                {{ $limit >= 9999 ? 'Ilimitado' : $limit }}
            </p>
        </div>
        <div class="card-panel p-6">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Locales Activos</h3>
            <p class="text-2xl font-bold {{ $activeCount >= $limit ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300' }}">
                {{ $activeCount }}
            </p>
        </div>
    </div>

    <div class="card-panel overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h2 class="font-bold text-slate-800 dark:text-slate-100">Lista de Locales</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold text-xs uppercase">
                        <th class="px-6 py-4">Nombre</th>
                        <th class="px-6 py-4">Dirección</th>
                        <th class="px-6 py-4">Teléfono</th>
                        <th class="px-6 py-4">Cód. Establecimiento</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($branches as $branch)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800 dark:text-slate-100">{{ $branch->name }}</td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $branch->address ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $branch->phone ?? '—' }}</td>
                            <td class="px-6 py-4 font-mono font-bold" style="color: var(--primary);">{{ $branch->establishment_code }}</td>
                            <td class="px-6 py-4">
                                @if ($branch->is_active)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('branches.edit', $branch) }}" class="btn-icon" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No hay locales registrados. Cree su primer local para comenzar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
