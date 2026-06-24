@extends('layouts.app')

@section('title', 'Nuevo Servicio')
@section('page-title', 'Registrar Nuevo Servicio')
@section('page-subtitle', 'Agrega un nuevo servicio al catálogo de tu empresa')

@section('content')
<div class="mt-2 max-w-4xl mx-auto page-fade">
    <div class="mb-4">
        <a href="{{ route('services.index') }}" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al listado
        </a>
    </div>

    <div class="card-panel p-6">
        <form method="POST" action="{{ route('services.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Sección General --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Información Básica</h3>

                    <div>
                        <label for="code" class="form-label">Código de Servicio <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-barcode"></i>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" class="input-solid" required placeholder="Ej: SERV-001">
                        </div>
                        @error('code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="form-label">Nombre del Servicio <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input-solid" required placeholder="Ej: Instalación de Aire Acondicionado">
                        </div>
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="service_category_id" class="form-label">Categoría</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-tags"></i>
                            <select name="service_category_id" id="service_category_id" class="input-solid">
                                <option value="">Sin categoría</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('service_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Descripción</label>
                        <textarea name="description" id="description" rows="3" class="input-solid" placeholder="Detalles o alcance del servicio...">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- Sección Financiera --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Precios e Impuestos</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="cost" class="form-label">Costo Referencial (S/)</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-money-bill-1-wave"></i>
                                <input type="number" name="cost" id="cost" step="0.01" min="0" value="{{ old('cost', '0.00') }}" class="input-solid">
                            </div>
                            <p class="text-xs mt-1" style="color: var(--text-tertiary);">Opcional. Para análisis de rentabilidad.</p>
                        </div>
                        <div>
                            <label for="price" class="form-label">Precio de Venta (S/) <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                <input type="number" name="price" id="price" step="0.01" min="0" value="{{ old('price', '0.00') }}" class="input-solid" required>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="tax_id" class="form-label">Impuesto Aplicable</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-percent"></i>
                            <select name="tax_id" id="tax_id" class="input-solid">
                                <option value="">Exento (0%)</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}" {{ old('tax_id') == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ number_format($tax->rate, 0) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="text-xs mt-1" style="color: var(--text-tertiary);">Selecciona "Exento" si el servicio no grava IVA.</p>
                    </div>

                    <div class="p-4 rounded-xl" style="background-color: var(--bg); border: 1px solid var(--border-light);">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: var(--primary-light);">
                                <i class="fa-solid fa-info-circle" style="color: var(--primary);"></i>
                            </div>
                            <div>
                                <p class="text-sm font-bold" style="color: var(--text-main);">Sin control de inventario</p>
                                <p class="text-xs" style="color: var(--text-tertiary);">Los servicios no afectan stock. Al vender un servicio, no se genera movimiento de inventario.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t pt-4 flex justify-end gap-2" style="border-color: var(--border-light);">
                <a href="{{ route('services.index') }}" class="btn-outline">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar Servicio
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
