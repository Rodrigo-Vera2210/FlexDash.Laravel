/**
 * Theme Preferences Handler (Alpine.js)
 * Manages light/dark mode toggle with localStorage persistence
 * 
 * Usage: x-data="themePreferencesHandler()" in Blade template
 */
function themePreferencesHandler() {
    return {
        currentTheme: localStorage.getItem('theme') || 'system',
        csrfToken: '',

        /**
         * Initialize theme handler
         */
        init() {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            this.applyTheme(this.currentTheme);
        },

        /**
         * Apply theme to DOM and localStorage
         */
        applyTheme(theme) {
            const html = document.documentElement;
            
            // Determine effective theme (system, light, or dark)
            let effectiveTheme = theme;
            if (theme === 'system') {
                effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            // Apply to DOM
            if (effectiveTheme === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }

            // Store in localStorage
            localStorage.setItem('theme', theme);
            localStorage.setItem('effectiveTheme', effectiveTheme);

            this.currentTheme = theme;
            this.syncWithServer(theme);
        },

        /**
         * Toggle between light and dark themes
         */
        toggleTheme() {
            const nextTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
            this.applyTheme(nextTheme);
        },

        /**
         * Sync theme preference with server (optional)
         */
        syncWithServer(theme) {
            if (!this.csrfToken) return;

            fetch('/api/profile', {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    theme_preference: theme,
                }),
            })
            .catch(error => console.error('Failed to sync theme preference:', error));
        },

        /**
         * Get CSS variable for current theme
         */
        getCSSVariable(variableName) {
            return getComputedStyle(document.documentElement).getPropertyValue(`--${variableName}`).trim();
        },
    };
}
