@extends('layouts.app')

@section('title', 'Editar Servicio')
@section('page-title', 'Editar Servicio')
@section('page-subtitle', 'Modifica los datos del servicio')

@section('content')
<div class="mt-2 max-w-4xl mx-auto page-fade">
    <div class="mb-4">
        <a href="{{ route('services.index') }}" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i>
            Volver al listado
        </a>
    </div>

    <div class="card-panel p-6">
        <form method="POST" action="{{ route('services.update', $service) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Sección General --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Información Básica</h3>

                    <div>
                        <label for="code" class="form-label">Código de Servicio <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-barcode"></i>
                            <input type="text" name="code" id="code" value="{{ old('code', $service->code) }}" class="input-solid" required>
                        </div>
                        @error('code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="form-label">Nombre del Servicio <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            <input type="text" name="name" id="name" value="{{ old('name', $service->name) }}" class="input-solid" required>
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
                                    <option value="{{ $cat->id }}" {{ old('service_category_id', $service->service_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="form-label">Descripción</label>
                        <textarea name="description" id="description" rows="3" class="input-solid">{{ old('description', $service->description) }}</textarea>
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
                                <input type="number" name="cost" id="cost" step="0.01" min="0" value="{{ old('cost', $service->cost) }}" class="input-solid">
                            </div>
                        </div>
                        <div>
                            <label for="price" class="form-label">Precio de Venta (S/) <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                <input type="number" name="price" id="price" step="0.01" min="0" value="{{ old('price', $service->price) }}" class="input-solid" required>
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
                                    <option value="{{ $tax->id }}" {{ old('tax_id', $service->tax_id) == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ number_format($tax->rate, 0) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Estado</label>
                        <div class="flex items-center gap-3">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                <span class="ml-2 text-sm font-semibold" style="color: var(--text-secondary);">Servicio Activo</span>
                            </label>
                        </div>
                        <p class="text-xs mt-1" style="color: var(--text-tertiary);">Los servicios inactivos no aparecen en los formularios de venta.</p>
                    </div>
                </div>
            </div>

            <div class="border-t pt-4 flex justify-end gap-2" style="border-color: var(--border-light);">
                <a href="{{ route('services.index') }}" class="btn-outline">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Actualizar Servicio
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
