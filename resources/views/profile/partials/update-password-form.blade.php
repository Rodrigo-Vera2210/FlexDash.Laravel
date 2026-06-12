<section class="space-y-4">
    <header class="border-b pb-3" style="border-color: var(--border-light);">
        <h2 class="text-base font-bold" style="color: var(--text-main);">
            {{ __('Actualizar Contraseña') }}
        </h2>
        <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
            {{ __('Asegúrate de que tu cuenta esté usando una contraseña larga y aleatoria para mantenerla segura.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="form-label">{{ __('Contraseña Actual') }}</label>
            <input id="update_password_current_password" name="current_password" type="password" class="input-solid mt-1" autocomplete="current-password" />
            @if($errors->updatePassword->get('current_password'))
                <p class="text-red-500 text-xs mt-1">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="form-label">{{ __('Nueva Contraseña') }}</label>
            <input id="update_password_password" name="password" type="password" class="input-solid mt-1" autocomplete="new-password" />
            @if($errors->updatePassword->get('password'))
                <p class="text-red-500 text-xs mt-1">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="form-label">{{ __('Confirmar Contraseña') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="input-solid mt-1" autocomplete="new-password" />
            @if($errors->updatePassword->get('password_confirmation'))
                <p class="text-red-500 text-xs mt-1">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-key"></i>
                {{ __('Guardar Contraseña') }}
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-semibold"
                    style="color: var(--success);"
                >
                    <i class="fa-solid fa-circle-check"></i> {{ __('Contraseña Guardada.') }}
                </p>
            @endif
        </div>
    </form>
</section>
