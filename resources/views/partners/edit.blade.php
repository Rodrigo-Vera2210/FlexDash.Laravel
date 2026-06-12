@extends('layouts.app')

@section('title', 'Editar Socio Comercial')
@section('page-title', 'Editar Socio Comercial')
@section('page-subtitle', 'Modifica la información de contacto, límites o tipo de socio comercial')

@section('content')
<div class="mt-2 max-w-4xl mx-auto page-fade">
    <div class="mb-4">
        <a href="{{ route('partners.show', $partner) }}" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a la ficha
        </a>
    </div>

    <div class="card-panel p-6">
        <form method="POST" action="{{ route('partners.update', $partner) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Sección General --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Información de Identificación</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="type" class="form-label">Tipo de Socio <span class="text-red-500">*</span></label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-user-tag"></i>
                                <select name="type" id="type" class="input-solid" required>
                                    <option value="cliente" {{ old('type', $partner->type) === 'cliente' ? 'selected' : '' }}>Cliente</option>
                                    <option value="proveedor" {{ old('type', $partner->type) === 'proveedor' ? 'selected' : '' }}>Proveedor</option>
                                    <option value="ambos" {{ old('type', $partner->type) === 'ambos' ? 'selected' : '' }}>Ambos</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="credit_limit" class="form-label">Límite Crédito (S/)</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-credit-card"></i>
                                <input type="number" name="credit_limit" id="credit_limit" step="0.01" min="0" value="{{ old('credit_limit', $partner->credit_limit) }}" class="input-solid">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label class="form-label font-bold" style="color: var(--text-secondary);">Doc.</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-address-card"></i>
                                <input type="text" value="{{ $partner->document_type }}" class="input-solid cursor-not-allowed opacity-60" readonly>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="form-label font-bold" style="color: var(--text-secondary);">Número Documento</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-barcode"></i>
                                <input type="text" value="{{ $partner->document_number }}" class="input-solid cursor-not-allowed opacity-60" readonly>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="business_name" class="form-label">Razón Social <span class="text-red-500">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-building"></i>
                            <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $partner->business_name) }}" class="input-solid" required placeholder="Nombre legal completo">
                        </div>
                        @error('business_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="trade_name" class="form-label">Nombre Comercial</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-store"></i>
                            <input type="text" name="trade_name" id="trade_name" value="{{ old('trade_name', $partner->trade_name) }}" class="input-solid" placeholder="Nombre comercial de fantasía">
                        </div>
                    </div>
                </div>

                {{-- Sección de Contacto --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold border-b pb-2" style="color: var(--text-main); border-color: var(--border-light);">Información de Contacto y Ubicación</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-envelope"></i>
                                <input type="email" name="email" id="email" value="{{ old('email', $partner->email) }}" class="input-solid" placeholder="ejemplo@correo.com">
                            </div>
                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="phone" class="form-label">Teléfono / Celular</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-phone"></i>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $partner->phone) }}" class="input-solid" placeholder="Ej: +51 987654321">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label for="address" class="form-label">Dirección Fiscal / Despacho</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-map-location-dot"></i>
                                <input type="text" name="address" id="address" value="{{ old('address', $partner->address) }}" class="input-solid" placeholder="Ej: Av. Las Begonias 456">
                            </div>
                        </div>
                        <div class="col-span-1">
                            <label for="city" class="form-label">Ciudad</label>
                            <div class="input-icon-wrapper">
                                <i class="fa-solid fa-city"></i>
                                <input type="text" name="city" id="city" value="{{ old('city', $partner->city) }}" class="input-solid" placeholder="Ej: Lima">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="form-label">Observaciones internas</label>
                        <textarea name="notes" id="notes" rows="4" class="input-solid" placeholder="Detalles de crédito, horarios de atención, persona de contacto...">{{ old('notes', $partner->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="border-t pt-4 flex justify-end gap-2" style="border-color: var(--border-light);">
                <a href="{{ route('partners.show', $partner) }}" class="btn-outline">Cancelar</a>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Actualizar Socio
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
