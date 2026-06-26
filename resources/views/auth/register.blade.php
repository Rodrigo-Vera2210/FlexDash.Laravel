<x-guest-layout>
    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Crear Cuenta</h2>
    <p class="text-sm mb-6" style="color: var(--text-tertiary);">Regístrate para acceder al panel</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        {{-- Nombre --}}
        <div>
            <label for="name" class="form-label">Nombre Completo</label>
            <input id="name"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required autofocus autocomplete="name"
                   class="input-solid"
                   placeholder="Juan Pérez">
            <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-xs" />
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="form-label">Correo Electrónico</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required autocomplete="username"
                   class="input-solid"
                   placeholder="usuario@empresa.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs" />
        </div>

        {{-- Contraseña --}}
        <div>
            <label for="password" class="form-label">Contraseña</label>
            <input id="password"
                   type="password"
                   name="password"
                   required autocomplete="new-password"
                   class="input-solid"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs" />
        </div>

        {{-- Confirmar Contraseña --}}
        <div>
            <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required autocomplete="new-password"
                   class="input-solid"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs" />
        </div>

        {{-- Botón Submit --}}
        <button type="submit" class="btn-primary w-full justify-center py-3">
            <i class="fa-solid fa-user-plus"></i>
            Registrarse
        </button>
    </form>

    {{-- Link de Retorno --}}
    <div class="text-center text-sm mt-8 pt-6 border-t" style="border-color: #e5e7eb;">
        <p style="color: #6b7280;">
            ¿Ya tienes cuenta?
        </p>
        <a href="{{ route('login') }}"
           class="inline-block font-bold mt-2 px-4 py-2 rounded transition-all hover:scale-105" 
           style="color: white; background-color: #0A7EA5; text-decoration: none;">
            ← Volver a Iniciar Sesión
        </a>
    </div>
</x-guest-layout>
