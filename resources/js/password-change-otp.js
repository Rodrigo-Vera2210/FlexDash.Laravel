/**
 * Password Change OTP Handler (Alpine.js)
 * Manages the 3-step password change workflow with OTP verification
 * 
 * Usage: x-data="passwordChangeOtpHandler()" in Blade template
 * Steps:
 * 1. Enter current password and request OTP
 * 2. Verify OTP sent to email
 * 3. Enter new password and confirm
 */
function passwordChangeOtpHandler() {
    return {
        // UI State
        step: 1,
        loading: false,
        showModal: false,
        showSuccess: false,
        successMessage: '',
        errors: {},

        // Form Data
        currentPassword: '',
        otp: '',
        newPassword: '',
        newPasswordConfirmation: '',

        // OTP State
        otpCooldown: 0,
        otpAttempts: 0,
        maxAttempts: 3,
        csrfToken: '',

        /**
         * Initialize OTP handler
         */
        init() {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        /**
         * Request OTP for password change
         */
        async requestOtp() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch('/api/password/request-otp', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        current_password: this.currentPassword,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    this.step = 2;
                    this.otpCooldown = data.cooldown_seconds || 30;
                    this.startCooldown();
                } else {
                    this.errors = { currentPassword: [data.message || 'Invalid password'] };
                }
            } catch (error) {
                this.errors = { form: ['Failed to request OTP'] };
                console.error('OTP request error:', error);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Start countdown timer for OTP cooldown
         */
        startCooldown() {
            const interval = setInterval(() => {
                this.otpCooldown--;
                if (this.otpCooldown <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        },

        /**
         * Verify OTP code
         */
        async verifyOtp() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch('/api/password/verify-otp', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        otp: this.otp,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    this.step = 3;
                } else {
                    this.otpAttempts++;
                    const attemptsLeft = this.maxAttempts - this.otpAttempts;
                    if (attemptsLeft > 0) {
                        this.errors = { otp: [`Invalid OTP. ${attemptsLeft} attempts left.`] };
                    } else {
                        this.errors = { otp: ['Too many failed attempts. Please start over.'] };
                        this.resetForm();
                    }
                }
            } catch (error) {
                this.errors = { form: ['Failed to verify OTP'] };
                console.error('OTP verification error:', error);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Reset password with new password
         */
        async resetPassword() {
            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch('/api/password/reset', {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify({
                        new_password: this.newPassword,
                        new_password_confirmation: this.newPasswordConfirmation,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    this.successMessage = 'Password changed successfully!';
                    this.showSuccess = true;
                    setTimeout(() => {
                        this.closeModal();
                    }, 2000);
                } else {
                    this.errors = data.errors || { form: [data.message || 'Failed to reset password'] };
                }
            } catch (error) {
                this.errors = { form: ['Failed to reset password'] };
                console.error('Password reset error:', error);
            } finally {
                this.loading = false;
            }
        },

        /**
         * Open the password change modal
         */
        openModal() {
            this.showModal = true;
            this.resetForm();
        },

        /**
         * Close the modal
         */
        closeModal() {
            this.showModal = false;
            this.resetForm();
        },

        /**
         * Reset form to initial state
         */
        resetForm() {
            this.step = 1;
            this.currentPassword = '';
            this.otp = '';
            this.newPassword = '';
            this.newPasswordConfirmation = '';
            this.otpCooldown = 0;
            this.otpAttempts = 0;
            this.errors = {};
            this.showSuccess = false;
        },

        /**
         * Go back to previous step
         */
        goBack() {
            if (this.step > 1) {
                if (this.step === 2) {
                    this.step = 1;
                    this.otp = '';
                    this.errors = {};
                } else if (this.step === 3) {
                    this.step = 2;
                    this.newPassword = '';
                    this.newPasswordConfirmation = '';
                    this.errors = {};
                }
            }
        },

        /**
         * Check if form has errors
         */
        hasErrors() {
            return Object.keys(this.errors).length > 0;
        },

        /**
         * Get error message for a field
         */
        getFieldError(field) {
            return this.errors[field]?.[0] || '';
        },

        /**
         * Check if password confirmation matches
         */
        passwordsMatch() {
            return this.newPassword === this.newPasswordConfirmation;
        },

        /**
         * Check if password meets minimum requirements
         */
        passwordValid() {
            return this.newPassword && this.newPassword.length >= 8;
        },
    };
}
