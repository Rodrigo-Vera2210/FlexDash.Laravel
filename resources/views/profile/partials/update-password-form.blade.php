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
    openModal() { this.showModal = true;
        this.reset(); },
    closeModal() { this.showModal = false;
        this.reset(); },
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
            if (res.ok) { this.step = 2;
                this.startCooldown(data.cooldown_seconds || 30); } else { this.errors = { currentPassword: [data.message || 'Contraseña incorrecta'] }; }
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

    <header class="border-b pb-3" style="border-color: var(--border-light);">
        <h2 class="text-base font-bold" style="color: var(--text-main);">
            {{ __('Actualizar Contraseña') }}
        </h2>
        <p class="mt-1 text-sm" style="color: var(--text-tertiary);">
            {{ __('Asegúrate de que tu cuenta esté usando una contraseña larga y aleatoria para mantenerla segura.') }}
        </p>
    </header>

    <div class="pt-2">
        <button @click="openModal()" type="button"
            style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.625rem 1.25rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:pointer;">
            <i class="fa-solid fa-key"></i>
            <span>{{ __('Cambiar Contraseña') }}</span>
        </button>
    </div>

    <!-- Modal overlay -->
    <div x-show="showModal" x-cloak
        style="position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);"
        @click.self="closeModal()">

        <div
            style="background:var(--surface,#fff);color:var(--text-main,#0d1e36);border-radius:0.75rem;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:100%;max-width:28rem;margin:1rem;">

            <!-- Header del modal -->
            <div
                style="display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border-light,#e5e7eb);">
                <h3 style="font-size:1.05rem;font-weight:700;">{{ __('Cambiar Contraseña') }}</h3>
                <button @click="closeModal()" type="button"
                    style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:#6b7280;padding:0.25rem;">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>

            <!-- Indicador de pasos -->
            <div style="display:flex;gap:0.5rem;padding:1rem 1.5rem 0;">
                <div
                    :style="step >= 1 ? 'flex:1;height:4px;border-radius:2px;background:#0A7EA5;' :
                        'flex:1;height:4px;border-radius:2px;background:#e5e7eb;'">
                </div>
                <div
                    :style="step >= 2 ? 'flex:1;height:4px;border-radius:2px;background:#0A7EA5;' :
                        'flex:1;height:4px;border-radius:2px;background:#e5e7eb;'">
                </div>
                <div
                    :style="step >= 3 ? 'flex:1;height:4px;border-radius:2px;background:#0A7EA5;' :
                        'flex:1;height:4px;border-radius:2px;background:#e5e7eb;'">
                </div>
            </div>

            <!-- Contenido -->
            <div style="padding:1.25rem 1.5rem;">

                <!-- Paso 1: Contraseña actual -->
                <div x-show="step === 1">
                    <p style="font-size:0.875rem;color:#6b7280;margin-bottom:0.75rem;">
                        Ingresa tu contraseña actual para verificar tu identidad.
                    </p>
                    <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Contraseña
                        Actual</label>
                    <input x-model="currentPassword" type="password"
                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.375rem;border:1px solid #d1d5db;background:var(--surface-alt,#f9fafb);color:var(--text-main,#0d1e36);box-sizing:border-box;"
                        :disabled="loading" placeholder="••••••••" autocomplete="current-password"
                        @keyup.enter="currentPassword && requestOtp()" />
                    <template x-if="fieldError('currentPassword')">
                        <p style="color:#ef4444;font-size:0.75rem;margin-top:0.25rem;"
                            x-text="fieldError('currentPassword')"></p>
                    </template>
                </div>

                <!-- Paso 2: Código OTP -->
                <div x-show="step === 2">
                    <p style="font-size:0.875rem;color:#6b7280;margin-bottom:0.75rem;">
                        Se envió un código de 6 dígitos a tu correo electrónico.
                    </p>
                    <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Código
                        OTP</label>
                    <input x-model="otp" type="text"
                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.375rem;border:1px solid #d1d5db;background:var(--surface-alt,#f9fafb);color:var(--text-main,#0d1e36);text-align:center;letter-spacing:0.5rem;font-size:1.25rem;box-sizing:border-box;"
                        :disabled="loading" placeholder="000000" maxlength="6"
                        @keyup.enter="otp.length === 6 && verifyOtp()" />
                    <template x-if="fieldError('otp')">
                        <p style="color:#ef4444;font-size:0.75rem;margin-top:0.25rem;" x-text="fieldError('otp')"></p>
                    </template>
                    <template x-if="otpCooldown > 0">
                        <p style="font-size:0.75rem;color:#6b7280;margin-top:0.25rem;">
                            Reenviar disponible en <span x-text="otpCooldown"></span>s
                        </p>
                    </template>
                </div>

                <!-- Paso 3: Nueva contraseña -->
                <div x-show="step === 3">
                    <p style="font-size:0.875rem;color:#6b7280;margin-bottom:0.75rem;">
                        Ingresa tu nueva contraseña (mínimo 8 caracteres).
                    </p>
                    <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Nueva
                        Contraseña</label>
                    <input x-model="newPassword" type="password"
                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.375rem;border:1px solid #d1d5db;background:var(--surface-alt,#f9fafb);color:var(--text-main,#0d1e36);box-sizing:border-box;margin-bottom:0.75rem;"
                        :disabled="loading" placeholder="Mínimo 8 caracteres" autocomplete="new-password"
                        minlength="8" />
                    <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Confirmar
                        Contraseña</label>
                    <input x-model="newPasswordConfirmation" type="password"
                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.375rem;background:var(--surface-alt,#f9fafb);color:var(--text-main,#0d1e36);box-sizing:border-box;"
                        :style="newPasswordConfirmation && !passwordsMatch ? 'border:1px solid #ef4444;' :
                            'border:1px solid #d1d5db;'"
                        :disabled="loading" placeholder="Repite la contraseña" autocomplete="new-password"
                        minlength="8" />
                    <template x-if="newPasswordConfirmation && !passwordsMatch">
                        <p style="color:#ef4444;font-size:0.75rem;margin-top:0.25rem;">Las contraseñas no coinciden.</p>
                    </template>
                </div>

                <!-- Mensaje de éxito -->
                <template x-if="showSuccess">
                    <div
                        style="margin-top:1rem;padding:0.75rem;background:#d1fae5;border-left:4px solid #059669;border-radius:0.375rem;">
                        <p style="color:#065f46;font-size:0.875rem;font-weight:600;">
                            <i class="fa-solid fa-circle-check"></i>
                            <span x-text="successMessage"></span>
                        </p>
                    </div>
                </template>

                <!-- Error general -->
                <template x-if="hasErrors && errors.form">
                    <div
                        style="margin-top:1rem;padding:0.75rem;background:#fee2e2;border-left:4px solid #dc2626;border-radius:0.375rem;">
                        <p style="color:#7f1d1d;font-size:0.875rem;font-weight:600;">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span x-text="errors.form[0]"></span>
                        </p>
                    </div>
                </template>
            </div>

            <!-- Footer del modal -->
            <div
                style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.5rem;border-top:1px solid var(--border-light,#e5e7eb);gap:0.75rem;">

                <!-- Botón atrás -->
                <div>
                    <template x-if="step > 1">
                        <button @click="step--" type="button" :disabled="loading"
                            style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:500;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;cursor:pointer;">
                            <i class="fa-solid fa-arrow-left"></i>
                            Atrás
                        </button>
                    </template>
                </div>

                <div style="display:flex;gap:0.75rem;">
                    <button @click="closeModal()" type="button"
                        style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:500;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;cursor:pointer;">
                        <i class="fa-solid fa-times"></i>
                        Cancelar
                    </button>

                    <!-- Paso 1: Enviar OTP -->
                    <template x-if="step === 1">
                        <button @click="requestOtp()" type="button" :disabled="loading || !currentPassword"
                            :style="(loading || !currentPassword) ?
                            'display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:not-allowed;opacity:0.6;' :
                            'display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:pointer;'">
                            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-envelope'"></i>
                            <span x-text="loading ? 'Enviando...' : 'Enviar Código'"></span>
                        </button>
                    </template>

                    <!-- Paso 2: Verificar -->
                    <template x-if="step === 2">
                        <button @click="verifyOtp()" type="button" :disabled="loading || otp.length !== 6"
                            :style="(loading || otp.length !== 6) ?
                            'display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:not-allowed;opacity:0.6;' :
                            'display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:pointer;'">
                            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                            <span x-text="loading ? 'Verificando...' : 'Verificar'"></span>
                        </button>
                    </template>

                    <!-- Paso 3: Cambiar contraseña -->
                    <template x-if="step === 3">
                        <button @click="resetPassword()" type="button"
                            :disabled="loading || !passwordValid || !passwordsMatch"
                            :style="(loading || !passwordValid || !passwordsMatch) ?
                            'display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:not-allowed;opacity:0.6;' :
                            'display:inline-flex;align-items:center;gap:0.375rem;padding:0.5rem 1rem;border-radius:0.5rem;font-weight:600;background:#0A7EA5;color:white;border:none;cursor:pointer;'">
                            <i class="fa-solid" :class="loading ? 'fa-spinner fa-spin' : 'fa-lock'"></i>
                            <span x-text="loading ? 'Cambiando...' : 'Cambiar Contraseña'"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</section>
