{{--
    Responsive Wrapper Control - ETAP_07f_P5 FAZA PP.3
    Przelacznik Device dla podgladu:
    - 3 przyciski: Desktop/Tablet/Mobile
    - Ikony urzadzen
    - Emituje event do zmiany widoku preview
--}}
@props([
    'controlId' => 'responsive-wrapper',
    'value' => 'desktop',
    'options' => [],
    'onChange' => null,
])

@php
    $devices = $options['devices'] ?? [
        'desktop' => [
            'label' => 'Desktop',
            'width' => '100%',
            'minWidth' => '1024px',
        ],
        'tablet' => [
            'label' => 'Tablet',
            'width' => '768px',
        ],
        'mobile' => [
            'label' => 'Mobile',
            'width' => '375px',
        ],
    ];
    $showDimensions = $options['showDimensions'] ?? true;
    $showRotate = $options['showRotate'] ?? true;
@endphp

<div
    class="uve-control uve-control--responsive-wrapper"
    x-data="uveResponsiveWrapperControl(@js($value), @js($showRotate))"
    wire:ignore.self
>
    {{-- Device Selector --}}
    <div class="uve-device-selector">
        {{-- Desktop --}}
        <button
            type="button"
            @click="selectDevice('desktop')"
            class="uve-device-btn"
            :class="{ 'uve-device-btn--active': activeDevice === 'desktop' }"
            title="Desktop (> 1024px)"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/>
                <line x1="8" y1="21" x2="16" y2="21" stroke-width="2"/>
                <line x1="12" y1="17" x2="12" y2="21" stroke-width="2"/>
            </svg>
            <span class="uve-device-btn__label">Desktop</span>
        </button>

        {{-- Tablet --}}
        <button
            type="button"
            @click="selectDevice('tablet')"
            class="uve-device-btn"
            :class="{
                'uve-device-btn--active': activeDevice === 'tablet',
                'uve-device-btn--rotated': activeDevice === 'tablet' && isRotated
            }"
            title="Tablet (768px)"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="uve-device-btn__label">Tablet</span>
        </button>

        {{-- Mobile --}}
        <button
            type="button"
            @click="selectDevice('mobile')"
            class="uve-device-btn"
            :class="{
                'uve-device-btn--active': activeDevice === 'mobile',
                'uve-device-btn--rotated': activeDevice === 'mobile' && isRotated
            }"
            title="Mobile (375px)"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="6" y="2" width="12" height="20" rx="2" stroke-width="2"/>
                <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="uve-device-btn__label">Mobile</span>
        </button>
    </div>

    {{-- Dimensions Display --}}
    @if($showDimensions)
        <div class="uve-device-dimensions">
            <span class="uve-device-dimensions__label">Wymiary:</span>
            <span class="uve-device-dimensions__value" x-text="currentDimensions"></span>
        </div>
    @endif

    {{-- Rotate Button (for tablet/mobile) --}}
    @if($showRotate)
        <div class="uve-device-rotate" x-show="activeDevice !== 'desktop'">
            <button
                type="button"
                @click="toggleRotation()"
                class="uve-rotate-btn"
                :class="{ 'uve-rotate-btn--active': isRotated }"
                title="Obroc urzadzenie"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="isRotated ? 'Landscape' : 'Portrait'"></span>
            </button>
        </div>
    @endif

    {{-- Quick Presets --}}
    <div class="uve-device-presets">
        <span class="uve-device-presets__label">Popularne:</span>
        <div class="uve-device-presets__list">
            <button
                type="button"
                @click="selectPreset('iPhone SE', 375, 667)"
                class="uve-preset-btn"
                title="iPhone SE"
            >
                SE
            </button>
            <button
                type="button"
                @click="selectPreset('iPhone 14', 390, 844)"
                class="uve-preset-btn"
                title="iPhone 14"
            >
                14
            </button>
            <button
                type="button"
                @click="selectPreset('iPad', 768, 1024)"
                class="uve-preset-btn"
                title="iPad"
            >
                iPad
            </button>
            <button
                type="button"
                @click="selectPreset('iPad Pro', 1024, 1366)"
                class="uve-preset-btn"
                title="iPad Pro"
            >
                Pro
            </button>
        </div>
    </div>

    {{-- Custom Size Input --}}
    <div class="uve-device-custom">
        <button
            type="button"
            @click="showCustom = !showCustom"
            class="uve-custom-toggle"
        >
            <span>Niestandardowy rozmiar</span>
            <svg
                class="uve-custom-toggle__icon"
                :class="{ 'uve-custom-toggle__icon--open': showCustom }"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <template x-if="showCustom">
            <div class="uve-custom-inputs">
                <div class="uve-custom-input-group">
                    <label>Szerokosc</label>
                    <div class="uve-input-with-unit">
                        <input
                            type="number"
                            x-model="customWidth"
                            @input="applyCustomSize()"
                            min="320"
                            max="2560"
                            class="uve-input"
                        />
                        <span class="uve-input-unit">px</span>
                    </div>
                </div>
                <div class="uve-custom-input-group">
                    <label>Wysokosc</label>
                    <div class="uve-input-with-unit">
                        <input
                            type="number"
                            x-model="customHeight"
                            @input="applyCustomSize()"
                            min="480"
                            max="2560"
                            class="uve-input"
                        />
                        <span class="uve-input-unit">px</span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Zoom Control --}}
    <div class="uve-device-zoom">
        <span class="uve-device-zoom__label">Zoom:</span>
        <div class="uve-zoom-control">
            <button
                type="button"
                @click="decreaseZoom()"
                class="uve-zoom-btn"
                :disabled="zoom <= 25"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                </svg>
            </button>
            <span class="uve-zoom-value" x-text="zoom + '%'"></span>
            <button
                type="button"
                @click="increaseZoom()"
                class="uve-zoom-btn"
                :disabled="zoom >= 200"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <button
                type="button"
                @click="resetZoom()"
                class="uve-zoom-reset"
                title="Reset zoom"
            >
                100%
            </button>
        </div>
    </div>
</div>

<style>
/* Responsive Wrapper Control Styles */
.uve-control--responsive-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

/* Device Selector */
.uve-device-selector {
    display: flex;
    background: #1e293b;
    border-radius: 0.375rem;
    padding: 0.25rem;
}

.uve-device-btn {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.125rem;
    padding: 0.5rem 0.375rem;
    background: transparent;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-device-btn svg {
    width: 20px;
    height: 20px;
    color: #64748b;
    transition: all 0.15s;
}

.uve-device-btn--rotated svg {
    transform: rotate(90deg);
}

.uve-device-btn__label {
    font-size: 0.65rem;
    font-weight: 500;
    color: #94a3b8;
    transition: all 0.15s;
}

.uve-device-btn:hover {
    background: #334155;
}

.uve-device-btn:hover svg {
    color: #e2e8f0;
}

.uve-device-btn--active {
    background: #334155;
}

.uve-device-btn--active svg {
    color: #e0ac7e;
}

.uve-device-btn--active .uve-device-btn__label {
    color: #e0ac7e;
}

/* Dimensions Display */
.uve-device-dimensions {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem;
    background: #0f172a;
    border-radius: 0.25rem;
}

.uve-device-dimensions__label {
    font-size: 0.7rem;
    color: #64748b;
}

.uve-device-dimensions__value {
    font-size: 0.75rem;
    font-weight: 600;
    color: #e0ac7e;
    font-family: monospace;
}

/* Rotate Button */
.uve-device-rotate {
    display: flex;
    justify-content: center;
}

.uve-rotate-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-rotate-btn svg {
    width: 14px;
    height: 14px;
}

.uve-rotate-btn:hover {
    background: #334155;
    color: #e2e8f0;
}

.uve-rotate-btn--active {
    background: rgba(224, 172, 126, 0.1);
    border-color: #e0ac7e;
    color: #e0ac7e;
}

/* Presets */
.uve-device-presets {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.uve-device-presets__label {
    font-size: 0.65rem;
    color: #64748b;
}

.uve-device-presets__list {
    display: flex;
    gap: 0.25rem;
}

.uve-preset-btn {
    flex: 1;
    padding: 0.25rem;
    font-size: 0.65rem;
    font-weight: 500;
    color: #94a3b8;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-preset-btn:hover {
    background: #334155;
    color: #e2e8f0;
}

/* Custom Size */
.uve-device-custom {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.uve-custom-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 0.375rem 0.5rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-custom-toggle:hover {
    background: #334155;
    color: #e2e8f0;
}

.uve-custom-toggle__icon {
    width: 14px;
    height: 14px;
    transition: transform 0.2s;
}

.uve-custom-toggle__icon--open {
    transform: rotate(180deg);
}

.uve-custom-inputs {
    display: flex;
    gap: 0.5rem;
}

.uve-custom-input-group {
    flex: 1;
}

.uve-custom-input-group label {
    display: block;
    font-size: 0.65rem;
    color: #64748b;
    margin-bottom: 0.125rem;
}

.uve-input-with-unit {
    display: flex;
}

.uve-input-with-unit .uve-input {
    flex: 1;
    border-radius: 0.25rem 0 0 0.25rem;
    font-size: 0.75rem;
}

.uve-input-unit {
    padding: 0.375rem 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-left: none;
    border-radius: 0 0.25rem 0.25rem 0;
    font-size: 0.7rem;
    color: #64748b;
}

/* Zoom Control */
.uve-device-zoom {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding-top: 0.375rem;
    border-top: 1px solid #334155;
}

.uve-device-zoom__label {
    font-size: 0.7rem;
    color: #64748b;
}

.uve-zoom-control {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex: 1;
}

.uve-zoom-btn {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-zoom-btn svg {
    width: 12px;
    height: 12px;
    color: #94a3b8;
}

.uve-zoom-btn:hover:not(:disabled) {
    background: #334155;
}

.uve-zoom-btn:hover:not(:disabled) svg {
    color: #e2e8f0;
}

.uve-zoom-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.uve-zoom-value {
    min-width: 40px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #e0ac7e;
    text-align: center;
}

.uve-zoom-reset {
    padding: 0.25rem 0.5rem;
    font-size: 0.65rem;
    color: #64748b;
    background: transparent;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    cursor: pointer;
    margin-left: auto;
    transition: all 0.15s;
}

.uve-zoom-reset:hover {
    background: #1e293b;
    color: #94a3b8;
}
</style>

<script>
function uveResponsiveWrapperControl(initialValue, showRotate) {
    return {
        activeDevice: initialValue || 'desktop',
        isRotated: false,
        showCustom: false,
        customWidth: 375,
        customHeight: 667,
        zoom: 100,

        deviceDimensions: {
            desktop: { width: '100%', height: 'auto' },
            tablet: { width: 768, height: 1024 },
            mobile: { width: 375, height: 667 },
        },

        get currentDimensions() {
            if (this.activeDevice === 'desktop') {
                return '100% x auto';
            }

            let dims = this.deviceDimensions[this.activeDevice];
            if (this.isRotated) {
                return `${dims.height}px x ${dims.width}px`;
            }
            return `${dims.width}px x ${dims.height}px`;
        },

        selectDevice(device) {
            this.activeDevice = device;
            this.isRotated = false;
            this.emitChange();
        },

        selectPreset(name, width, height) {
            // Determine device type based on width
            let device = 'desktop';
            if (width < 768) {
                device = 'mobile';
            } else if (width < 1024) {
                device = 'tablet';
            }

            this.activeDevice = device;
            this.deviceDimensions[device] = { width, height };
            this.isRotated = false;
            this.emitChange();
        },

        toggleRotation() {
            this.isRotated = !this.isRotated;
            this.emitChange();
        },

        applyCustomSize() {
            // Update current device dimensions
            if (this.activeDevice !== 'desktop') {
                this.deviceDimensions[this.activeDevice] = {
                    width: parseInt(this.customWidth) || 375,
                    height: parseInt(this.customHeight) || 667,
                };
            }
            this.emitChange();
        },

        increaseZoom() {
            if (this.zoom < 200) {
                this.zoom += 25;
                this.emitZoomChange();
            }
        },

        decreaseZoom() {
            if (this.zoom > 25) {
                this.zoom -= 25;
                this.emitZoomChange();
            }
        },

        resetZoom() {
            this.zoom = 100;
            this.emitZoomChange();
        },

        emitChange() {
            let dims = this.deviceDimensions[this.activeDevice];
            let width, height;

            if (this.activeDevice === 'desktop') {
                width = '100%';
                height = 'auto';
            } else if (this.isRotated) {
                width = dims.height + 'px';
                height = dims.width + 'px';
            } else {
                width = dims.width + 'px';
                height = dims.height + 'px';
            }

            const value = {
                device: this.activeDevice,
                width: width,
                height: height,
                isRotated: this.isRotated,
                zoom: this.zoom,
            };

            this.$wire.updateControlValue('responsive-wrapper', value);

            // Dispatch browser event for iframe resize
            this.$dispatch('uve-viewport-change', value);
        },

        emitZoomChange() {
            this.$dispatch('uve-zoom-change', { zoom: this.zoom });
            this.$wire.updateControlValue('responsive-wrapper', {
                device: this.activeDevice,
                zoom: this.zoom,
            });
        }
    }
}
</script>
