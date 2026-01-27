import './bootstrap';

// Import vanilla-colorful Web Component for AttributeColorPicker
import 'vanilla-colorful/hex-color-picker.js';

// Import resizable columns for import panel
import './resizable-columns.js';

// =====================================================
// PPM DIAGNOSTICS - Bug Report System Support
// Tracks user actions and console errors for bug reports
// =====================================================
window.ppmDiagnostics = {
    actions: [],
    consoleErrors: [],
    maxActions: 5,
    maxErrors: 10,

    /**
     * Track user action for bug report context
     * @param {string} action - Description of action
     */
    trackAction(action) {
        this.actions.push({
            action,
            timestamp: Date.now(),
            url: location.href
        });
        if (this.actions.length > this.maxActions) {
            this.actions.shift();
        }
    },

    /**
     * Get current diagnostics data for bug report
     * @returns {Object} Diagnostics data
     */
    getData() {
        return {
            actions: [...this.actions],
            consoleErrors: [...this.consoleErrors],
            url: location.href,
            browser: navigator.userAgent,
            timestamp: Date.now()
        };
    },

    /**
     * Clear all tracked data
     */
    clear() {
        this.actions = [];
        this.consoleErrors = [];
    },

    /**
     * Initialize diagnostics tracking
     */
    init() {
        // Intercept console.error
        const originalError = console.error;
        console.error = (...args) => {
            this.consoleErrors.push({
                message: args.map(arg => {
                    if (arg instanceof Error) return arg.message;
                    if (typeof arg === 'object') {
                        try { return JSON.stringify(arg); }
                        catch { return String(arg); }
                    }
                    return String(arg);
                }).join(' '),
                timestamp: Date.now(),
                url: location.href
            });
            if (this.consoleErrors.length > this.maxErrors) {
                this.consoleErrors.shift();
            }
            originalError.apply(console, args);
        };

        // Track navigation clicks
        document.addEventListener('click', (e) => {
            const target = e.target.closest('button, a, [wire\\:click], [x-on\\:click]');
            if (target) {
                const text = target.textContent?.trim().slice(0, 50) || target.tagName;
                const identifier = target.id ? `#${target.id}` :
                    target.className ? `.${target.className.split(' ')[0]}` : '';
                this.trackAction(`Click: ${text}${identifier ? ` (${identifier})` : ''}`);
            }
        });

        // Track form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            const formId = form.id || form.action || 'unknown';
            this.trackAction(`Form submit: ${formId}`);
        });

        // Track Livewire navigation
        document.addEventListener('livewire:navigate', () => {
            this.trackAction(`Navigate: ${location.href}`);
        });

        console.log('[PPM Diagnostics] Initialized');
    }
};

// Initialize diagnostics immediately
window.ppmDiagnostics.init();

/**
 * Register Alpine stores and data components.
 * PP.0.6 FIX: Handle case when livewire:init already fired before app.js loaded.
 *
 * Livewire 3.x emits 'livewire:init' ONCE when initializing.
 * If app.js loads async (as ES module via Vite), the event may have already fired.
 * Solution: Check if Alpine is already available, if so register immediately.
 */
function registerAlpineComponents(Alpine) {
    // Prevent double registration
    if (Alpine._ppmAppJsRegistered) {
        return;
    }
    Alpine._ppmAppJsRegistered = true;

    console.log('[PPM app.js] Registering Alpine components...');

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
    // REDESIGNED: Collapsible dropdown panel with proper z-index
    Alpine.data('attributeColorPicker', (initialColor = '#000000', hasInitialError = false) => ({
        colorValue: initialColor,
        hasError: hasInitialError,
        showPicker: false, // NEW: Toggle for collapsible picker

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

    // =====================================================
    // UVE PROPERTY PANEL CONTROLS - ETAP_07f_P5
    // =====================================================

    // Property Panel V2 - main panel component
    Alpine.data('uvePropertyPanelV2', (config) => ({
        activeTab: config.activeTab || 'style',
        hoverState: config.hoverState || 'normal',
        currentDevice: config.currentDevice || 'desktop',
        values: config.values || {},
        hoverValues: config.hoverValues || {},
        responsiveValues: config.responsiveValues || {},
        hoverSupported: config.hoverSupported || [],
        responsiveSupported: config.responsiveSupported || [],
        openSections: ['typography', 'colors', 'size', 'box-model', 'image-settings', 'border', 'effects'],
        newClass: '',
        hasClipboard: false,
        clipboard: null,
        currentClasses: [],

        init() {
            this.currentClasses = this.$wire?.get('panelConfig.cssClasses') || [];
            this.$watch('clipboard', (val) => {
                this.hasClipboard = !!val;
            });
        },

        switchTab(tab) {
            this.activeTab = tab;
        },

        toggleSection(section) {
            if (this.openSections.includes(section)) {
                this.openSections = this.openSections.filter(s => s !== section);
            } else {
                this.openSections.push(section);
            }
        },

        hasHoverControls() {
            return this.hoverSupported.length > 0;
        },

        addClass() {
            const cls = this.newClass.trim();
            if (cls && !this.currentClasses.includes(cls)) {
                this.$wire?.call('addClass', cls);
                this.newClass = '';
            }
        },

        removeClass(cls) {
            this.$wire?.call('removeClass', cls);
        },

        toggleClass(cls) {
            if (this.hasClass(cls)) {
                this.removeClass(cls);
            } else {
                this.$wire?.call('addClass', cls);
            }
        },

        hasClass(cls) {
            return this.currentClasses.includes(cls);
        },

        resetStyles() {
            if (confirm('Czy na pewno chcesz zresetowac style tego elementu?')) {
                this.$wire?.call('resetElementStyles');
            }
        },

        copyStyles() {
            this.clipboard = { ...this.values };
            this.hasClipboard = true;
            this.$wire?.dispatch('notify', { type: 'info', message: 'Style skopiowane' });
        },

        pasteStyles() {
            if (this.clipboard) {
                this.$wire?.call('applyStyles', this.clipboard);
                this.$wire?.dispatch('notify', { type: 'success', message: 'Style wklejone' });
            }
        }
    }));

    // Typography Control - with $watch for all properties
    // FIXED: wire:key forces re-creation on element change, reset buttons trigger $watch
    Alpine.data('uveTypographyControl', (initialValue, units) => ({
        fontSize: initialValue?.fontSize?.replace(/[^0-9.]/g, '') || '16',
        fontSizeUnit: initialValue?.fontSize?.replace(/[0-9.]/g, '') || 'px',
        fontWeight: initialValue?.fontWeight || '400',
        fontFamily: initialValue?.fontFamily || 'inherit',
        lineHeight: initialValue?.lineHeight?.replace(/[^0-9.]/g, '') || '',
        lineHeightUnit: initialValue?.lineHeight?.replace(/[0-9.]/g, '') || '',
        letterSpacing: initialValue?.letterSpacing?.replace(/[^0-9.-]/g, '') || '',
        letterSpacingUnit: initialValue?.letterSpacing?.replace(/[0-9.-]/g, '') || 'px',
        textTransform: initialValue?.textTransform || 'none',
        textDecoration: initialValue?.textDecoration || 'none',
        textAlign: initialValue?.textAlign || 'left',
        units: units || ['px', 'rem', 'em', '%', 'vw'],
        _initialized: false,

        init() {
            console.log('[UVE Typography] init() called with:', {
                initialValue,
                textTransform: this.textTransform,
                textDecoration: this.textDecoration,
                textAlign: this.textAlign
            });

            const watchedProps = [
                'fontSize', 'fontSizeUnit', 'fontWeight', 'fontFamily',
                'lineHeight', 'lineHeightUnit', 'letterSpacing', 'letterSpacingUnit',
                'textTransform', 'textDecoration', 'textAlign'
            ];

            let debounceTimer = null;
            const debouncedEmit = () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => this.emitChange(), 150);
            };

            this.$nextTick(() => {
                this._initialized = true;
                watchedProps.forEach(prop => {
                    this.$watch(prop, (newVal, oldVal) => {
                        if (this._initialized) {
                            console.log(`[UVE Typography] $watch triggered: ${prop} = ${oldVal} -> ${newVal}`);
                            debouncedEmit();
                        }
                    });
                });
            });
        },

        setFontSize(size) {
            const num = size.replace(/[^0-9.]/g, '');
            const unit = size.replace(/[0-9.]/g, '') || 'px';
            this.fontSize = num;
            this.fontSizeUnit = unit;
        },

        // Reset methods for explicit reset buttons
        resetTextTransform() {
            this.textTransform = 'none';
        },

        resetTextDecoration() {
            this.textDecoration = 'none';
        },

        resetTextAlign() {
            this.textAlign = 'left';
        },

        emitChange() {
            const value = {
                fontSize: this.fontSize ? this.fontSize + this.fontSizeUnit : '',
                fontWeight: this.fontWeight,
                fontFamily: this.fontFamily,
                lineHeight: this.lineHeight ? this.lineHeight + this.lineHeightUnit : '',
                letterSpacing: this.letterSpacing ? this.letterSpacing + this.letterSpacingUnit : '',
                textTransform: this.textTransform,
                textDecoration: this.textDecoration,
                textAlign: this.textAlign,
            };
            console.log('[UVE Typography] emitChange() sending:', value);

            if (this.$wire) {
                this.$wire.updateControlValue('typography', value);
            } else {
                console.warn('[UVE Typography] $wire is undefined!');
            }
        }
    }));

    // Device Switcher Control
    Alpine.data('uveDeviceSwitcher', (initialDevice, devices) => ({
        device: initialDevice || 'desktop',
        devices: devices || {},

        switchDevice(newDevice) {
            if (this.device === newDevice) return;
            this.device = newDevice;
            this.$wire?.call('switchDevice', newDevice);
            this.$wire?.dispatch('device-changed', {
                device: newDevice,
                width: this.devices[newDevice]?.width,
                breakpoint: this.getBreakpoint()
            });
        },

        getDimensions() {
            const deviceConfig = this.devices[this.device];
            if (this.device === 'desktop') return '100% width';
            return deviceConfig?.width || '';
        },

        getBreakpoint() {
            switch (this.device) {
                case 'mobile': return 'max-width: 767px';
                case 'tablet': return 'min-width: 768px and max-width: 1023px';
                case 'desktop': return 'min-width: 1024px';
                default: return '';
            }
        },

        getBreakpointLabel() {
            switch (this.device) {
                case 'mobile': return '<768px';
                case 'tablet': return '768px';
                case 'desktop': return '>1024px';
                default: return '';
            }
        },

        getIndicatorStyle() {
            switch (this.device) {
                case 'mobile': return 'left: 0; width: 25%;';
                case 'tablet': return 'left: 25%; width: 25%;';
                case 'desktop': return 'left: 50%; width: 50%;';
                default: return 'left: 50%; width: 50%;';
            }
        }
    }));

    // Hover States Control
    Alpine.data('uveHoverStatesControl', (initialState) => ({
        state: initialState || 'normal',
        previewHover: false,

        setState(newState) {
            this.state = newState;
            this.$wire?.call('switchHoverState', newState);
        },

        togglePreviewHover() {
            this.$wire?.dispatch('uve-preview-hover', { enabled: this.previewHover });
        },

        applyPreset(preset) {
            const presets = {
                opacity: {
                    transition: { property: 'opacity', duration: '200ms', timing: 'ease' },
                    hover: { opacity: '0.7' }
                },
                scale: {
                    transition: { property: 'transform', duration: '200ms', timing: 'ease' },
                    hover: { transform: 'scale(1.05)' }
                },
                shadow: {
                    transition: { property: 'box-shadow', duration: '200ms', timing: 'ease' },
                    hover: { boxShadow: '0 10px 25px rgba(0, 0, 0, 0.25)' }
                },
                color: {
                    transition: { property: 'background-color, color', duration: '200ms', timing: 'ease' },
                    hover: { backgroundColor: '#e0ac7e', color: '#0f172a' }
                },
                lift: {
                    transition: { property: 'transform, box-shadow', duration: '200ms', timing: 'ease' },
                    hover: { transform: 'translateY(-4px)', boxShadow: '0 8px 20px rgba(0, 0, 0, 0.2)' }
                },
                glow: {
                    transition: { property: 'box-shadow', duration: '300ms', timing: 'ease' },
                    hover: { boxShadow: '0 0 20px rgba(224, 172, 126, 0.5)' }
                }
            };

            const presetConfig = presets[preset];
            if (presetConfig) {
                this.$wire?.call('applyHoverPreset', presetConfig);
                this.$wire?.dispatch('notify', { type: 'success', message: `Preset "${preset}" zastosowany` });
            }
        }
    }));

    // Color Picker Control
    Alpine.data('uveColorPickerControl', (initialValue, property) => ({
        hexColor: initialValue || '#000000',
        opacity: 100,
        property: property,
        isTransparent: initialValue === 'transparent',

        get displayColor() {
            if (this.isTransparent) return 'transparent';
            if (this.opacity < 100) {
                return this.hexToRgba(this.hexColor, this.opacity / 100);
            }
            return this.hexColor;
        },

        onColorInput() {
            this.isTransparent = false;
            this.emitChange();
        },

        onHexInput() {
            this.isTransparent = false;
            if (this.hexColor && !this.hexColor.startsWith('#')) {
                this.hexColor = '#' + this.hexColor;
            }
            this.emitChange();
        },

        validateHex() {
            const hex = this.hexColor.replace('#', '');
            if (!/^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/.test(hex)) {
                this.hexColor = '#000000';
            } else if (hex.length === 3) {
                this.hexColor = '#' + hex.split('').map(c => c + c).join('');
            } else {
                this.hexColor = '#' + hex.toUpperCase();
            }
        },

        setColor(hex) {
            this.hexColor = hex;
            this.isTransparent = false;
            this.opacity = 100;
            this.emitChange();
        },

        setTransparent() {
            this.isTransparent = true;
            this.emitChange();
        },

        clearColor() {
            this.hexColor = '';
            this.isTransparent = false;
            this.opacity = 100;
            this.emitChange();
        },

        hexToRgba(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },

        emitChange() {
            let value = '';
            if (this.isTransparent) {
                value = 'transparent';
            } else if (this.hexColor) {
                if (this.opacity < 100) {
                    value = this.hexToRgba(this.hexColor, this.opacity / 100);
                } else {
                    value = this.hexColor;
                }
            }
            this.$wire?.updateControlValue(this.property, value);
        }
    }));

    // Size Control
    Alpine.data('uveSizeControl', (initialValue) => {
        const parseValue = (val) => {
            if (!val || val === 'auto' || val === 'none') return { value: '', unit: '' };
            const match = val.match(/^([\d.]+)(.*)$/);
            if (match) return { value: match[1], unit: match[2] || 'px' };
            return { value: val, unit: '' };
        };

        const w = parseValue(initialValue.width);
        const h = parseValue(initialValue.height);
        const minW = parseValue(initialValue.minWidth);
        const maxW = parseValue(initialValue.maxWidth);
        const minH = parseValue(initialValue.minHeight);
        const maxH = parseValue(initialValue.maxHeight);

        return {
            width: w.value,
            widthUnit: w.unit,
            height: h.value,
            heightUnit: h.unit,
            minWidth: minW.value,
            minWidthUnit: minW.unit,
            maxWidth: maxW.value,
            maxWidthUnit: maxW.unit,
            minHeight: minH.value,
            minHeightUnit: minH.unit,
            maxHeight: maxH.value,
            maxHeightUnit: maxH.unit,
            aspectRatio: initialValue.aspectRatio || '',
            objectFit: initialValue.objectFit || 'fill',
            showAdvanced: false,

            setWidth(val) {
                if (val === 'auto' || val === 'fit-content' || val === 'max-content') {
                    this.width = val;
                    this.widthUnit = '';
                } else {
                    const parsed = this.parseValue(val);
                    this.width = parsed.value;
                    this.widthUnit = parsed.unit;
                }
                this.emitChange();
            },

            setHeight(val) {
                if (val === 'auto' || val === 'fit-content') {
                    this.height = val;
                    this.heightUnit = '';
                } else {
                    const parsed = this.parseValue(val);
                    this.height = parsed.value;
                    this.heightUnit = parsed.unit;
                }
                this.emitChange();
            },

            setMaxWidth(val) {
                if (val === 'none') {
                    this.maxWidth = '';
                    this.maxWidthUnit = '';
                } else {
                    const parsed = this.parseValue(val);
                    this.maxWidth = parsed.value;
                    this.maxWidthUnit = parsed.unit;
                }
                this.emitChange();
            },

            setAspectRatio(val) {
                this.aspectRatio = val;
                this.emitChange();
            },

            parseValue(val) {
                const match = val.match(/^([\d.]+)(.*)$/);
                if (match) return { value: match[1], unit: match[2] || 'px' };
                return { value: val, unit: '' };
            },

            formatValue(val, unit) {
                if (!val) return '';
                if (val === 'auto' || val === 'none' || val === 'fit-content' || val === 'max-content') return val;
                return val + (unit || 'px');
            },

            emitChange() {
                const value = {
                    width: this.formatValue(this.width, this.widthUnit),
                    height: this.formatValue(this.height, this.heightUnit),
                    minWidth: this.formatValue(this.minWidth, this.minWidthUnit),
                    maxWidth: this.formatValue(this.maxWidth, this.maxWidthUnit),
                    minHeight: this.formatValue(this.minHeight, this.minHeightUnit),
                    maxHeight: this.formatValue(this.maxHeight, this.maxHeightUnit),
                    aspectRatio: this.aspectRatio,
                    objectFit: this.objectFit,
                };
                this.$wire?.updateControlValue('size', value);
            }
        };
    });

    // Box Model Control
    Alpine.data('uveBoxModelControl', (initialValue, units, presets) => ({
        margin: initialValue.margin || { top: '', right: '', bottom: '', left: '', linked: true },
        padding: initialValue.padding || { top: '', right: '', bottom: '', left: '', linked: true },
        borderRadius: initialValue.borderRadius || { top: '', right: '', bottom: '', left: '', linked: true },
        units: units,
        presets: presets,
        currentUnits: {
            margin: 'px',
            padding: 'px',
            borderRadius: 'px'
        },

        toggleLinked(type) {
            this[type].linked = !this[type].linked;
            this.emitChange();
        },

        setUnit(type, unit) {
            this.currentUnits[type] = unit;
        },

        onMarginChange(side) {
            if (this.margin.linked) {
                const value = this.margin[side];
                this.margin.top = value;
                this.margin.right = value;
                this.margin.bottom = value;
                this.margin.left = value;
            }
            this.emitChange();
        },

        onPaddingChange(side) {
            if (this.padding.linked) {
                const value = this.padding[side];
                this.padding.top = value;
                this.padding.right = value;
                this.padding.bottom = value;
                this.padding.left = value;
            }
            this.emitChange();
        },

        emitChange() {
            const value = {
                margin: { ...this.margin },
                padding: { ...this.padding },
                borderRadius: { ...this.borderRadius }
            };
            this.$wire?.updateControlValue('box-model', value);
        }
    }));

    // Layout Flex Control
    Alpine.data('uveLayoutFlexControl', (initialValue) => ({
        enabled: initialValue.display === 'flex',
        flexDirection: initialValue.flexDirection || 'row',
        flexWrap: initialValue.flexWrap || 'nowrap',
        justifyContent: initialValue.justifyContent || 'flex-start',
        alignItems: initialValue.alignItems || 'stretch',
        gap: initialValue.gap || '',

        get previewStyle() {
            if (!this.enabled) return 'opacity: 0.5;';
            return `
                flex-direction: ${this.flexDirection};
                flex-wrap: ${this.flexWrap};
                justify-content: ${this.justifyContent};
                align-items: ${this.alignItems};
                gap: ${this.gap || '0.25rem'};
            `;
        },

        emitChange() {
            const value = {
                display: this.enabled ? 'flex' : 'block',
                flexDirection: this.flexDirection,
                flexWrap: this.flexWrap,
                justifyContent: this.justifyContent,
                alignItems: this.alignItems,
                gap: this.gap,
            };
            this.$wire?.updateControlValue('layout-flex', value);
        }
    }));

    // Layout Grid Control
    Alpine.data('uveLayoutGridControl', (initialValue) => ({
        enabled: initialValue.display === 'grid',
        gridTemplateColumns: initialValue.gridTemplateColumns || 'repeat(3, 1fr)',
        gridTemplateRows: initialValue.gridTemplateRows || 'auto',
        gap: initialValue.gap || '1rem',
        alignItems: initialValue.alignItems || 'stretch',
        justifyItems: initialValue.justifyItems || 'stretch',
        gridAutoFlow: initialValue.gridAutoFlow || 'row',

        get previewCells() {
            const cols = this.gridTemplateColumns;
            if (cols.includes('repeat(')) {
                const match = cols.match(/repeat\((\d+)/);
                if (match) return parseInt(match[1]) * 2;
            }
            if (cols === '1fr') return 2;
            return 6;
        },

        get previewStyle() {
            if (!this.enabled) return 'opacity: 0.5; grid-template-columns: repeat(3, 1fr);';
            return `
                grid-template-columns: ${this.gridTemplateColumns};
                grid-template-rows: ${this.gridTemplateRows};
                gap: ${this.gap || '0.25rem'};
                align-items: ${this.alignItems};
                justify-items: ${this.justifyItems};
            `;
        },

        emitChange() {
            const value = {
                display: this.enabled ? 'grid' : 'block',
                gridTemplateColumns: this.gridTemplateColumns,
                gridTemplateRows: this.gridTemplateRows,
                gap: this.gap,
                alignItems: this.alignItems,
                justifyItems: this.justifyItems,
                gridAutoFlow: this.gridAutoFlow,
            };
            this.$wire?.updateControlValue('layout-grid', value);
        }
    }));

    // Position Control
    Alpine.data('uvePositionControl', (initialValue) => ({
        position: initialValue.position || 'static',
        top: initialValue.top?.replace(/[^0-9.-]/g, '') || '',
        right: initialValue.right?.replace(/[^0-9.-]/g, '') || '',
        bottom: initialValue.bottom?.replace(/[^0-9.-]/g, '') || '',
        left: initialValue.left?.replace(/[^0-9.-]/g, '') || '',
        zIndex: initialValue.zIndex || '',
        unit: 'px',

        applyPreset(preset) {
            switch (preset) {
                case 'fill':
                    this.top = '0'; this.right = '0'; this.bottom = '0'; this.left = '0';
                    break;
                case 'center':
                    this.top = '50%'; this.left = '50%'; this.right = ''; this.bottom = '';
                    break;
                case 'top-left':
                    this.top = '0'; this.left = '0'; this.right = ''; this.bottom = '';
                    break;
                case 'top-right':
                    this.top = '0'; this.right = '0'; this.left = ''; this.bottom = '';
                    break;
                case 'bottom-left':
                    this.bottom = '0'; this.left = '0'; this.top = ''; this.right = '';
                    break;
                case 'bottom-right':
                    this.bottom = '0'; this.right = '0'; this.top = ''; this.left = '';
                    break;
            }
            this.emitChange();
        },

        formatValue(val) {
            if (!val || val === 'auto') return 'auto';
            if (/[a-z%]/.test(val)) return val;
            return val + this.unit;
        },

        emitChange() {
            const value = {
                position: this.position,
                top: this.top ? this.formatValue(this.top) : '',
                right: this.right ? this.formatValue(this.right) : '',
                bottom: this.bottom ? this.formatValue(this.bottom) : '',
                left: this.left ? this.formatValue(this.left) : '',
                zIndex: this.zIndex,
            };
            this.$wire?.updateControlValue('position', value);
        }
    }));

    // Transform Control
    Alpine.data('uveTransformControl', (initialValue) => ({
        rotate: parseInt(initialValue.rotate) || 0,
        scaleX: parseFloat(initialValue.scaleX) || 1,
        scaleY: parseFloat(initialValue.scaleY) || 1,
        scaleLinked: true,
        translateX: initialValue.translateX?.replace(/[^0-9.-]/g, '') || '0',
        translateY: initialValue.translateY?.replace(/[^0-9.-]/g, '') || '0',
        translateUnit: initialValue.translateUnit || 'px',
        skewX: parseInt(initialValue.skewX) || 0,
        skewY: parseInt(initialValue.skewY) || 0,
        transformOrigin: initialValue.transformOrigin || 'center center',

        origins: [
            { value: 'left top', label: 'Lewo gora' },
            { value: 'center top', label: 'Srodek gora' },
            { value: 'right top', label: 'Prawo gora' },
            { value: 'left center', label: 'Lewo srodek' },
            { value: 'center center', label: 'Srodek' },
            { value: 'right center', label: 'Prawo srodek' },
            { value: 'left bottom', label: 'Lewo dol' },
            { value: 'center bottom', label: 'Srodek dol' },
            { value: 'right bottom', label: 'Prawo dol' },
        ],

        get previewStyle() {
            const transforms = [];
            if (this.rotate !== 0) transforms.push(`rotate(${this.rotate}deg)`);
            if (this.scaleX !== 1 || this.scaleY !== 1) transforms.push(`scale(${this.scaleX}, ${this.scaleY})`);
            if (this.translateX !== '0' || this.translateY !== '0') {
                transforms.push(`translate(${this.translateX}${this.translateUnit}, ${this.translateY}${this.translateUnit})`);
            }
            if (this.skewX !== 0 || this.skewY !== 0) transforms.push(`skew(${this.skewX}deg, ${this.skewY}deg)`);
            return `
                transform: ${transforms.length > 0 ? transforms.join(' ') : 'none'};
                transform-origin: ${this.transformOrigin};
            `;
        },

        onScaleChange(axis) {
            if (this.scaleLinked) {
                if (axis === 'x') this.scaleY = this.scaleX;
                else this.scaleX = this.scaleY;
            }
            this.emitChange();
        },

        resetTransform() {
            this.rotate = 0;
            this.scaleX = 1;
            this.scaleY = 1;
            this.translateX = '0';
            this.translateY = '0';
            this.skewX = 0;
            this.skewY = 0;
            this.transformOrigin = 'center center';
            this.emitChange();
        },

        emitChange() {
            const transforms = [];
            if (this.rotate !== 0) transforms.push(`rotate(${this.rotate}deg)`);
            if (this.scaleX !== 1 || this.scaleY !== 1) transforms.push(`scale(${this.scaleX}, ${this.scaleY})`);
            if (this.translateX !== '0' || this.translateY !== '0') {
                transforms.push(`translate(${this.translateX}${this.translateUnit}, ${this.translateY}${this.translateUnit})`);
            }
            if (this.skewX !== 0 || this.skewY !== 0) transforms.push(`skew(${this.skewX}deg, ${this.skewY}deg)`);

            const value = {
                transform: transforms.length > 0 ? transforms.join(' ') : 'none',
                transformOrigin: this.transformOrigin,
                rotate: this.rotate,
                scaleX: this.scaleX,
                scaleY: this.scaleY,
                translateX: this.translateX,
                translateY: this.translateY,
                translateUnit: this.translateUnit,
                skewX: this.skewX,
                skewY: this.skewY,
            };
            this.$wire?.updateControlValue('transform', value);
        }
    }));

    // Effects Control
    Alpine.data('uveEffectsControl', (initialValue) => ({
        opacity: parseInt(initialValue.opacity) || 100,
        boxShadows: initialValue.boxShadows || [
            { x: 0, y: 4, blur: 6, spread: -1, color: 'rgba(0, 0, 0, 0.1)', inset: false }
        ],
        textShadowEnabled: !!initialValue.textShadow,
        textShadow: initialValue.textShadow || { x: 1, y: 1, blur: 2, color: 'rgba(0, 0, 0, 0.3)' },

        get previewStyle() {
            let style = `opacity: ${this.opacity / 100};`;
            const boxShadowStr = this.boxShadows.map(s =>
                `${s.inset ? 'inset ' : ''}${s.x}px ${s.y}px ${s.blur}px ${s.spread}px ${s.color}`
            ).join(', ');
            if (boxShadowStr) style += `box-shadow: ${boxShadowStr};`;
            if (this.textShadowEnabled) {
                style += `text-shadow: ${this.textShadow.x}px ${this.textShadow.y}px ${this.textShadow.blur}px ${this.textShadow.color};`;
            }
            return style;
        },

        addBoxShadow() {
            if (this.boxShadows.length >= 3) return;
            this.boxShadows.push({ x: 0, y: 4, blur: 6, spread: 0, color: 'rgba(0, 0, 0, 0.2)', inset: false });
            this.emitChange();
        },

        removeBoxShadow(index) {
            this.boxShadows.splice(index, 1);
            this.emitChange();
        },

        applyBoxShadowPreset(value) {
            if (value === 'none') this.boxShadows = [];
            else this.boxShadows = [{ x: 0, y: 4, blur: 6, spread: -1, color: 'rgba(0, 0, 0, 0.1)', inset: false }];
            this.emitChange();
        },

        emitChange() {
            const boxShadowCss = this.boxShadows.length > 0
                ? this.boxShadows.map(s =>
                    `${s.inset ? 'inset ' : ''}${s.x}px ${s.y}px ${s.blur}px ${s.spread}px ${s.color}`
                ).join(', ')
                : 'none';
            const textShadowCss = this.textShadowEnabled
                ? `${this.textShadow.x}px ${this.textShadow.y}px ${this.textShadow.blur}px ${this.textShadow.color}`
                : 'none';
            const value = {
                opacity: this.opacity + '%',
                boxShadow: boxShadowCss,
                textShadow: textShadowCss,
                boxShadows: this.boxShadows,
            };
            this.$wire?.updateControlValue('effects', value);
        }
    }));

    // Background Control
    // NOTE: Background control receives ONLY CSS background-image/background-color
    // Nested <img> elements are handled by image-settings control, NOT background control
    // IMG ≠ Background Image - these are conceptually different!
    Alpine.data('uveBackgroundControl', (initialValue) => ({
        activeTab: 'color',
        backgroundColor: initialValue.backgroundColor || '',
        // FIX #13b: Separate storage for image URL vs gradient
        backgroundImage: '',
        backgroundGradient: '',
        backgroundSize: initialValue.backgroundSize || 'cover',
        backgroundPosition: initialValue.backgroundPosition || 'center center',
        backgroundRepeat: initialValue.backgroundRepeat || 'no-repeat',
        backgroundAttachment: initialValue.backgroundAttachment || 'scroll',
        showFullPreview: false,

        // FIX #13b: Helper to detect CSS gradient values
        isGradient(value) {
            if (!value) return false;
            const gradientPatterns = [
                'linear-gradient',
                'radial-gradient',
                'conic-gradient',
                'repeating-linear-gradient',
                'repeating-radial-gradient',
                'repeating-conic-gradient'
            ];
            return gradientPatterns.some(pattern => value.includes(pattern));
        },

        // FIX #13b: Parse background-image value (URL or gradient)
        parseBackgroundImage(rawValue) {
            if (!rawValue) return { image: '', gradient: '' };

            // Check if it's a gradient
            if (this.isGradient(rawValue)) {
                return { image: '', gradient: rawValue };
            }

            // It's a URL - clean the url() wrapper
            const cleanUrl = rawValue.replace(/url\(['"]?|['"]?\)/g, '');
            return { image: cleanUrl, gradient: '' };
        },

        // FIX #12: Listen for Livewire event to update background when element changes
        // This is needed because wire:ignore.self prevents Alpine from reinitializing
        init() {
            // FIX #13b: Parse initial value for gradient vs URL
            const initialBg = initialValue.backgroundImage || '';
            const parsed = this.parseBackgroundImage(initialBg);
            this.backgroundImage = parsed.image;
            this.backgroundGradient = parsed.gradient;

            // Set initial active tab based on content
            if (parsed.gradient) {
                this.activeTab = 'gradient';
            } else if (parsed.image) {
                this.activeTab = 'image';
            } else if (this.backgroundColor) {
                this.activeTab = 'color';
            }

            Livewire.on('uve-background-updated', (data) => {
                // Handle both array format and object format
                const d = Array.isArray(data) ? data[0] : data;
                if (d) {
                    // FIX #13b: Parse background-image for gradient vs URL
                    const bgValue = d.backgroundImage || '';
                    const parsed = this.parseBackgroundImage(bgValue);

                    // FIX #14d: ALWAYS update - event is now dispatched for EVERY element selection
                    // This ensures proper RESET when switching from element with gradient to element without
                    // Previous FIX #13c logic prevented reset - REMOVED
                    this.backgroundImage = parsed.image;
                    this.backgroundGradient = parsed.gradient;

                    // FIX #14d: Also reset/update backgroundColor (may be empty for reset)
                    this.backgroundColor = d.backgroundColor || '';

                    this.backgroundSize = d.backgroundSize || 'cover';
                    this.backgroundPosition = d.backgroundPosition || 'center center';
                    this.backgroundRepeat = d.backgroundRepeat || 'no-repeat';
                    this.backgroundAttachment = d.backgroundAttachment || 'scroll';

                    // FIX #13b: Switch to appropriate tab based on content
                    if (this.backgroundGradient) {
                        this.activeTab = 'gradient';
                    } else if (this.backgroundImage) {
                        this.activeTab = 'image';
                    } else if (this.backgroundColor) {
                        this.activeTab = 'color';
                    } else {
                        // FIX #14d: Default to color tab when no background
                        this.activeTab = 'color';
                    }

                    // FIX #14d: Dispatch event for gradient editor sync
                    // This notifies the nested uveGradientEditorInline component
                    window.dispatchEvent(new CustomEvent('uve-gradient-sync', {
                        detail: { gradient: parsed.gradient }
                    }));
                }
            });

            // FIX #14: Listen for tab switch command from Livewire
            Livewire.on('uve-switch-background-tab', (data) => {
                const d = Array.isArray(data) ? data[0] : data;
                if (d && d.tab) {
                    this.activeTab = d.tab;
                }
            });
        },

        positions: [
            { value: 'left top', label: 'Lewo gora' },
            { value: 'center top', label: 'Srodek gora' },
            { value: 'right top', label: 'Prawo gora' },
            { value: 'left center', label: 'Lewo srodek' },
            { value: 'center center', label: 'Srodek' },
            { value: 'right center', label: 'Prawo srodek' },
            { value: 'left bottom', label: 'Lewo dol' },
            { value: 'center bottom', label: 'Srodek dol' },
            { value: 'right bottom', label: 'Prawo dol' },
        ],

        // FIX #13b: Include gradient in hasBackground check
        get hasBackground() {
            return this.backgroundColor || this.backgroundImage || this.backgroundGradient;
        },

        // FIX #13b: Handle gradients properly in preview (don't wrap in url())
        get previewStyle() {
            let style = '';
            if (this.backgroundColor) style += `background-color: ${this.backgroundColor};`;

            // FIX #13b: Gradient gets used directly, URL gets wrapped
            if (this.backgroundGradient) {
                // Gradient - use directly without url() wrapper
                style += `background-image: ${this.backgroundGradient};`;
            } else if (this.backgroundImage) {
                // URL - wrap in url()
                style += `background-image: url('${this.backgroundImage}');`;
                style += `background-size: ${this.backgroundSize};`;
                style += `background-position: ${this.backgroundPosition};`;
                style += `background-repeat: ${this.backgroundRepeat};`;
            }
            return style;
        },

        setColor(color) {
            this.backgroundColor = color;
            this.emitChange();
        },

        clearBackground() {
            this.backgroundColor = '';
            this.backgroundImage = '';
            this.backgroundGradient = '';  // FIX #13b: Clear gradient too
            this.backgroundSize = 'cover';
            this.backgroundPosition = 'center center';
            this.backgroundRepeat = 'no-repeat';
            this.backgroundAttachment = 'scroll';
            this.emitChange();
        },

        // FIX #13b: Handle gradients properly when emitting changes
        emitChange() {
            let bgImageValue = '';

            // FIX #13b: Gradient used directly, URL wrapped in url()
            if (this.backgroundGradient) {
                bgImageValue = this.backgroundGradient;  // Gradient - direct
            } else if (this.backgroundImage) {
                bgImageValue = `url('${this.backgroundImage}')`;  // URL - wrapped
            }

            const value = {
                backgroundColor: this.backgroundColor,
                backgroundImage: bgImageValue,
                backgroundSize: this.backgroundSize,
                backgroundPosition: this.backgroundPosition,
                backgroundRepeat: this.backgroundRepeat,
                backgroundAttachment: this.backgroundAttachment,
            };
            this.$wire?.updateControlValue('background', value);
        }
    }));

    // FIX #14: Inline Gradient Editor (nested inside Background Control)
    // This component is used within the Gradient tab of uveBackgroundControl
    Alpine.data('uveGradientEditorInline', () => ({
        gradientType: 'linear',
        angle: 180,
        colorStops: [
            { color: '#f6f6f6', position: 0 },
            { color: '#ef8248', position: 100 }
        ],
        selectedStop: 0,
        draggingIndex: null,
        barElement: null,

        // Presets configuration
        presets: {
            brand: { type: 'linear', angle: 135, stops: [{ color: '#e0ac7e', position: 0 }, { color: '#d1975a', position: 50 }, { color: '#c08449', position: 100 }] },
            cover: { type: 'linear', angle: 180, stops: [{ color: '#f6f6f6', position: 70 }, { color: '#ef8248', position: 70 }] },
            dark: { type: 'linear', angle: 180, stops: [{ color: '#1a1a1a', position: 0 }, { color: '#333333', position: 100 }] },
            light: { type: 'linear', angle: 180, stops: [{ color: '#ffffff', position: 0 }, { color: '#f6f6f6', position: 100 }] },
            sunset: { type: 'linear', angle: 135, stops: [{ color: '#f6f6f6', position: 0 }, { color: '#ef8248', position: 100 }] },
            ocean: { type: 'linear', angle: 135, stops: [{ color: '#667eea', position: 0 }, { color: '#764ba2', position: 100 }] }
        },

        init() {
            // FIX #14d: Try to parse initial gradient from parent backgroundGradient
            // Use _x_dataStack[0] (Alpine 3.x) instead of __x (Alpine 2.x)
            const parentEl = this.$el.closest('[x-data*="uveBackgroundControl"]');
            if (parentEl && parentEl._x_dataStack && parentEl._x_dataStack[0]) {
                const parentGradient = parentEl._x_dataStack[0].backgroundGradient;
                if (parentGradient) {
                    this.parseGradient(parentGradient);
                }
            }

            // FIX #14d: Listen for gradient sync event from parent uveBackgroundControl
            // This replaces the broken $watch approach that used old Alpine 2.x syntax
            window.addEventListener('uve-gradient-sync', (event) => {
                const gradient = event.detail?.gradient;
                if (gradient && gradient !== this.gradientCss) {
                    this.parseGradient(gradient);
                } else if (!gradient) {
                    // FIX #14d: Reset to default when no gradient (switching to element without gradient)
                    this.resetToDefault();
                }
            });

            // FIX #14b: Set up drag event listeners for color stop markers
            document.addEventListener('mousemove', (e) => this.onDrag(e));
            document.addEventListener('mouseup', () => this.stopDrag());
            document.addEventListener('touchmove', (e) => this.onDrag(e));
            document.addEventListener('touchend', () => this.stopDrag());

            // FIX #14c: Cache bar element reference after DOM is ready
            this.$nextTick(() => {
                this.barElement = this.$el.querySelector('.uve-gradient-stops-bar');
            });
        },

        // FIX #14d: Reset gradient editor to default state
        resetToDefault() {
            this.gradientType = 'linear';
            this.angle = 180;
            this.colorStops = [
                { color: '#f6f6f6', position: 0 },
                { color: '#ef8248', position: 100 }
            ];
            this.selectedStop = 0;
        },

        // FIX #14b: Start dragging a color stop marker
        startDrag(index, event) {
            event.preventDefault();
            this.draggingIndex = index;
            this.selectedStop = index;
            // FIX #14c: Ensure barElement is set (fallback if init cache failed)
            if (!this.barElement) {
                this.barElement = this.$el.querySelector('.uve-gradient-stops-bar')
                    || document.querySelector('.uve-gradient-stops-bar');
            }
        },

        // FIX #14b: Handle drag movement
        onDrag(event) {
            if (this.draggingIndex === null || !this.barElement) return;

            const bar = this.barElement;
            const rect = bar.getBoundingClientRect();

            // Get X position (handle both mouse and touch)
            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const relativeX = clientX - rect.left;
            const barWidth = rect.width;

            // Calculate position as percentage (0-100)
            let newPosition = Math.round((relativeX / barWidth) * 100);
            newPosition = Math.max(0, Math.min(100, newPosition));

            // Update the color stop position
            this.colorStops[this.draggingIndex].position = newPosition;
        },

        // FIX #14b: Stop dragging
        stopDrag() {
            if (this.draggingIndex !== null) {
                this.updateParent();
                this.draggingIndex = null;
            }
        },

        // Generate CSS gradient string
        get gradientCss() {
            const sortedStops = [...this.colorStops].sort((a, b) => a.position - b.position);
            const stopsStr = sortedStops.map(s => `${s.color} ${s.position}%`).join(', ');

            if (this.gradientType === 'linear') {
                return `linear-gradient(${this.angle}deg, ${stopsStr})`;
            } else {
                return `radial-gradient(circle, ${stopsStr})`;
            }
        },

        // Parse CSS gradient string into components
        parseGradient(cssGradient) {
            if (!cssGradient) return;

            // Detect type
            if (cssGradient.startsWith('radial-gradient')) {
                this.gradientType = 'radial';
            } else {
                this.gradientType = 'linear';
            }

            // Extract angle for linear gradients
            const angleMatch = cssGradient.match(/linear-gradient\s*\(\s*(\d+)deg/);
            if (angleMatch) {
                this.angle = parseInt(angleMatch[1], 10);
            }

            // Extract color stops
            // Match patterns like: #ffffff 0%, rgb(246, 246, 246) 70%
            const colorStopRegex = /(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|[a-z]+)\s*(\d+)%/g;
            const stops = [];
            let match;

            while ((match = colorStopRegex.exec(cssGradient)) !== null) {
                stops.push({
                    color: match[1],
                    position: parseInt(match[2], 10)
                });
            }

            if (stops.length >= 2) {
                this.colorStops = stops;
            }
        },

        // Add a new color stop
        addStop() {
            if (this.colorStops.length >= 5) return;

            // Find the largest gap and add stop there
            const sorted = [...this.colorStops].sort((a, b) => a.position - b.position);
            let maxGap = 0;
            let gapStart = 0;

            for (let i = 0; i < sorted.length - 1; i++) {
                const gap = sorted[i + 1].position - sorted[i].position;
                if (gap > maxGap) {
                    maxGap = gap;
                    gapStart = sorted[i].position;
                }
            }

            const newPosition = Math.round(gapStart + maxGap / 2);
            this.colorStops.push({ color: '#888888', position: newPosition });
            this.selectedStop = this.colorStops.length - 1;
            this.updateParent();
        },

        // Remove a color stop
        removeStop(index) {
            if (this.colorStops.length <= 2) return;
            this.colorStops.splice(index, 1);
            if (this.selectedStop >= this.colorStops.length) {
                this.selectedStop = this.colorStops.length - 1;
            }
            this.updateParent();
        },

        // Apply a preset gradient
        applyPreset(presetName) {
            const preset = this.presets[presetName];
            if (!preset) return;

            this.gradientType = preset.type;
            this.angle = preset.angle;
            this.colorStops = JSON.parse(JSON.stringify(preset.stops));
            this.selectedStop = 0;
            this.updateParent();
        },

        // Update parent component's backgroundGradient
        updateParent() {
            // FIX #14d: Find the parent uveBackgroundControl component
            // Use _x_dataStack[0] (Alpine 3.x) instead of __x (Alpine 2.x)
            const parentEl = this.$el.closest('[x-data*="uveBackgroundControl"]');
            if (parentEl && parentEl._x_dataStack && parentEl._x_dataStack[0]) {
                const parentData = parentEl._x_dataStack[0];
                parentData.backgroundGradient = this.gradientCss;
                // Trigger parent's emitChange to sync with Livewire
                if (typeof parentData.emitChange === 'function') {
                    parentData.emitChange();
                }
            }
        }
    }));

    // Border Control
    Alpine.data('uveBorderControl', (initialValue) => ({
        width: {
            all: initialValue.width || '',
            top: initialValue.borderTopWidth || '',
            right: initialValue.borderRightWidth || '',
            bottom: initialValue.borderBottomWidth || '',
            left: initialValue.borderLeftWidth || '',
        },
        widthLinked: true,
        borderStyle: initialValue.style || 'solid',
        borderColor: initialValue.color || '#475569',
        radius: {
            all: initialValue.radius || '',
            topLeft: initialValue.borderTopLeftRadius || '',
            topRight: initialValue.borderTopRightRadius || '',
            bottomLeft: initialValue.borderBottomLeftRadius || '',
            bottomRight: initialValue.borderBottomRightRadius || '',
        },
        radiusLinked: true,

        get previewStyle() {
            const w = this.widthLinked ? this.width.all : `${this.width.top || 0} ${this.width.right || 0} ${this.width.bottom || 0} ${this.width.left || 0}`;
            const r = this.radiusLinked ? this.radius.all : `${this.radius.topLeft || 0} ${this.radius.topRight || 0} ${this.radius.bottomRight || 0} ${this.radius.bottomLeft || 0}`;
            return `
                border-width: ${w || '1px'};
                border-style: ${this.borderStyle};
                border-color: ${this.borderColor};
                border-radius: ${r || '0'};
            `;
        },

        toggleLinkedWidth() {
            this.widthLinked = !this.widthLinked;
            if (this.widthLinked && this.width.top) this.width.all = this.width.top;
        },

        toggleLinkedRadius() {
            this.radiusLinked = !this.radiusLinked;
            if (this.radiusLinked && this.radius.topLeft) this.radius.all = this.radius.topLeft;
        },

        onWidthAllChange() {
            this.width.top = this.width.all;
            this.width.right = this.width.all;
            this.width.bottom = this.width.all;
            this.width.left = this.width.all;
            this.emitChange();
        },

        onRadiusAllChange() {
            this.radius.topLeft = this.radius.all;
            this.radius.topRight = this.radius.all;
            this.radius.bottomLeft = this.radius.all;
            this.radius.bottomRight = this.radius.all;
            this.emitChange();
        },

        setRadiusPreset(value) {
            this.radius.all = value;
            this.radiusLinked = true;
            this.onRadiusAllChange();
        },

        emitChange() {
            const value = {
                width: this.widthLinked ? this.width.all : null,
                borderTopWidth: !this.widthLinked ? this.width.top : null,
                borderRightWidth: !this.widthLinked ? this.width.right : null,
                borderBottomWidth: !this.widthLinked ? this.width.bottom : null,
                borderLeftWidth: !this.widthLinked ? this.width.left : null,
                style: this.borderStyle,
                color: this.borderColor,
                radius: this.radiusLinked ? this.radius.all : null,
                borderTopLeftRadius: !this.radiusLinked ? this.radius.topLeft : null,
                borderTopRightRadius: !this.radiusLinked ? this.radius.topRight : null,
                borderBottomLeftRadius: !this.radiusLinked ? this.radius.bottomLeft : null,
                borderBottomRightRadius: !this.radiusLinked ? this.radius.bottomRight : null,
            };
            this.$wire?.updateControlValue('border', value);
        }
    }));

    // Image Settings Control
    Alpine.data('uveImageSettingsControl', (config) => ({
        imageUrl: config.imageUrl || '',
        size: config.size || 'full',
        customWidth: config.customWidth || '100%',
        customHeight: config.customHeight || 'auto',
        alignment: config.alignment || 'center',
        objectFit: config.objectFit || 'contain',
        borderRadius: config.borderRadius || '0',
        shadow: config.shadow || false,
        lightbox: config.lightbox || false,
        lazyLoad: config.lazyLoad ?? true,
        showFullPreview: false,
        _clientSeq: 0,
        _emitTimer: null,
        _cleanupListeners: null,

        // CRITICAL FIX: Listen for image URL updates from Media Picker
        init() {
            // Livewire 3.x: dispatch() sends browser events, listen with Livewire.on()
            const handler = (data) => {
                // In Livewire 3.x data comes as array or object
                const eventData = Array.isArray(data) ? data[0] : data;
                const newUrl = eventData?.url;

                if (newUrl !== undefined) {
                    console.log('[UVE ImageSettings] Received uve-image-url-updated, new URL:', newUrl);
                    this.imageUrl = newUrl;

                    // Update the preview in Property Panel
                    // Note: No need to emitChange() here - the HTML is already updated by applyMediaToElement
                }
            };

            // Register listener
            Livewire.on('uve-image-url-updated', handler);

            // Store cleanup function for destroy
            this._cleanupListeners = () => {
                // Livewire.off is not available in all versions, use try-catch
                try {
                    if (typeof Livewire.off === 'function') {
                        Livewire.off('uve-image-url-updated', handler);
                    }
                } catch (e) {
                    // Ignore cleanup errors
                }
            };
        },

        destroy() {
            if (this._cleanupListeners) {
                this._cleanupListeners();
            }
        },

        setSize(newSize) {
            this.size = newSize;
            this.emitChange();
        },

        setAlignment(newAlignment) {
            this.alignment = newAlignment;
            this.emitChange();
        },

        setBorderRadius(newRadius) {
            this.borderRadius = newRadius;
            this.emitChange();
        },

        /**
         * FIX FAZA 3: Apply external URL to element
         * Called from "Zastosuj" button in image-settings control
         */
        applyExternalUrl() {
            if (!this.imageUrl || this.imageUrl.trim() === '') {
                console.warn('[UVE ImageSettings] applyExternalUrl: No URL provided');
                return;
            }

            console.log('[UVE ImageSettings] Applying external URL:', this.imageUrl);

            // Call Livewire method to apply URL to selected element
            this.$wire?.call('applyExternalImageUrl', this.imageUrl);
        },

        _nextClientSeq() {
            // Monotonic sequence shared across re-initializations.
            window.__uveClientSeq = Math.max(window.__uveClientSeq || 0, Date.now()) + 1;
            this._clientSeq = window.__uveClientSeq;
            return this._clientSeq;
        },

        _buildPreviewCss() {
            const sizePresets = {
                full: '100%',
                large: '75%',
                medium: '50%',
                small: '25%'
            };

            let width = sizePresets[this.size] || '100%';
            let height = 'auto';

            if (this.size === 'custom') {
                width = (this.customWidth || '').trim() || '100%';
                const h = (this.customHeight || '').trim();
                height = (h && h.toLowerCase() !== 'auto') ? h : 'auto';
            }

            const css = {
                width,
                height,
                display: 'block'
            };

            if (this.alignment === 'center') {
                css.marginLeft = 'auto';
                css.marginRight = 'auto';
            } else if (this.alignment === 'right') {
                css.marginLeft = 'auto';
                css.marginRight = '0';
            } else {
                css.marginLeft = '0';
                css.marginRight = 'auto';
            }

            if (this.objectFit) {
                css.objectFit = this.objectFit;
            }

            css.borderRadius = this.borderRadius || '0';
            css.boxShadow = this.shadow ? '0 4px 12px rgba(0, 0, 0, 0.15)' : 'none';

            return css;
        },

        _applyInstantPreview() {
            const elementId = this.$wire?.get('selectedElementId');
            if (!elementId || !window.uveApplyStyles) return;

            window.uveApplyStyles({
                elementId,
                controlId: 'image-settings',
                clientSeq: this._clientSeq,
                styles: this._buildPreviewCss(),
            });
        },

        emitChange() {
            // 1) Bump seq + apply to canvas immediately (no mismatch while Livewire queues requests)
            this._nextClientSeq();
            this._applyInstantPreview();

            // 2) Debounce server update to avoid request backlog
            clearTimeout(this._emitTimer);
            this._emitTimer = setTimeout(() => this._flushChange(), 150);
        },

        _flushChange() {
            const value = {
                imageUrl: this.imageUrl,
                src: this.imageUrl, // Also include as 'src' for IMG element attribute
                size: this.size,
                customWidth: this.customWidth,
                customHeight: this.customHeight,
                alignment: this.alignment,
                objectFit: this.objectFit,
                borderRadius: this.borderRadius,
                shadow: this.shadow,
                lightbox: this.lightbox,
                lazyLoad: this.lazyLoad,
                _clientSeq: this._clientSeq,
            };

            console.log('[UVE Image Settings] Emitting change:', value);
            this.$wire?.call('updateControlValue', 'image-settings', value);
        }
    }));
    // Transition Control
    Alpine.data('uveTransitionControl', (initialValue, timingFunctions, properties) => ({
        duration: initialValue.duration || 300,
        timing: initialValue.timing || 'ease',
        delay: initialValue.delay || 0,
        selectedProperties: initialValue.properties || ['all'],
        bezier: { x1: 0.4, y1: 0, x2: 0.2, y2: 1 },
        previewHover: false,

        init() {
            // Parse bezier if timing is cubic-bezier
            if (this.timing && this.timing.startsWith('cubic-bezier')) {
                const match = this.timing.match(/cubic-bezier\(([\d.-]+),\s*([\d.-]+),\s*([\d.-]+),\s*([\d.-]+)\)/);
                if (match) {
                    this.bezier = {
                        x1: parseFloat(match[1]),
                        y1: parseFloat(match[2]),
                        x2: parseFloat(match[3]),
                        y2: parseFloat(match[4])
                    };
                }
            }
            this.$nextTick(() => this.drawBezierCurve());
        },

        applyPreset(preset) {
            this.duration = preset.duration;
            this.delay = preset.delay;
            if (preset.timing.startsWith('cubic-bezier')) {
                this.timing = 'cubic-bezier';
                const match = preset.timing.match(/cubic-bezier\(([\d.-]+),\s*([\d.-]+),\s*([\d.-]+),\s*([\d.-]+)\)/);
                if (match) {
                    this.bezier = {
                        x1: parseFloat(match[1]),
                        y1: parseFloat(match[2]),
                        x2: parseFloat(match[3]),
                        y2: parseFloat(match[4])
                    };
                }
            } else {
                this.timing = preset.timing;
            }
            this.emitChange();
        },

        isPresetActive(preset) {
            return this.duration === preset.duration &&
                   this.delay === preset.delay &&
                   this.getTimingValue() === (preset.timing.startsWith('cubic-bezier') ? preset.timing : preset.timing);
        },

        onTimingChange() {
            if (this.timing === 'cubic-bezier') {
                this.$nextTick(() => this.drawBezierCurve());
            }
            this.emitChange();
        },

        updateBezier() {
            this.drawBezierCurve();
            this.emitChange();
        },

        setBezier(x1, y1, x2, y2) {
            this.bezier = { x1, y1, x2, y2 };
            this.drawBezierCurve();
            this.emitChange();
        },

        drawBezierCurve() {
            const canvas = this.$refs.bezierCanvas;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const w = canvas.width;
            const h = canvas.height;
            const padding = 10;
            const size = w - padding * 2;

            ctx.clearRect(0, 0, w, h);

            // Grid
            ctx.strokeStyle = '#334155';
            ctx.lineWidth = 1;
            ctx.beginPath();
            for (let i = 0; i <= 4; i++) {
                const x = padding + (size / 4) * i;
                const y = padding + (size / 4) * i;
                ctx.moveTo(x, padding);
                ctx.lineTo(x, h - padding);
                ctx.moveTo(padding, y);
                ctx.lineTo(w - padding, y);
            }
            ctx.stroke();

            // Control points and lines
            const p0 = { x: padding, y: h - padding };
            const p1 = { x: padding + this.bezier.x1 * size, y: h - padding - this.bezier.y1 * size };
            const p2 = { x: padding + this.bezier.x2 * size, y: h - padding - this.bezier.y2 * size };
            const p3 = { x: w - padding, y: padding };

            // Control lines
            ctx.strokeStyle = '#475569';
            ctx.lineWidth = 1;
            ctx.setLineDash([3, 3]);
            ctx.beginPath();
            ctx.moveTo(p0.x, p0.y);
            ctx.lineTo(p1.x, p1.y);
            ctx.moveTo(p3.x, p3.y);
            ctx.lineTo(p2.x, p2.y);
            ctx.stroke();
            ctx.setLineDash([]);

            // Bezier curve
            ctx.strokeStyle = '#e0ac7e';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(p0.x, p0.y);
            ctx.bezierCurveTo(p1.x, p1.y, p2.x, p2.y, p3.x, p3.y);
            ctx.stroke();

            // Control points
            ctx.fillStyle = '#3b82f6';
            ctx.beginPath();
            ctx.arc(p1.x, p1.y, 5, 0, Math.PI * 2);
            ctx.fill();
            ctx.beginPath();
            ctx.arc(p2.x, p2.y, 5, 0, Math.PI * 2);
            ctx.fill();
        },

        toggleProperty(prop) {
            if (prop === 'all') {
                this.selectedProperties = ['all'];
            } else {
                this.selectedProperties = this.selectedProperties.filter(p => p !== 'all');
                if (this.selectedProperties.includes(prop)) {
                    this.selectedProperties = this.selectedProperties.filter(p => p !== prop);
                } else {
                    this.selectedProperties.push(prop);
                }
                if (this.selectedProperties.length === 0) {
                    this.selectedProperties = ['all'];
                }
            }
            this.emitChange();
        },

        getTimingValue() {
            if (this.timing === 'cubic-bezier') {
                return `cubic-bezier(${this.bezier.x1}, ${this.bezier.y1}, ${this.bezier.x2}, ${this.bezier.y2})`;
            }
            return this.timing;
        },

        getTransitionCss() {
            const props = this.selectedProperties.join(', ');
            const timing = this.getTimingValue();
            return `transition: ${props} ${this.duration}ms ${timing} ${this.delay}ms;`;
        },

        getPreviewStyle() {
            const timing = this.getTimingValue();
            const props = this.selectedProperties.join(', ');
            let style = `transition: ${props} ${this.duration}ms ${timing} ${this.delay}ms;`;
            if (this.previewHover) {
                style += ' transform: scale(1.1); background: #e0ac7e;';
            }
            return style;
        },

        copyCss() {
            navigator.clipboard.writeText(this.getTransitionCss());
            this.$wire?.dispatch('notify', { type: 'success', message: 'CSS skopiowany' });
        },

        emitChange() {
            const value = {
                duration: this.duration,
                timing: this.getTimingValue(),
                delay: this.delay,
                properties: this.selectedProperties
            };
            this.$wire?.updateControlValue('transition', value);
        }
    }));

    // =====================================================
    // ETAP_08.5: ERP SYNC STATUS TRACKER COMPONENT
    // =====================================================
    Alpine.data('erpSyncStatusTracker', (activeErpJobStatus, activeErpJobType, erpJobResult, erpJobCreatedAt) => ({
        activeErpJobStatus: activeErpJobStatus,
        activeErpJobType: activeErpJobType,
        erpJobResult: erpJobResult,
        erpJobCreatedAt: erpJobCreatedAt,
        progress: 0,
        remainingSeconds: 0,
        showCompletionStatus: false,
        completionResult: null,
        completionTimeout: null,
        estimatedDuration: 60, // seconds (max ERP JOB execution time)

        init() {
            const jobKey = this.erpJobCreatedAt || 'no-erp-job';
            window._erpSyncCompletionShown = window._erpSyncCompletionShown || {};

            this.$watch('activeErpJobStatus', (newStatus, oldStatus) => {
                console.log('[ERPSyncStatus] activeErpJobStatus changed:', oldStatus, '->', newStatus);

                if (newStatus === 'completed' || newStatus === 'failed') {
                    const currentJobKey = this.erpJobCreatedAt || 'no-erp-job';
                    if (!window._erpSyncCompletionShown[currentJobKey]) {
                        window._erpSyncCompletionShown[currentJobKey] = true;
                        this.handleJobCompletion(newStatus);
                        const keys = Object.keys(window._erpSyncCompletionShown);
                        if (keys.length > 10) {
                            delete window._erpSyncCompletionShown[keys[0]];
                        }
                    }
                } else if (newStatus === 'pending' || newStatus === 'running') {
                    this.startProgressTracking();
                } else {
                    this.resetState();
                }
            });

            // Initialize if job is already running on mount
            if (this.activeErpJobStatus === 'pending' || this.activeErpJobStatus === 'running') {
                this.startProgressTracking();
            }
        },

        get isJobRunning() {
            return this.activeErpJobStatus === 'pending' || this.activeErpJobStatus === 'running';
        },

        get statusText() {
            if (this.activeErpJobType === 'sync') {
                return 'Wysylanie do ERP...';
            } else if (this.activeErpJobType === 'pull') {
                return 'Pobieranie z ERP...';
            }
            return 'Przetwarzanie...';
        },

        resetState() {
            this.showCompletionStatus = false;
            this.completionResult = null;
            this.progress = 0;
            this.remainingSeconds = 0;
            if (this.completionTimeout) {
                clearTimeout(this.completionTimeout);
                this.completionTimeout = null;
            }
        },

        startProgressTracking() {
            this.showCompletionStatus = false;
            this.completionResult = null;

            if (this.completionTimeout) {
                clearTimeout(this.completionTimeout);
                this.completionTimeout = null;
            }

            const startTime = this.erpJobCreatedAt ? new Date(this.erpJobCreatedAt).getTime() : Date.now();
            const updateProgress = () => {
                if (!this.isJobRunning) return;

                const elapsed = (Date.now() - startTime) / 1000;
                this.progress = Math.min(95, (elapsed / this.estimatedDuration) * 100);
                this.remainingSeconds = Math.max(0, Math.round(this.estimatedDuration - elapsed));

                if (this.isJobRunning) {
                    setTimeout(updateProgress, 500);
                }
            };
            updateProgress();
        },

        handleJobCompletion(status) {
            console.log('[ERPSyncStatus] handleJobCompletion:', status, 'erpJobResult:', this.erpJobResult);

            this.progress = 100;
            this.remainingSeconds = 0;
            this.showCompletionStatus = true;
            this.completionResult = (status === 'completed') ? (this.erpJobResult || 'success') : 'error';

            // Clear after 5 seconds
            this.completionTimeout = setTimeout(() => {
                this.resetState();
            }, 5000);
        }
    }));

    // =====================================================
    // ETAP_08 FAZA 8: LOCATION LABELS COMPONENT
    // Stock Tab - Location management with clickable labels
    // =====================================================
    Alpine.data('locationLabels', (initialValue, warehouseId) => ({
        rawValue: initialValue || '',
        warehouseId: warehouseId,

        // Parse comma-separated locations into array
        get locations() {
            if (!this.rawValue || typeof this.rawValue !== 'string') return [];
            return this.rawValue
                .split(',')
                .map(l => l.trim())
                .filter(l => l.length > 0);
        },

        // Join array back to comma-separated string
        set locations(arr) {
            this.rawValue = arr.join(', ');
        },

        // Add new location (from input)
        addLocation(loc) {
            const trimmed = (loc || '').trim();
            if (!trimmed) return;

            // Don't add duplicates
            const current = this.locations;
            if (current.includes(trimmed)) {
                console.log('[LocationLabels] Duplicate location ignored:', trimmed);
                return;
            }

            current.push(trimmed);
            this.locations = current;

            // Notify Livewire about dirty state
            if (this.$wire) {
                this.$wire.markStockDirty(this.warehouseId, 'location');
            }

            console.log('[LocationLabels] Added location:', trimmed, 'Warehouse:', this.warehouseId);
        },

        // Remove location by index
        removeLocation(index) {
            const current = [...this.locations];
            const removed = current.splice(index, 1);
            this.locations = current;

            // Notify Livewire about dirty state
            if (this.$wire) {
                this.$wire.markStockDirty(this.warehouseId, 'location');
            }

            console.log('[LocationLabels] Removed location:', removed[0], 'Warehouse:', this.warehouseId);
        },

        // Edit location (simple prompt for now)
        editLocation(index) {
            const current = this.locations;
            const oldValue = current[index];

            const newValue = prompt('Edytuj lokalizacje:', oldValue);
            if (newValue === null) return; // Cancelled

            const trimmed = newValue.trim();
            if (!trimmed) {
                // Empty = remove
                this.removeLocation(index);
                return;
            }

            if (trimmed !== oldValue) {
                current[index] = trimmed;
                this.locations = current;

                // Notify Livewire about dirty state
                if (this.$wire) {
                    this.$wire.markStockDirty(this.warehouseId, 'location');
                }

                console.log('[LocationLabels] Edited location:', oldValue, '->', trimmed, 'Warehouse:', this.warehouseId);
            }
        },

        // Copy location to clipboard
        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);

                // Show notification via Livewire dispatch
                if (this.$wire) {
                    this.$wire.dispatch('notify', {
                        type: 'info',
                        message: `Skopiowano: ${text}`
                    });
                }

                console.log('[LocationLabels] Copied to clipboard:', text);
            } catch (err) {
                console.error('[LocationLabels] Failed to copy:', err);

                // Fallback: select text
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                if (this.$wire) {
                    this.$wire.dispatch('notify', {
                        type: 'info',
                        message: `Skopiowano: ${text}`
                    });
                }
            }
        },

        // Init: Listen for Livewire updates to sync location from server
        init() {
            const self = this;

            // Listen for stock-locations-updated event (dispatched after ERP pull)
            Livewire.on('stock-locations-updated', (data) => {
                const locations = data?.locations || data[0]?.locations;
                if (locations && locations[self.warehouseId] !== undefined) {
                    const newValue = locations[self.warehouseId] || '';
                    if (newValue !== self.rawValue) {
                        console.log('[LocationLabels] Updated from Livewire event:', self.warehouseId, newValue);
                        self.rawValue = newValue;
                    }
                }
            });

            console.log('[LocationLabels] Initialized for warehouse:', this.warehouseId, 'value:', this.rawValue);
        }
    }));

    // =====================================================
    // MARKDOWN EDITOR TOOLBAR COMPONENT (Bug Reports)
    // =====================================================
    Alpine.data('markdownEditor', () => ({
        init() {
            // Focus textarea on mount
            this.$nextTick(() => {
                this.$refs.textarea?.focus();
            });
        },

        // Insert formatting around selected text (bold, italic, code)
        insertFormat(before, after) {
            const textarea = this.$refs.textarea;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selectedText = text.substring(start, end);

            const newText = text.substring(0, start) + before + selectedText + after + text.substring(end);
            textarea.value = newText;

            // Position cursor
            if (selectedText) {
                textarea.selectionStart = start;
                textarea.selectionEnd = end + before.length + after.length;
            } else {
                textarea.selectionStart = textarea.selectionEnd = start + before.length;
            }

            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },

        // Insert prefix at line start (headers, lists, quotes)
        insertPrefix(prefix) {
            const textarea = this.$refs.textarea;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const text = textarea.value;

            // Find line start
            let lineStart = text.lastIndexOf('\n', start - 1) + 1;
            const newText = text.substring(0, lineStart) + prefix + text.substring(lineStart);

            textarea.value = newText;
            textarea.selectionStart = textarea.selectionEnd = start + prefix.length;
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },

        // Insert code block
        insertCodeBlock() {
            const textarea = this.$refs.textarea;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selectedText = text.substring(start, end);

            const codeBlock = '\n```\n' + (selectedText || 'kod') + '\n```\n';
            const newText = text.substring(0, start) + codeBlock + text.substring(end);

            textarea.value = newText;
            textarea.selectionStart = start + 5;
            textarea.selectionEnd = start + 5 + (selectedText || 'kod').length;
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },

        // Insert link
        insertLink() {
            const textarea = this.$refs.textarea;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selectedText = text.substring(start, end);

            const linkText = selectedText || 'tekst linku';
            const link = '[' + linkText + '](url)';
            const newText = text.substring(0, start) + link + text.substring(end);

            textarea.value = newText;
            // Select 'url' part
            textarea.selectionStart = start + linkText.length + 3;
            textarea.selectionEnd = start + linkText.length + 6;
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },

        // Insert horizontal rule
        insertHr() {
            const textarea = this.$refs.textarea;
            if (!textarea) return;

            const start = textarea.selectionStart;
            const text = textarea.value;

            const hr = '\n\n---\n\n';
            const newText = text.substring(0, start) + hr + text.substring(start);

            textarea.value = newText;
            textarea.selectionStart = textarea.selectionEnd = start + hr.length;
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }));

    console.log('[PPM app.js] Alpine components registered successfully:', {
        loading: Alpine.store('loading'),
        notifications: Alpine.store('notifications'),
        theme: Alpine.store('theme')
    });
}

/**
 * PP.0.6 FIX: Initialize Alpine components
 *
 * Problem: When app.js is loaded as ES module via Vite, it may load AFTER
 * livewire:init event has already been dispatched. In that case, our event
 * listener never fires and Alpine.data components are not registered.
 *
 * Solution:
 * 1. If Alpine is already available on window -> register immediately
 * 2. If not -> listen for livewire:init event
 */
if (window.Alpine) {
    // Alpine already initialized (livewire:init already fired)
    console.log('[PPM app.js] Alpine already available, registering immediately...');
    registerAlpineComponents(window.Alpine);
} else {
    // Alpine not yet available, wait for livewire:init
    console.log('[PPM app.js] Alpine not yet available, waiting for livewire:init...');
    document.addEventListener('livewire:init', () => {
        console.log('[PPM app.js] livewire:init fired, registering components...');
        registerAlpineComponents(window.Alpine);
    });
}

// Global error handler
window.addEventListener('error', (event) => {
    console.error('Global JavaScript error:', event.error);
});
