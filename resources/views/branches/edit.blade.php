@extends('layouts.app')

@section('title', 'Editar Local')
@section('page-title', 'Editar Local: ' . $branch->name)
@section('page-subtitle', 'Actualice los datos del punto de venta')

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
            <form action="{{ route('branches.update', $branch) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="form-label">Nombre del Local</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $branch->name) }}"
                           class="input-solid" required>
                </div>

                <div>
                    <label for="address" class="form-label">Dirección</label>
                    <input type="text" id="address" name="address" value="{{ old('address', $branch->address) }}"
                           class="input-solid">
                </div>

                <div>
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}"
                           class="input-solid">
                </div>

                <div>
                    <label for="establishment_code" class="form-label">Código de Establecimiento SRI (3 dígitos)</label>
                    <input type="text" id="establishment_code" name="establishment_code"
                           value="{{ old('establishment_code', $branch->establishment_code) }}"
                           class="input-solid font-mono" maxlength="3" pattern="\d{3}" required>
                </div>

                <div>
                    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $branch->is_active) ? 'checked' : '' }}
                               class="rounded border-slate-300 dark:border-slate-700 text-brand-blue focus:ring-brand-blue">
                        <span class="font-semibold">Local activo</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                    <a href="{{ route('branches.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('branches.destroy', $branch) }}" class="mt-4"
                  onsubmit="return confirm('¿Desactivar este local?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary text-rose-500 border-rose-500/30">
                    <i class="fa-solid fa-ban"></i> Desactivar Local
                </button>
            </form>
        </div>
    </div>
@endsection
