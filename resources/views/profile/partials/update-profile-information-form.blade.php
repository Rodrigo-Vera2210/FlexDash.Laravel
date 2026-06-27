@php
    $userData = json_encode([
        'name' => $user->name ?? '',
        'email' => $user->email ?? '',
        'phone' => $user->phone ?? '',
        'language' => $user->language ?? 'es',
        'timezone' => $user->timezone ?? 'America/Guayaquil',
        'notifications_enabled' => (bool)($user->notifications_enabled ?? true),
    ]);
@endphp

<section
    x-data="{
        saving: false,
        showSuccess: false,
        successMessage: '',
        errors: {},
        formData: {{ $userData }},
        get hasErrors() { return Object.keys(this.errors).length > 0; },
        fieldError(field) { return this.errors[field]?.[0] || ''; },
        async submit() {
            this.saving = true;
            this.errors = {};
            const token = document.querySelector('meta[name=csrf-token]').content;
            try {
                const res = await fetch('/api/profile', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(this.formData),
                });
                const data = await res.json();
                if (res.ok) {
                    this.successMessage = data.message || 'Perfil actualizado exitosamente.';
                    this.showSuccess = true;
                    setTimeout(() => { this.showSuccess = false; }, 3000);
                } else {
                    this.errors = data.errors || { form: [data.message || 'Error al actualizar'] };
                }
            } catch(e) {
                this.errors = { form: ['Error de conexión. Intenta de nuevo.'] };
            } finally {
                this.saving = false;
            }
        }
    }"
    class="space-y-4">

    <header class="border-b pb-3" style="border-color: var(--border-light);">
        <h2 class="text-base font-bold" style="color: var(--text-main);">
            {{ __('Información de Perfil') }}
        </h2>
        <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
            {{ __('Actualiza la información de tu cuenta y dirección de correo electrónico.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form @submit.prevent="submit" class="space-y-4">

        <div>
            <label for="name" class="form-label">{{ __('Nombre') }}</label>
            <input id="name" x-model="formData.name" type="text" class="input-solid mt-1"
                placeholder="{{ $user->name }}"
                :class="{ 'border-red-500': fieldError('name') }"
                required autofocus autocomplete="name" />
            <template x-if="fieldError('name')">
                <p class="text-red-500 text-xs mt-1" x-text="fieldError('name')"></p>
            </template>
        </div>

        <div>
            <label for="email" class="form-label">{{ __('Correo Electrónico') }}</label>
            <input id="email" x-model="formData.email" type="email" class="input-solid mt-1"
                placeholder="{{ $user->email }}"
                :class="{ 'border-red-500': fieldError('email') }"
                required autocomplete="username" />
            <template x-if="fieldError('email')">
                <p class="text-red-500 text-xs mt-1" x-text="fieldError('email')"></p>
            </template>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                <div class="mt-3 p-3 rounded-lg border"
                    style="background-color: var(--warning-light); border-color: var(--warning);">
                    <p class="text-sm" style="color: var(--warning);">
                        {{ __('Tu dirección de correo electrónico no está verificada.') }}
                        <button form="send-verification" class="underline font-semibold" style="color: var(--primary);">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-bold text-xs" style="color: var(--success);">
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu correo.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <label for="phone" class="form-label">{{ __('Teléfono (Opcional)') }}</label>
            <input id="phone" x-model="formData.phone" type="tel" class="input-solid mt-1"
                placeholder="{{ $user->phone ?? __('Ingresa tu teléfono') }}"
                :class="{ 'border-red-500': fieldError('phone') }"
                autocomplete="tel" />
            <template x-if="fieldError('phone')">
                <p class="text-red-500 text-xs mt-1" x-text="fieldError('phone')"></p>
            </template>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="language" class="form-label">{{ __('Idioma') }}</label>
                <select id="language" x-model="formData.language" class="input-solid mt-1">
                    <option value="es">Español</option>
                    <option value="en">English</option>
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
            <input id="notifications_enabled" x-model="formData.notifications_enabled"
                type="checkbox" class="rounded" />
            <label for="notifications_enabled" class="text-sm" style="color: var(--text-main);">
                {{ __('Recibir notificaciones por correo electrónico') }}
            </label>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" :disabled="saving"
                style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:pointer;"
                :style="saving ? 'opacity:0.6;cursor:not-allowed;' : ''">
                <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                <span x-text="saving ? 'Guardando...' : 'Guardar Cambios'"></span>
            </button>

            <template x-if="showSuccess">
                <p x-transition class="text-sm font-semibold" style="color: var(--success);" x-text="successMessage"></p>
            </template>
            <template x-if="hasErrors && !showSuccess && errors.form">
                <p class="text-sm font-semibold" style="color: var(--danger);">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i>
                    <span x-text="errors.form[0]"></span>
                </p>
            </template>
        </div>
    </form>
</section>

