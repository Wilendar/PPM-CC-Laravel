import './bootstrap';

// Import vanilla-colorful Web Component for AttributeColorPicker
import 'vanilla-colorful/hex-color-picker.js';

// Livewire 3.x provides Alpine with $persist magic already included
// Use 'livewire:init' to register stores and data after Livewire starts Alpine
document.addEventListener('livewire:init', () => {
    const Alpine = window.Alpine;

    // Theme store (using localStorage directly instead of Alpine.$persist to avoid Livewire conflicts)
    Alpine.store('theme', {
        // Initialize from localStorage
        dark: localStorage.getItem('theme_dark') === 'true',

        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('theme_dark', this.dark);
            this.updateDOM();
        },

        updateDOM() {
            if (this.dark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        // Initialize DOM state on load
        init() {
            this.updateDOM();
        }
    });

    // Loading store
    Alpine.store('loading', {
        isLoading: false,
        loadingText: 'Loading...',

        show(text = 'Loading...') {
            this.isLoading = true;
            this.loadingText = text;
        },

        hide() {
            this.isLoading = false;
        }
    });

    // Notifications store
    Alpine.store('notifications', {
        items: [],
        nextId: 1,

        add(message, type = 'info', duration = 5000) {
            const notification = {
                id: this.nextId++,
                message,
                type,
                timestamp: Date.now()
            };

            this.items.push(notification);

            if (duration > 0) {
                setTimeout(() => {
                    this.remove(notification.id);
                }, duration);
            }

            return notification;
        },

        remove(id) {
            const index = this.items.findIndex(item => item.id === id);
            if (index > -1) {
                this.items.splice(index, 1);
            }
        },

        clear() {
            this.items = [];
        },

        success(message, duration = 5000) {
            return this.add(message, 'success', duration);
        },

        error(message, duration = 0) {
            return this.add(message, 'error', duration);
        },

        warning(message, duration = 7000) {
            return this.add(message, 'warning', duration);
        },

        info(message, duration = 5000) {
            return this.add(message, 'info', duration);
        }
    });

    // Product search component
    Alpine.data('productSearch', () => ({
        search: '',
        suggestions: [],
        loading: false,

        async fetchSuggestions() {
            if (this.search.length < 2) {
                this.suggestions = [];
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/api/products/search?q=' + encodeURIComponent(this.search));
                const data = await response.json();
                this.suggestions = data.suggestions || [];
            } catch (error) {
                console.error('Search error:', error);
                if (Alpine.store('notifications')) {
                    Alpine.store('notifications').error('Search failed: ' + error.message);
                }
            } finally {
                this.loading = false;
            }
        }
    }));

    // Attribute Color Picker component (for Livewire AttributeValueManager)
    Alpine.data('attributeColorPicker', (initialColor = '#000000', hasInitialError = false) => ({
        colorValue: initialColor,
        hasError: hasInitialError,

        /**
         * Handle color picker color-changed event
         * @param {CustomEvent} event - color-changed event from vanilla-colorful
         */
        handleColorChanged(event) {
            const newColor = event.detail.value;

            // Update local state
            this.colorValue = newColor.toUpperCase();

            // Clear error on valid change
            this.hasError = false;

            // Trigger Livewire update (using Livewire 3.x $wire magic)
            if (this.$wire) {
                this.$wire.set('color', this.colorValue);
            }
        },

        /**
         * Handle manual input in color field
         * @param {Event} event - Input event
         */
        handleColorInput(event) {
            let value = event.target.value.trim();

            // Auto-add # prefix if missing
            if (value && !value.startsWith('#')) {
                value = '#' + value;
                event.target.value = value;
            }

            // Convert to uppercase
            if (value) {
                value = value.toUpperCase();
                event.target.value = value;
            }

            // Update local state
            this.colorValue = value;

            // Validate format
            this.hasError = !this.isValidHex(value);
        },

        /**
         * Validate if string is valid #RRGGBB hex color
         * @param {string} hex - Color value to validate
         * @returns {boolean}
         */
        isValidHex(hex) {
            if (!hex) return false;
            return /^#[0-9A-Fa-f]{6}$/.test(hex);
        },

        /**
         * Convert hex color to RGB string (for display)
         * @param {string} hex - Hex color value
         * @returns {string} - RGB string (e.g., "255, 0, 0")
         */
        getRgbFromHex(hex) {
            if (!this.isValidHex(hex)) return '-, -, -';

            const color = hex.substring(1);
            const r = parseInt(color.substring(0, 2), 16);
            const g = parseInt(color.substring(2, 4), 16);
            const b = parseInt(color.substring(4, 6), 16);

            return `${r}, ${g}, ${b}`;
        }
    }));

    // Initialize theme from localStorage
    Alpine.store('theme').init();

    console.log('Livewire Alpine initialized - stores registered:', {
        loading: Alpine.store('loading'),
        notifications: Alpine.store('notifications'),
        theme: Alpine.store('theme')
    });
});

// Global error handler
window.addEventListener('error', (event) => {
    console.error('Global JavaScript error:', event.error);
});