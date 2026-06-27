<section class="space-y-4" x-data="profileFormHandler()" @init="init()">
    <header class="border-b pb-3" style="border-color: var(--border-light);">
        <h2 class="text-base font-bold" style="color: var(--text-main);">
            {{ __('Información de Perfil') }}
        </h2>
        <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
            {{ __('Actualiza la información de tu cuenta y dirección de correo electrónico.') }}
        </p>
    </header>

    <!-- INFORMACIÓN ACTUAL DEL USUARIO -->
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border" style="border-color: var(--border-light);">
        <h3 class="text-sm font-semibold mb-3" style="color: var(--text-main);">{{ __('Información Actual') }}</h3>
        <div class="grid grid-cols-1 gap-3 text-sm">
            <div class="flex justify-between">
                <span style="color: var(--text-tertiary);">{{ __('Nombre') }}:</span>
                <span class="font-medium" style="color: var(--text-main);" x-text="currentData.name"></span>
            </div>
            <div class="flex justify-between">
                <span style="color: var(--text-tertiary);">{{ __('Correo Electrónico') }}:</span>
                <span class="font-medium" style="color: var(--text-main);" x-text="currentData.email"></span>
            </div>
            <div class="flex justify-between">
                <span style="color: var(--text-tertiary);">{{ __('Teléfono') }}:</span>
                <span class="font-medium" style="color: var(--text-main);"
                    x-text="currentData.phone || '{{ __('No especificado') }}'"></span>
            </div>
            <div class="flex justify-between">
                <span style="color: var(--text-tertiary);">{{ __('Idioma') }}:</span>
                <span class="font-medium" style="color: var(--text-main);"
                    x-text="currentData.language === 'es' ? '{{ __('Español') }}' : '{{ __('English') }}'"></span>
            </div>
            <div class="flex justify-between">
                <span style="color: var(--text-tertiary);">{{ __('Zona Horaria') }}:</span>
                <span class="font-medium" style="color: var(--text-main);" x-text="currentData.timezone"></span>
            </div>
        </div>
    </div>

    <!-- Sección de edición -->
    <div class="border-t pt-4" style="border-color: var(--border-light);">
        <h3 class="text-sm font-semibold mb-4" style="color: var(--text-main);">{{ __('Editar Información') }}</h3>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <!-- AJAX Form -->
    <form @submit.prevent="submitForm" class="mt-6 space-y-4">
        <div>
            <label for="name" class="form-label">{{ __('Nombre') }}</label>
            <input id="name" x-model="formData.name" type="text" class="input-solid mt-1"
                :placeholder="currentData.name || ''" :class="{ 'border-red-500': getFieldError('name') }" required
                autofocus autocomplete="name" />
            <template x-if="getFieldError('name')">
                <p class="text-red-500 text-xs mt-1" x-text="getFieldError('name')"></p>
            </template>
        </div>

        <div>
            <label for="email" class="form-label">{{ __('Correo Electrónico') }}</label>
            <input id="email" x-model="formData.email" type="email" class="input-solid mt-1"
                :placeholder="currentData.email || ''" :class="{ 'border-red-500': getFieldError('email') }" required
                autocomplete="username" />
            <template x-if="getFieldError('email')">
                <p class="text-red-500 text-xs mt-1" x-text="getFieldError('email')"></p>
            </template>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div class="mt-3 p-3 rounded-lg border"
                    style="background-color: var(--warning-light); border-color: var(--warning);">
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

        <div>
            <label for="phone" class="form-label">{{ __('Teléfono (Opcional)') }}</label>
            <input id="phone" x-model="formData.phone" type="tel" class="input-solid mt-1"
                :placeholder="currentData.phone || 'Ingresa tu teléfono'"
                :class="{ 'border-red-500': getFieldError('phone') }" autocomplete="tel" />
            <template x-if="getFieldError('phone')">
                <p class="text-red-500 text-xs mt-1" x-text="getFieldError('phone')"></p>
            </template>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="language" class="form-label">{{ __('Idioma') }}</label>
                <select id="language" x-model="formData.language" class="input-solid mt-1">
                    <option value="es">{{ __('Español') }}</option>
                    <option value="en">{{ __('English') }}</option>
                </select>
            </div>

            <div>
                <label for="timezone" class="form-label">{{ __('Zona Horaria') }}</label>
                <select id="timezone" x-model="formData.timezone" class="input-solid mt-1">
                    <option value="America/Guayaquil">America/Guayaquil (UTC-5)</option>
                    <option value="America/New_York">America/New_York (UTC-5/-4)</option>
                    <option value="America/Los_Angeles">America/Los_Angeles (UTC-8/-7)</option>
                    <option value="Europe/London">Europe/London (UTC+0/+1)</option>
                    <option value="Europe/Madrid">Europe/Madrid (UTC+1/+2)</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input id="notifications_enabled" x-model="formData.notifications_enabled" type="checkbox"
                class="rounded" />
            <label for="notifications_enabled" class="text-sm" style="color: var(--text-main);">
                {{ __('Recibir notificaciones por correo electrónico') }}
            </label>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-primary" :disabled="saving">
                <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                <span x-text="saving ? '{{ __('Guardando...') }}' : '{{ __('Guardar') }}'"></span>
            </button>

            <template x-if="showSuccess">
                <p x-transition class="text-sm font-semibold" style="color: var(--success);" x-text="successMessage">
                </p>
            </template>

            <template x-if="hasErrors() && !showSuccess">
                <p class="text-sm font-semibold" style="color: var(--danger);">
                    <i class="fa-solid fa-circle-exclamation"></i> {{ __('Errores en el formulario') }}
                </p>
            </template>
        </div>
    </form>
</section>

<script src="{{ asset('js/profile-form.js') }}"></script>
