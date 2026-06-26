@extends('layouts.app')

@section('title', 'Preferencias')
@section('page-title', 'Mis Preferencias')
@section('page-subtitle', 'Configura idioma, zona horaria, tema y notificaciones')

@section('content')
    <div class="mt-2 max-w-2xl mx-auto space-y-6 page-fade">

        {{-- Language & Timezone Preferences --}}
        <div class="card-panel p-6" x-data="preferencesHandler()" @init="init()">
            <header class="border-b pb-3 mb-6" style="border-color: var(--border-light);">
                <h2 class="text-base font-bold" style="color: var(--text-main);">
                    <i class="fa-solid fa-globe mr-2"></i>
                    {{ __('Configuración Regional') }}
                </h2>
                <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
                    {{ __('Elige tu idioma, zona horaria y formato de fecha preferidos.') }}
                </p>
            </header>

            <form @submit.prevent="savePreferences" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="language" class="form-label">{{ __('Idioma') }}</label>
                        <select id="language" x-model="preferences.language" class="input-solid mt-1 w-full">
                            <option value="es">{{ __('Español') }}</option>
                            <option value="en">{{ __('English') }}</option>
                        </select>
                        <p class="text-xs mt-2" style="color: var(--text-tertiary);">
                            {{ __('Idioma de la interfaz') }}
                        </p>
                    </div>

                    <div>
                        <label for="timezone" class="form-label">{{ __('Zona Horaria') }}</label>
                        <select id="timezone" x-model="preferences.timezone" class="input-solid mt-1 w-full">
                            <optgroup label="{{ __('América') }}">
                                <option value="America/Guayaquil">Ecuador (UTC-5)</option>
                                <option value="America/New_York">New York (UTC-5/-4)</option>
                                <option value="America/Los_Angeles">Los Angeles (UTC-8/-7)</option>
                                <option value="America/Mexico_City">Mexico City (UTC-6/-5)</option>
                                <option value="America/Sao_Paulo">São Paulo (UTC-3/-2)</option>
                            </optgroup>
                            <optgroup label="{{ __('Europa') }}">
                                <option value="Europe/London">London (UTC+0/+1)</option>
                                <option value="Europe/Madrid">Madrid (UTC+1/+2)</option>
                                <option value="Europe/Paris">Paris (UTC+1/+2)</option>
                                <option value="Europe/Berlin">Berlin (UTC+1/+2)</option>
                            </optgroup>
                            <optgroup label="{{ __('Asia') }}">
                                <option value="Asia/Tokyo">Tokyo (UTC+9)</option>
                                <option value="Asia/Shanghai">Shanghai (UTC+8)</option>
                                <option value="Asia/Singapore">Singapore (UTC+8)</option>
                            </optgroup>
                        </select>
                        <p class="text-xs mt-2" style="color: var(--text-tertiary);">
                            {{ __('Usada para mostrar horas y fechas') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <button type="submit" class="btn-primary" :disabled="saving">
                        <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="saving ? '{{ __('Guardando...') }}' : '{{ __('Guardar Preferencias') }}'"></span>
                    </button>

                    <template x-if="showSuccess">
                        <p class="text-sm font-semibold" style="color: var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                            {{ __('Preferencias actualizadas') }}
                        </p>
                    </template>
                </div>
            </form>
        </div>

        {{-- Theme Preference --}}
        <div class="card-panel p-6" x-data="themePreferencesHandler()" @init="init()">
            <header class="border-b pb-3 mb-6" style="border-color: var(--border-light);">
                <h2 class="text-base font-bold" style="color: var(--text-main);">
                    <i class="fa-solid fa-palette mr-2"></i>
                    {{ __('Tema') }}
                </h2>
                <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
                    {{ __('Elige entre modo claro, oscuro o automático según tu preferencia.') }}
                </p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Light Theme -->
                <button @click="applyTheme('light')" class="p-4 rounded-lg border-2 transition-all"
                    :class="currentTheme === 'light' ? 'border-primary bg-primary-light' :
                        'border-border hover:border-primary'"
                    style="border-color: var(--border);">
                    <div class="flex items-center justify-center mb-3">
                        <i class="fa-solid fa-sun text-2xl" style="color: var(--primary);"></i>
                    </div>
                    <p class="text-sm font-semibold" style="color: var(--text-main);">
                        {{ __('Claro') }}
                    </p>
                    <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                        {{ __('Fondo blanco, texto oscuro') }}
                    </p>
                </button>

                <!-- Dark Theme -->
                <button @click="applyTheme('dark')" class="p-4 rounded-lg border-2 transition-all"
                    :class="currentTheme === 'dark' ? 'border-primary bg-primary-light' :
                        'border-border hover:border-primary'"
                    style="border-color: var(--border);">
                    <div class="flex items-center justify-center mb-3">
                        <i class="fa-solid fa-moon text-2xl" style="color: var(--primary);"></i>
                    </div>
                    <p class="text-sm font-semibold" style="color: var(--text-main);">
                        {{ __('Oscuro') }}
                    </p>
                    <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                        {{ __('Fondo oscuro, texto claro') }}
                    </p>
                </button>

                <!-- System Theme -->
                <button @click="applyTheme('system')" class="p-4 rounded-lg border-2 transition-all"
                    :class="currentTheme === 'system' ? 'border-primary bg-primary-light' :
                        'border-border hover:border-primary'"
                    style="border-color: var(--border);">
                    <div class="flex items-center justify-center mb-3">
                        <i class="fa-solid fa-laptop text-2xl" style="color: var(--primary);"></i>
                    </div>
                    <p class="text-sm font-semibold" style="color: var(--text-main);">
                        {{ __('Sistema') }}
                    </p>
                    <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                        {{ __('Sigue la configuración del dispositivo') }}
                    </p>
                </button>
            </div>
        </div>

        {{-- Notification Preferences --}}
        <div class="card-panel p-6" x-data="notificationPreferencesHandler()" @init="init()">
            <header class="border-b pb-3 mb-6" style="border-color: var(--border-light);">
                <h2 class="text-base font-bold" style="color: var(--text-main);">
                    <i class="fa-solid fa-bell mr-2"></i>
                    {{ __('Notificaciones') }}
                </h2>
                <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
                    {{ __('Controla cómo y cuándo recibes notificaciones por correo electrónico.') }}
                </p>
            </header>

            <form @submit.prevent="saveNotificationPreferences" class="space-y-4">
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 rounded-lg"
                        style="background-color: var(--bg); border: 1px solid var(--border-light);">
                        <input type="checkbox" id="notifications_enabled" x-model="preferences.notifications_enabled"
                            class="mt-1" />
                        <div class="flex-1">
                            <label for="notifications_enabled" class="text-sm font-semibold"
                                style="color: var(--text-main);">
                                {{ __('Habilitar notificaciones por correo') }}
                            </label>
                            <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                                {{ __('Recibe notificaciones sobre cambios importantes en tu cuenta y sistemas.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 rounded-lg"
                        style="background-color: var(--bg); border: 1px solid var(--border-light);">
                        <input type="checkbox" id="notifications_sales" x-model="preferences.notifications_sales"
                            :disabled="!preferences.notifications_enabled" class="mt-1" />
                        <div class="flex-1">
                            <label for="notifications_sales" class="text-sm font-semibold"
                                style="color: var(--text-main);">
                                {{ __('Notificaciones de ventas') }}
                            </label>
                            <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                                {{ __('Recibe alertas cuando se realicen nuevas ventas.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 rounded-lg"
                        style="background-color: var(--bg); border: 1px solid var(--border-light);">
                        <input type="checkbox" id="notifications_security" x-model="preferences.notifications_security"
                            :disabled="!preferences.notifications_enabled" class="mt-1" />
                        <div class="flex-1">
                            <label for="notifications_security" class="text-sm font-semibold"
                                style="color: var(--text-main);">
                                {{ __('Alertas de seguridad') }}
                            </label>
                            <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                                {{ __('Recibe alertas sobre accesos y cambios de seguridad.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <button type="submit" class="btn-primary" :disabled="saving">
                        <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                        <span x-text="saving ? '{{ __('Guardando...') }}' : '{{ __('Guardar') }}'"></span>
                    </button>

                    <template x-if="showSuccess">
                        <p class="text-sm font-semibold" style="color: var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                            {{ __('Preferencias actualizadas') }}
                        </p>
                    </template>
                </div>
            </form>
        </div>

    </div>

    <script src="{{ asset('js/theme-preferences.js') }}"></script>
    <script>
        function preferencesHandler() {
            return {
                preferences: {
                    language: 'es',
                    timezone: 'America/Guayaquil',
                },
                saving: false,
                showSuccess: false,
                csrfToken: '',

                init() {
                    this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    this.loadPreferences();
                },

                loadPreferences() {
                    const user = @json($user ?? auth()->user());
                    this.preferences = {
                        language: user.language || 'es',
                        timezone: user.timezone || 'America/Guayaquil',
                    };
                },

                async savePreferences() {
                    this.saving = true;
                    try {
                        const response = await fetch('/api/profile', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify(this.preferences),
                        });

                        if (response.ok) {
                            this.showSuccess = true;
                            setTimeout(() => {
                                this.showSuccess = false;
                            }, 3000);
                        }
                    } catch (error) {
                        console.error('Error saving preferences:', error);
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }

        function notificationPreferencesHandler() {
            return {
                preferences: {
                    notifications_enabled: true,
                    notifications_sales: true,
                    notifications_security: true,
                },
                saving: false,
                showSuccess: false,
                csrfToken: '',

                init() {
                    this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    this.loadPreferences();
                },

                loadPreferences() {
                    const user = @json($user ?? auth()->user());
                    this.preferences = {
                        notifications_enabled: user.notifications_enabled ?? true,
                        notifications_sales: localStorage.getItem('notifications_sales') !== 'false',
                        notifications_security: localStorage.getItem('notifications_security') !== 'false',
                    };
                },

                async saveNotificationPreferences() {
                    this.saving = true;
                    try {
                        const response = await fetch('/api/profile', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                notifications_enabled: this.preferences.notifications_enabled,
                            }),
                        });

                        if (response.ok) {
                            localStorage.setItem('notifications_sales', this.preferences.notifications_sales);
                            localStorage.setItem('notifications_security', this.preferences.notifications_security);
                            this.showSuccess = true;
                            setTimeout(() => {
                                this.showSuccess = false;
                            }, 3000);
                        }
                    } catch (error) {
                        console.error('Error saving notification preferences:', error);
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }
    </script>
@endsection
