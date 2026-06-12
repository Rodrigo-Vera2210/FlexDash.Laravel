@extends('layouts.app')

@section('title', 'Productos')
@section('page-title', 'Catálogo de Productos')
@section('page-subtitle', 'Administra el inventario, costos, precios y stock mínimo')

@section('header-actions')
    <a href="{{ route('products.create') }}" class="btn-primary">
        <i class="fa-solid fa-plus"></i>
        Nuevo Producto
    </a>
@endsection

@section('content')
    <div class="mt-2 space-y-6 page-fade">

        {{-- Filtros --}}
        <div class="card-panel p-6">
            <form method="GET" action="{{ route('products.index') }}"
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

                <div class="flex items-center h-10">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="low_stock" value="1" {{ $lowStock ? 'checked' : '' }}
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                        <span class="ml-2 text-sm font-semibold" style="color: var(--text-secondary);">⚠️ Sólo Stock
                            Crítico</span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary justify-center flex-1">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('products.index') }}" class="btn-outline justify-center">
                        <i class="fa-solid fa-xmark"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabla de Productos --}}
        <div class="card-panel overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-custom w-full">
                    <thead>
                        <tr>
                            <th class="table-header">Imagen</th>
                            <th class="table-header">Código</th>
                            <th class="table-header">Nombre</th>
                            <th class="table-header">Categoría</th>
                            <th class="table-header text-right">Stock Actual</th>
                            <th class="table-header text-right">Costo</th>
                            <th class="table-header text-right">Precio Venta</th>
                            <th class="table-header">Impuesto</th>
                            <th class="table-header">Estado</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="table-cell">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                        class="w-10 h-10 rounded-lg object-cover border"
                                        style="border-color: var(--border);"
                                        onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($product->name) }}&size=40&background=0a7ea5&color=ffffff'">
                                </td>
                                <td class="table-cell font-mono font-bold text-xs" style="color: var(--text-tertiary);">
                                    {{ $product->code }}</td>
                                <td class="table-cell">
                                    <div class="font-bold text-sm" style="color: var(--text-main);">{{ $product->name }}
                                    </div>
                                    @if ($product->description)
                                        <div class="text-xs truncate max-w-xs" style="color: var(--text-tertiary);">
                                            {{ $product->description }}</div>
                                    @endif
                                </td>
                                <td class="table-cell" style="color: var(--text-secondary);">{{ $product->category->name }}
                                </td>
                                <td class="table-cell text-right font-mono">
                                    @if ($product->stock <= $product->minimum_stock)
                                        <span class="badge badge-danger font-bold">
                                            {{ number_format($product->stock, 2) }} {{ $product->unit }}
                                        </span>
                                    @else
                                        <span class="font-bold text-sm" style="color: var(--text-main);">
                                            {{ number_format($product->stock, 2) }} {{ $product->unit }}
                                        </span>
                                    @endif
                                    <div class="text-xs mt-0.5" style="color: var(--text-tertiary);">mín:
                                        {{ number_format($product->minimum_stock, 2) }}</div>
                                </td>
                                <td class="table-cell text-right font-semibold font-mono"
                                    style="color: var(--text-secondary);">S/ {{ number_format($product->cost, 2) }}</td>
                                <td class="table-cell text-right font-bold font-mono" style="color: var(--text-main);">S/
                                    {{ number_format($product->price, 2) }}</td>
                                <td class="table-cell text-xs" style="color: var(--text-tertiary);">
                                    {{ $product->tax->name }} ({{ number_format($product->tax->rate, 0) }}%)
                                </td>
                                <td class="table-cell">
                                    @if ($product->is_active)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="table-cell text-center">
                                    <div class="inline-flex gap-2">
                                        <a href="{{ route('products.show', $product) }}" class="btn-icon"
                                            title="Ver Ficha">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('products.edit', $product) }}" class="btn-icon" title="Editar"
                                            style="color: var(--warning);">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form method="POST" action="{{ route('products.destroy', $product) }}"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar este producto?')"
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
                                <td colspan="10" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center"
                                            style="background-color: var(--primary-light);">
                                            <i class="fa-solid fa-boxes-stacked text-xl"
                                                style="color: var(--primary);"></i>
                                        </div>
                                        <p class="text-sm font-medium" style="color: var(--text-tertiary);">
                                            No se encontraron productos con los filtros especificados.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="px-6 py-4 border-t" style="border-color: var(--border-light); background-color: var(--bg);">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
