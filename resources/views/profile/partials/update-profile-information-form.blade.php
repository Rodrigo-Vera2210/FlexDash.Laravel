<section class="space-y-4">
    <header class="border-b pb-3" style="border-color: var(--border-light);">
        <h2 class="text-base font-bold" style="color: var(--text-main);">
            {{ __('Información de Perfil') }}
        </h2>
        <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
            {{ __('Actualiza la información de tu cuenta y dirección de correo electrónico.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="form-label">{{ __('Nombre') }}</label>
            <input id="name" name="name" type="text" class="input-solid mt-1" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @if($errors->get('name'))
                <p class="text-red-500 text-xs mt-1">{{ $errors->first('name') }}</p>
            @endif
        </div>

        <div>
            <label for="email" class="form-label">{{ __('Correo Electrónico') }}</label>
            <input id="email" name="email" type="email" class="input-solid mt-1" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @if($errors->get('email'))
                <p class="text-red-500 text-xs mt-1">{{ $errors->first('email') }}</p>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 rounded-lg border" style="background-color: var(--warning-light); border-color: var(--warning);">
                    <p class="text-sm" style="color: var(--warning);">
                        {{ __('Tu dirección de correo electrónico no está verificada.') }}

                        <button form="send-verification" class="underline font-semibold" style="color: var(--primary);">
                            {{ __('Haz clic aquí para volver a enviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-bold text-xs" style="color: var(--success);">
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                {{ __('Guardar') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-semibold"
                    style="color: var(--success);"
                >
                    <i class="fa-solid fa-circle-check"></i> {{ __('Guardado.') }}
                </p>
            @endif
        </div>
    </form>
</section>
