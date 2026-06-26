/**
 * Profile Form Handler (Alpine.js)
 * Manages profile editing with AJAX submission via Alpine.js
 * 
 * Usage: x-data="profileFormHandler()" in Blade template
 */
function profileFormHandler() {
    return {
        loading: false,
        saving: false,
        showSuccess: false,
        successMessage: '',
        errors: {},
        currentData: {
            name: '',
            email: '',
            phone: '',
            language: 'es',
            timezone: 'America/Guayaquil',
            notifications_enabled: true,
        },
        formData: {
            name: '',
            email: '',
            phone: '',
            language: 'es',
            timezone: 'America/Guayaquil',
            notifications_enabled: true,
        },

        /**
         * Initialize form with user data
         */
        init() {
            // Get CSRF token from meta tag
            this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // Load existing data if available
            this.loadFormData();
        },

        /**
         * Load form data from API or local storage
         */
        loadFormData() {
            // Try to fetch current user profile via API
            fetch('/api/profile', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            })
            .then(response => response.json())
            .then(data => {
                const userData = {
                    name: data.name || '',
                    email: data.email || '',
                    phone: data.phone || '',
                    language: data.language || 'es',
                    timezone: data.timezone || 'America/Guayaquil',
                    notifications_enabled: data.notifications_enabled ?? true,
                };
                // Mostrar información actual
                this.currentData = userData;
                // Pre-llenar formulario de edición
                this.formData = userData;
            })
            .catch(error => console.error('Failed to load profile:', error));
        },

        /**
         * Submit form via AJAX
         */
        async submitForm() {
            this.saving = true;
            this.errors = {};

            try {
                const response = await fetch('/api/profile', {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body: JSON.stringify(this.formData),
                });

                const data = await response.json();

                if (response.ok) {
                    this.successMessage = data.message || 'Profile updated successfully!';
                    this.showSuccess = true;
                    setTimeout(() => {
                        this.showSuccess = false;
                    }, 3000);
                } else {
                    // Handle validation errors
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        this.errors = { form: [data.message || 'Failed to update profile'] };
                    }
                }
            } catch (error) {
                this.errors = { form: ['An error occurred. Please try again.'] };
                console.error('Form submission error:', error);
            } finally {
                this.saving = false;
            }
        },

        /**
         * Check if form has any errors
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
    };
}
