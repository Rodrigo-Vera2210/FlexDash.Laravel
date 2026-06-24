@extends('layouts.app')

@section('title', 'Servicios')
@section('page-title', 'Catálogo de Servicios')
@section('page-subtitle', 'Administra los servicios que ofrece tu empresa')

@section('header-actions')
    <a href="{{ route('services.create') }}" class="btn-primary">
        <i class="fa-solid fa-plus"></i>
        Nuevo Servicio
    </a>
@endsection

@section('content')
    <div class="mt-2 space-y-6 page-fade">

        {{-- Filtros --}}
        <div class="card-panel p-6">
            <form method="GET" action="{{ route('services.index') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" name="search" id="search" value="{{ $search }}"
                        placeholder="Código o nombre..." class="input-solid">
                </div>

                <div>
                    <label for="category" class="form-label">Categoría</label>
                    <select name="category" id="category" class="input-solid">
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $category == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 md:col-span-2">
                    <button type="submit" class="btn-primary justify-center flex-1">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('services.index') }}" class="btn-outline justify-center">
                        <i class="fa-solid fa-xmark"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabla de Servicios --}}
        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Código</th>
                            <th class="table-header">Nombre</th>
                            <th class="table-header">Categoría</th>
                            <th class="table-header text-right">Costo</th>
                            <th class="table-header text-right">Precio Venta</th>
                            <th class="table-header">Impuesto</th>
                            <th class="table-header">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr>
                                <td class="table-cell font-mono font-bold text-xs" style="color: var(--text-tertiary);">
                                    {{ $service->code }}</td>
                                <td class="table-cell">
                                    <div class="font-bold text-sm" style="color: var(--text-main);">{{ $service->name }}
                                    </div>
                                    @if ($service->description)
                                        <div class="text-xs truncate max-w-xs" style="color: var(--text-tertiary);">
                                            {{ $service->description }}</div>
                                    @endif
                                </td>
                                <td class="table-cell" style="color: var(--text-secondary);">
                                    {{ $service->category->name ?? '—' }}
                                </td>
                                <td class="table-cell text-right font-semibold font-mono"
                                    style="color: var(--text-secondary);">S/ {{ number_format($service->cost, 2) }}</td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--text-main);">S/
                                    {{ number_format($service->price, 2) }}</td>
                                <td class="table-cell text-xs" style="color: var(--text-tertiary);">
                                    @if ($service->tax)
                                        {{ $service->tax->name }} ({{ number_format($service->tax->rate, 0) }}%)
                                    @else
                                        <span class="badge badge-draft">Exento</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    @if ($service->is_active)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="table-cell text-center">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('services.show', $service) }}" class="btn-icon"
                                            title="Ver Ficha">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('services.edit', $service) }}" class="btn-icon" title="Editar"
                                            style="color: var(--warning);">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('services.destroy', $service) }}"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este servicio?')"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Eliminar"
                                                style="color: var(--danger);">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                            style="background-color: var(--primary-light);">
                                            <i class="fa-solid fa-screwdriver-wrench text-xl"
                                                style="color: var(--primary);"></i>
                                        </div>
                                        <p class="text-sm font-medium" style="color: var(--text-tertiary);">
                                            No se encontraron servicios. Crea tu primer servicio.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($services->hasPages())
                <div class="px-6 py-4 border-t" style="border-color: var(--border-light); background-color: var(--bg);">
                    {{ $services->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
