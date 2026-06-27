<section class="space-y-4" x-data="passwordChangeOtpHandler()" @init="init()">
    <header class="border-b pb-3" style="border-color: var(--border-light);">
        <h2 class="text-base font-bold" style="color: var(--text-main);">
            {{ __('Actualizar Contraseña') }}
        </h2>
        <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
            {{ __('Asegúrate de que tu cuenta esté usando una contraseña larga y aleatoria para mantenerla segura.') }}
        </p>
    </header>

    <div class="flex items-center gap-4 pt-2">
        <button @click="openModal()" type="button" class="btn-primary"
            style="display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-weight: 500;">
            <i class="fa-solid fa-key"></i>
            <span>{{ __('Cambiar Contraseña') }}</span>
        </button>
    </div>

    <!-- Password Change OTP Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center"
        style="background: rgba(0, 0, 0, 0.5);" @click.self="closeModal()" x-cloak>
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 dark:bg-gray-800"
            style="color: var(--text-main);">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b" style="border-color: var(--border-light);">
                <h3 class="text-lg font-semibold">{{ __('Cambiar Contraseña') }}</h3>
                <button @click="closeModal()" type="button" class="text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 space-y-4">
                <!-- Step 1: Request OTP -->
                <div x-show="step === 1" x-transition>
                    <p class="text-sm mb-4" style="color: var(--text-tertiary);">
                        {{ __('Ingresa tu contraseña actual para recibir un código OTP por correo.') }}
                    </p>

                    <div>
                        <label for="current_password" class="form-label">{{ __('Contraseña Actual') }}</label>
                        <input id="current_password" x-model="currentPassword" type="password"
                            class="input-solid mt-1 w-full"
                            :class="{ 'border-red-500': getFieldError('currentPassword') }" :disabled="loading"
                            autocomplete="current-password" />
                        <template x-if="getFieldError('currentPassword')">
                            <p class="text-red-500 text-xs mt-1" x-text="getFieldError('currentPassword')"></p>
                        </template>
                    </div>
                </div>

                <!-- Step 2: Verify OTP -->
                <div x-show="step === 2" x-transition>
                    <p class="text-sm mb-4" style="color: var(--text-tertiary);">
                        {{ __('Se ha enviado un código OTP a tu correo. Ingresalo aquí.') }}
                    </p>

                    <div>
                        <label for="otp" class="form-label">{{ __('Código OTP') }}</label>
                        <input id="otp" x-model="otp" type="text"
                            class="input-solid mt-1 w-full text-center tracking-widest"
                            :class="{ 'border-red-500': getFieldError('otp') }" :disabled="loading || otpCooldown > 0"
                            placeholder="000000" maxlength="6" autocomplete="off" />
                        <template x-if="getFieldError('otp')">
                            <p class="text-red-500 text-xs mt-1" x-text="getFieldError('otp')"></p>
                        </template>
                        <template x-if="otpCooldown > 0">
                            <p class="text-xs mt-1" style="color: var(--text-tertiary);">
                                {{ __('Reintenta en') }} <span x-text="otpCooldown"></span>s
                            </p>
                        </template>
                    </div>
                </div>

                <!-- Step 3: New Password -->
                <div x-show="step === 3" x-transition>
                    <p class="text-sm mb-4" style="color: var(--text-tertiary);">
                        {{ __('Ingresa tu nueva contraseña.') }}
                    </p>

                    <div>
                        <label for="new_password" class="form-label">{{ __('Nueva Contraseña') }}</label>
                        <input id="new_password" x-model="newPassword" type="password" class="input-solid mt-1 w-full"
                            :class="{ 'border-red-500': getFieldError('newPassword') }" :disabled="loading"
                            autocomplete="new-password" minlength="8" />
                        <template x-if="getFieldError('newPassword')">
                            <p class="text-red-500 text-xs mt-1" x-text="getFieldError('newPassword')"></p>
                        </template>
                    </div>

                    <div class="mt-3">
                        <label for="new_password_confirmation"
                            class="form-label">{{ __('Confirmar Contraseña') }}</label>
                        <input id="new_password_confirmation" x-model="newPasswordConfirmation" type="password"
                            class="input-solid mt-1 w-full" :class="{ 'border-red-500': !passwordsMatch() }"
                            :disabled="loading" autocomplete="new-password" minlength="8" />
                        <template x-if="!passwordsMatch() && newPasswordConfirmation">
                            <p class="text-red-500 text-xs mt-1">{{ __('Las contraseñas no coinciden.') }}</p>
                        </template>
                    </div>
                </div>

                <!-- Success Message -->
                <template x-if="showSuccess">
                    <div class="p-3 rounded-lg"
                        style="background-color: var(--success-light); border-left: 4px solid var(--success);">
                        <p class="text-sm font-semibold" style="color: var(--success);">
                            <i class="fa-solid fa-circle-check"></i>
                            <span x-text="successMessage"></span>
                        </p>
                    </div>
                </template>

                <!-- Form Errors -->
                <template x-if="hasErrors() && !showSuccess && errors.form">
                    <div class="p-3 rounded-lg"
                        style="background-color: var(--danger-light); border-left: 4px solid var(--danger);">
                        <p class="text-sm font-semibold" style="color: var(--danger);">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span x-text="errors.form[0]"></span>
                        </p>
                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between gap-3 p-6 border-t"
                style="border-color: var(--border-light);">
                <template x-if="step > 1">
                    <button @click="goBack()" type="button" class="btn-secondary" :disabled="loading">
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ __('Atrás') }}
                    </button>
                </template>

                <template x-if="step === 1">
                    <button @click="requestOtp()" type="button" class="btn-primary ml-auto"
                        :disabled="loading || !currentPassword">
                        <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-envelope'"></i>
                        <span x-text="loading ? '{{ __('Enviando...') }}' : '{{ __('Enviar OTP') }}'"></span>
                    </button>
                </template>

                <template x-if="step === 2">
                    <button @click="verifyOtp()" type="button" class="btn-primary ml-auto"
                        :disabled="loading || otp.length !== 6">
                        <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span x-text="loading ? '{{ __('Verificando...') }}' : '{{ __('Verificar') }}'"></span>
                    </button>
                </template>

                <template x-if="step === 3">
                    <button @click="resetPassword()" type="button" class="btn-primary ml-auto"
                        :disabled="loading || !passwordValid() || !passwordsMatch()">
                        <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                        <span
                            x-text="loading ? '{{ __('Cambiando...') }}' : '{{ __('Cambiar Contraseña') }}'"></span>
                    </button>
                </template>

                <button @click="closeModal()" type="button" class="btn-secondary" :disabled="loading"
                    style="padding: 0.625rem 1rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-times"></i>
                    <span>{{ __('Cancelar') }}</span>
                </button>
            </div>
        </div>
    </div>
</section>

<script src="{{ asset('js/password-change-otp.js') }}"></script>
