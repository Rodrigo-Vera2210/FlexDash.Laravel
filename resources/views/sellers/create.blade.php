@extends('layouts.app')

@section('title', 'Nuevo Vendedor')
@section('page-title', 'Crear Vendedor')
@section('page-subtitle', 'Agregar una nueva cuenta de vendedor para operar ventas e inventario')

@section('content')
    <div class="max-w-2xl">
        {{-- Form card --}}
        <div class="card-panel p-6">
            <h2 class="font-bold text-lg mb-4 text-slate-800 dark:text-slate-100">Datos de la Cuenta</h2>

            <form action="{{ route('sellers.store') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Nombre Completo</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue"
                           placeholder="Ej. Juan Pérez" required>
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue"
                           placeholder="ejemplo@empresa.com" required>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-semibold mb-1 text-slate-700 dark:text-slate-300">Contraseña</label>
                    <input type="password" id="password" name="password"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 bg-transparent rounded-lg text-slate-800 dark:text-slate-100 focus:outline-none focus:border-brand-blue"
                           placeholder="Mínimo 8 caracteres" required>
                </div>

                {{-- Actions --}}
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                    <a href="{{ route('sellers.index') }}" class="btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-save"></i> Guardar Vendedor
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
