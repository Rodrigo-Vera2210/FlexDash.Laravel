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
    <p class="text-center text-sm mt-6" style="color: var(--text-tertiary);">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}"
           class="font-bold ml-1 transition-colors"
           style="color: var(--primary);">
            Inicia sesión aquí
        </a>
    </p>
</x-guest-layout>
