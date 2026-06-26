@extends('layouts.app')

@section('title', 'Nuevo Local')
@section('page-title', 'Crear Local / Sucursal')
@section('page-subtitle', 'Registre un nuevo punto de venta con su código de establecimiento SRI')

@section('content')
    <div class="max-w-2xl">
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl text-sm font-medium border border-rose-500/20 bg-rose-500/10 text-rose-500">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-panel p-6">
            <form action="{{ route('branches.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="form-label">Nombre del Local</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           class="input-solid" placeholder="Ej. Sucursal Norte" required>
                </div>

                <div>
                    <label for="address" class="form-label">Dirección</label>
                    <input type="text" id="address" name="address" value="{{ old('address') }}"
                           class="input-solid" placeholder="Av. Principal y Calle Secundaria">
                </div>

                <div>
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                           class="input-solid" placeholder="Ej. 0991234567">
                </div>

                <div>
                    <label for="establishment_code" class="form-label">Código de Establecimiento SRI (3 dígitos)</label>
                    <input type="text" id="establishment_code" name="establishment_code"
                           value="{{ old('establishment_code') }}"
                           class="input-solid font-mono" placeholder="002" maxlength="3" pattern="\d{3}" required>
                    <p class="text-xs mt-1" style="color: var(--text-tertiary);">Código único de 3 dígitos asignado por el SRI (ej. 001, 002).</p>
                </div>

                <div>
                    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="rounded border-slate-300 dark:border-slate-700 text-brand-blue focus:ring-brand-blue">
                        <span class="font-semibold">Local activo</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                    <a href="{{ route('branches.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-save"></i> Guardar Local
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
