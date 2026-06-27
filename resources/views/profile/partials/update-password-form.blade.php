<section x-data="{
    showModal: false,
    step: 1,
    loading: false,
    showSuccess: false,
    successMessage: '',
    errors: {},
    currentPassword: '',
    otp: '',
    newPassword: '',
    newPasswordConfirmation: '',
    otpCooldown: 0,
    fieldError(field) { return this.errors[field]?.[0] || ''; },
    get hasErrors() { return Object.keys(this.errors).length > 0; },
    get passwordsMatch() { return this.newPassword === this.newPasswordConfirmation; },
    get passwordValid() { return this.newPassword.length >= 8; },
    openModal() {
        this.showModal = true;
        this.reset();
    },
    closeModal() {
        this.showModal = false;
        this.reset();
    },
    reset() {
        this.step = 1;
        this.currentPassword = '';
        this.otp = '';
        this.newPassword = '';
        this.newPasswordConfirmation = '';
        this.otpCooldown = 0;
        this.errors = {};
        this.showSuccess = false;
    },
    startCooldown(secs) {
        this.otpCooldown = secs;
        const iv = setInterval(() => { this.otpCooldown--; if (this.otpCooldown <= 0) clearInterval(iv); }, 1000);
    },
    async requestOtp() {
        this.loading = true;
        this.errors = {};
        const token = document.querySelector('meta[name=csrf-token]').content;
        try {
            const res = await fetch('/password/request-otp', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ current_password: this.currentPassword }),
            });
            const data = await res.json();
            if (res.ok) {
                this.step = 2;
                this.startCooldown(data.cooldown_seconds || 30);
            } else { this.errors = { currentPassword: [data.message || 'Contraseña incorrecta'] }; }
        } catch (e) { this.errors = { form: ['Error de conexión'] }; } finally { this.loading = false; }
    },
    async verifyOtp() {
        this.loading = true;
        this.errors = {};
        const token = document.querySelector('meta[name=csrf-token]').content;
        try {
            const res = await fetch('/password/verify-otp', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ otp: this.otp }),
            });
            const data = await res.json();
            if (res.ok) { this.step = 3; } else { this.errors = { otp: [data.message || 'Código inválido'] }; }
        } catch (e) { this.errors = { form: ['Error de conexión'] }; } finally { this.loading = false; }
    },
    async resetPassword() {
        this.loading = true;
        this.errors = {};
        const token = document.querySelector('meta[name=csrf-token]').content;
        try {
            const res = await fetch('/password/reset', {
                method: 'PUT',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ password: this.newPassword, password_confirmation: this.newPasswordConfirmation }),
            });
            const data = await res.json();
            if (res.ok) {
                this.showSuccess = true;
                this.successMessage = data.message || 'Contraseña cambiada con éxito.';
                setTimeout(() => { this.closeModal(); }, 2000);
            } else {
                this.errors = data.errors || { form: [data.message || 'Error al cambiar contraseña'] };
            }
        } catch (e) { this.errors = { form: ['Error de conexión'] }; } finally { this.loading = false; }
    }
}" class="space-y-4">

    <header class="border-b border-[color:var(--border-light)] pb-3">
        <h2 class="text-base font-bold text-[color:var(--text-main)]">
            {{ __('Actualizar Contraseña') }}
        </h2>
        <p class="mt-1 text-sm text-[color:var(--text-tertiary)]">
            {{ __('Asegúrate de que tu cuenta esté usando una contraseña larga y aleatoria para mantenerla segura.') }}
        </p>
    </header>

    <div class="pt-2">
        <button @click="openModal()" type="button"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg font-semibold bg-[#0A7EA5] text-white border-0 cursor-pointer hover:bg-[#075f7d] transition-colors">
            <i class="fa-solid fa-key"></i>
            <span>{{ __('Cambiar Contraseña') }}</span>
        </button>
    </div>

    <!-- Modal overlay -->
    <div x-show="showModal" x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60"
        @click.self="closeModal()">

        <div class="bg-[color:var(--surface)] text-[color:var(--text-main)] rounded-xl shadow-2xl w-full max-w-md mx-4">

            <!-- Header del modal -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-[color:var(--border-light)]">
                <h3 class="text-base font-bold">{{ __('Cambiar Contraseña') }}</h3>
                <button @click="closeModal()" type="button"
                    class="bg-transparent border-0 cursor-pointer text-lg text-[color:var(--text-tertiary)] p-1 hover:text-[color:var(--text-secondary)] leading-none transition-colors">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <!-- Indicador de pasos -->
            <div class="flex gap-2 px-6 pt-4">
                <div class="flex-1 h-1 rounded-sm transition-colors"
                    :class="step >= 1 ? 'bg-[#0A7EA5]' : 'bg-[color:var(--border)]'"></div>
                <div class="flex-1 h-1 rounded-sm transition-colors"
                    :class="step >= 2 ? 'bg-[#0A7EA5]' : 'bg-[color:var(--border)]'"></div>
                <div class="flex-1 h-1 rounded-sm transition-colors"
                    :class="step >= 3 ? 'bg-[#0A7EA5]' : 'bg-[color:var(--border)]'"></div>
            </div>

            <!-- Contenido -->
            <div class="px-6 py-5">

                <!-- Paso 1: Contraseña actual -->
                <div x-show="step === 1">
                    <p class="text-sm text-[color:var(--text-tertiary)] mb-3">
                        Ingresa tu contraseña actual para verificar tu identidad.
                    </p>
                    <label class="block text-sm font-medium text-[color:var(--text-main)] mb-1">Contraseña Actual</label>
                    <input x-model="currentPassword" type="password"
                        class="w-full px-3 py-2 rounded-md border border-[color:var(--border)] bg-[color:var(--bg)] text-[color:var(--text-main)] text-sm focus:outline-none focus:border-[#0A7EA5] focus:ring-2 focus:ring-[#0A7EA5]/20 transition-colors"
                        :disabled="loading" placeholder="••••••••" autocomplete="current-password"
                        @keyup.enter="currentPassword && requestOtp()" />
                    <template x-if="fieldError('currentPassword')">
                        <p class="text-red-500 text-xs mt-1" x-text="fieldError('currentPassword')"></p>
                    </template>
                </div>

                <!-- Paso 2: Código OTP -->
                <div x-show="step === 2">
                    <p class="text-sm text-[color:var(--text-tertiary)] mb-3">
                        Se envió un código de 6 dígitos a tu correo electrónico.
                    </p>
                    <label class="block text-sm font-medium text-[color:var(--text-main)] mb-1">Código OTP</label>
                    <input x-model="otp" type="text"
                        class="w-full px-3 py-2 rounded-md border border-[color:var(--border)] bg-[color:var(--bg)] text-[color:var(--text-main)] text-center tracking-[0.5rem] text-xl focus:outline-none focus:border-[#0A7EA5] focus:ring-2 focus:ring-[#0A7EA5]/20 transition-colors"
                        :disabled="loading" placeholder="000000" maxlength="6"
                        @keyup.enter="otp.length === 6 && verifyOtp()" />
                    <template x-if="fieldError('otp')">
                        <p class="text-red-500 text-xs mt-1" x-text="fieldError('otp')"></p>
                    </template>
                    <template x-if="otpCooldown > 0">
                        <p class="text-xs text-[color:var(--text-tertiary)] mt-1">
                            Reenviar disponible en <span x-text="otpCooldown"></span>s
                        </p>
                    </template>
                </div>

                <!-- Paso 3: Nueva contraseña -->
                <div x-show="step === 3">
                    <p class="text-sm text-[color:var(--text-tertiary)] mb-3">
                        Ingresa tu nueva contraseña (mínimo 8 caracteres).
                    </p>
                    <label class="block text-sm font-medium text-[color:var(--text-main)] mb-1">Nueva Contraseña</label>
                    <input x-model="newPassword" type="password"
                        class="w-full px-3 py-2 rounded-md border border-[color:var(--border)] bg-[color:var(--bg)] text-[color:var(--text-main)] text-sm focus:outline-none focus:border-[#0A7EA5] focus:ring-2 focus:ring-[#0A7EA5]/20 transition-colors mb-3"
                        :disabled="loading" placeholder="Mínimo 8 caracteres" autocomplete="new-password"
                        minlength="8" />
                    <label class="block text-sm font-medium text-[color:var(--text-main)] mb-1">Confirmar Contraseña</label>
                    <input x-model="newPasswordConfirmation" type="password"
                        class="w-full px-3 py-2 rounded-md bg-[color:var(--bg)] text-[color:var(--text-main)] text-sm focus:outline-none focus:border-[#0A7EA5] focus:ring-2 focus:ring-[#0A7EA5]/20 transition-colors"
                        :class="newPasswordConfirmation && !passwordsMatch ? 'border border-red-400' : 'border border-[color:var(--border)]'"
                        :disabled="loading" placeholder="Repite la contraseña" autocomplete="new-password"
                        minlength="8" />
                    <template x-if="newPasswordConfirmation && !passwordsMatch">
                        <p class="text-red-500 text-xs mt-1">Las contraseñas no coinciden.</p>
                    </template>
                </div>

                <!-- Mensaje de éxito -->
                <template x-if="showSuccess">
                    <div class="mt-4 p-3 bg-emerald-100 border-l-4 border-emerald-600 rounded-md">
                        <p class="text-emerald-800 text-sm font-semibold">
                            <i class="fa-solid fa-circle-check mr-1"></i>
                            <span x-text="successMessage"></span>
                        </p>
                    </div>
                </template>

                <!-- Error general -->
                <template x-if="hasErrors && errors.form">
                    <div class="mt-4 p-3 bg-red-100 border-l-4 border-red-600 rounded-md">
                        <p class="text-red-900 text-sm font-semibold">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>
                            <span x-text="errors.form[0]"></span>
                        </p>
                    </div>
                </template>
            </div>

            <!-- Footer del modal -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-[color:var(--border-light)] gap-3">

                <!-- Botón atrás -->
                <div>
                    <template x-if="step > 1">
                        <button @click="step--" type="button" :disabled="loading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-medium bg-[color:var(--border-light)] text-[color:var(--text-secondary)] border border-[color:var(--border)] cursor-pointer hover:bg-[color:var(--bg)] transition-colors">
                            <i class="fa-solid fa-arrow-left"></i>
                            Atrás
                        </button>
                    </template>
                </div>

                <div class="flex gap-3">
                    <button @click="closeModal()" type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-medium bg-[color:var(--border-light)] text-[color:var(--text-secondary)] border border-[color:var(--border)] cursor-pointer hover:bg-[color:var(--bg)] transition-colors">
                        <i class="fa-solid fa-times"></i>
                        Cancelar
                    </button>

                    <!-- Paso 1: Enviar OTP -->
                    <template x-if="step === 1">
                        <button @click="requestOtp()" type="button" :disabled="loading || !currentPassword"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-semibold bg-[#0A7EA5] text-white border-0 cursor-pointer hover:bg-[#075f7d] transition-colors"
                            :class="(loading || !currentPassword) ? 'opacity-60 !cursor-not-allowed' : ''">
                            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-envelope'"></i>
                            <span x-text="loading ? 'Enviando...' : 'Enviar Código'"></span>
                        </button>
                    </template>

                    <!-- Paso 2: Verificar -->
                    <template x-if="step === 2">
                        <button @click="verifyOtp()" type="button" :disabled="loading || otp.length !== 6"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-semibold bg-[#0A7EA5] text-white border-0 cursor-pointer hover:bg-[#075f7d] transition-colors"
                            :class="(loading || otp.length !== 6) ? 'opacity-60 !cursor-not-allowed' : ''">
                            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            <span x-text="loading ? 'Verificando...' : 'Verificar'"></span>
                        </button>
                    </template>

                    <!-- Paso 3: Cambiar contraseña -->
                    <template x-if="step === 3">
                        <button @click="resetPassword()" type="button"
                            :disabled="loading || !passwordValid || !passwordsMatch"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-semibold bg-[#0A7EA5] text-white border-0 cursor-pointer hover:bg-[#075f7d] transition-colors"
                            :class="(loading || !passwordValid || !passwordsMatch) ? 'opacity-60 !cursor-not-allowed' : ''">
                            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-lock'"></i>
                            <span x-text="loading ? 'Cambiando...' : 'Cambiar Contraseña'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</section>
