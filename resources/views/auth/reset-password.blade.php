<x-guest-layout>
    <h2 class="text-xl font-bold mb-1" style="color: var(--text-main);">Restablecer Contraseña</h2>
    <p class="text-sm mb-6" style="color: var(--text-tertiary);">Crea tu nueva contraseña de acceso</p>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email', $request->email) }}" 
                   required autofocus autocomplete="username"
                   class="input-solid"
                   placeholder="usuario@empresa.com">
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs" />
        </div>

        <!-- Password -->
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

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
            <input id="password_confirmation" 
                   type="password" 
                   name="password_confirmation" 
                   required autocomplete="new-password"
                   class="input-solid"
                   placeholder="••••••••">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs" />
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full justify-center py-3">
                <i class="fa-solid fa-key"></i>
                Restablecer Contraseña
            </button>
        </div>
    </form>
</x-guest-layout>
