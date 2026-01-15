{{--
    Parallax Settings Control - ETAP_07f_P5 FAZA PP.3
    Konfiguracja efektu parallax:
    - height: min-height sekcji (px/vh)
    - overlay: kolor + opacity
    - textPosition: center/left/right
    - speed: predkosc parallax (0.1-1.0)
    - backgroundSize: cover/contain
--}}
@props([
    'controlId' => 'parallax-settings',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $textPositionOptions = $options['textPositions'] ?? [
        'left' => 'Lewo',
        'center' => 'Srodek',
        'right' => 'Prawo',
    ];
    $backgroundSizeOptions = $options['backgroundSizes'] ?? [
        'cover' => 'Cover (wypelnij)',
        'contain' => 'Contain (dopasuj)',
        'auto' => 'Auto',
    ];
    $heightUnitOptions = $options['heightUnits'] ?? [
        'px' => 'px',
        'vh' => 'vh',
        'rem' => 'rem',
    ];
@endphp

<div
    class="uve-control uve-control--parallax-settings"
    x-data="uveParallaxSettingsControl(@js($value))"
    wire:ignore.self
>
    {{-- Preview --}}
    <div class="uve-parallax-preview" :style="previewStyle">
        <div class="uve-parallax-preview__overlay" :style="overlayStyle"></div>
        <div class="uve-parallax-preview__content" :class="'uve-parallax-preview__content--' + textPosition">
            <span class="uve-parallax-preview__text">Tekst</span>
        </div>
        <div class="uve-parallax-preview__indicator">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
            <span x-text="(speed * 100).toFixed(0) + '%'"></span>
        </div>
    </div>

    {{-- Height --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Wysokosc sekcji</label>
        <div class="uve-input-with-unit">
            <input
                type="number"
                x-model="height"
                @input="emitChange()"
                min="100"
                max="1000"
                step="10"
                class="uve-input"
            />
            <select x-model="heightUnit" @change="emitChange()" class="uve-select uve-select--unit">
                @foreach($heightUnitOptions as $unitVal => $unitLabel)
                    <option value="{{ $unitVal }}">{{ $unitLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="uve-control__hint">
            Sugerowane: 400-600px lub 50-80vh
        </div>
    </div>

    {{-- Parallax Speed --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Predkosc parallax</label>
        <div class="uve-parallax-speed">
            <input
                type="range"
                x-model="speed"
                @input="emitChange()"
                min="0"
                max="1"
                step="0.1"
                class="uve-slider"
            />
            <div class="uve-parallax-speed-labels">
                <span>Wolno</span>
                <span class="uve-parallax-speed-value" x-text="(speed * 100).toFixed(0) + '%'"></span>
                <span>Szybko</span>
            </div>
        </div>
    </div>

    {{-- Background Size --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Rozmiar tla</label>
        <div class="uve-btn-group-full">
            @foreach($backgroundSizeOptions as $sizeVal => $sizeLabel)
                <button
                    type="button"
                    @click="backgroundSize = '{{ $sizeVal }}'; emitChange()"
                    class="uve-btn uve-btn-sm"
                    :class="{ 'uve-btn-active': backgroundSize === '{{ $sizeVal }}' }"
                >
                    {{ $sizeLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Overlay Section --}}
    <div class="uve-control__field">
        <div class="uve-control__header-row">
            <label class="uve-control__label">Overlay (nakladka)</label>
            <label class="uve-toggle">
                <input
                    type="checkbox"
                    x-model="overlayEnabled"
                    @change="emitChange()"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-slider"></span>
            </label>
        </div>

        <template x-if="overlayEnabled">
            <div class="uve-parallax-overlay-options">
                {{-- Overlay Color --}}
                <div class="uve-overlay-color-row">
                    <label class="uve-control__label uve-control__label--sm">Kolor</label>
                    <div class="uve-color-row">
                        <div class="uve-color-preview-wrapper">
                            <input
                                type="color"
                                x-model="overlayColor"
                                @input="emitChange()"
                                class="uve-color-native-input"
                            />
                            <div class="uve-color-preview" :style="'background-color: ' + overlayColor"></div>
                        </div>
                        <input
                            type="text"
                            x-model="overlayColor"
                            @input="emitChange()"
                            class="uve-input uve-input--color-hex"
                            placeholder="#000000"
                        />
                    </div>
                </div>

                {{-- Overlay Color Presets --}}
                <div class="uve-color-swatches">
                    <button type="button" @click="setOverlayColor('#000000')" class="uve-color-swatch" title="Black"></button>
                    <button type="button" @click="setOverlayColor('#1a1a1a')" class="uve-color-swatch" title="Dark Gray"></button>
                    <button type="button" @click="setOverlayColor('#0f172a')" class="uve-color-swatch" title="Slate"></button>
                    <button type="button" @click="setOverlayColor('#1e3a5f')" class="uve-color-swatch" title="Navy"></button>
                    <button type="button" @click="setOverlayColor('#e0ac7e')" class="uve-color-swatch" title="Brand Gold"></button>
                    <button type="button" @click="setOverlayColor('#ef8248')" class="uve-color-swatch" title="Brand Orange"></button>
                </div>

                {{-- Overlay Opacity --}}
                <div class="uve-overlay-opacity-row">
                    <label class="uve-control__label uve-control__label--sm">Przezroczystosc</label>
                    <div class="uve-opacity-control">
                        <input
                            type="range"
                            x-model="overlayOpacity"
                            @input="emitChange()"
                            min="0"
                            max="100"
                            step="5"
                            class="uve-slider"
                        />
                        <span class="uve-opacity-value" x-text="overlayOpacity + '%'"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Text Position --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Pozycja tekstu</label>
        <div class="uve-text-position-grid">
            @foreach($textPositionOptions as $posVal => $posLabel)
                <button
                    type="button"
                    @click="textPosition = '{{ $posVal }}'; emitChange()"
                    class="uve-text-position-btn"
                    :class="{ 'uve-text-position-btn--active': textPosition === '{{ $posVal }}' }"
                    title="{{ $posLabel }}"
                >
                    <span class="uve-text-position-preview uve-text-position-preview--{{ $posVal }}">
                        <span class="uve-text-position-line"></span>
                        <span class="uve-text-position-line uve-text-position-line--short"></span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Additional Options --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Opcje dodatkowe</label>
        <div class="uve-parallax-extra-options">
            <label class="uve-toggle-row">
                <input
                    type="checkbox"
                    x-model="fixedBackground"
                    @change="emitChange()"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-label">Przypiete tlo (fixed)</span>
            </label>

            <label class="uve-toggle-row">
                <input
                    type="checkbox"
                    x-model="centerContent"
                    @change="emitChange()"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-label">Centruj zawartosc pionowo</span>
            </label>
        </div>
    </div>

    {{-- Reset Button --}}
    <div class="uve-control__actions">
        <button
            type="button"
            @click="resetToDefaults()"
            class="uve-btn uve-btn-sm"
        >
            <svg class="uve-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Przywroc domyslne
        </button>
    </div>
</div>

<style>
/* Parallax Settings Control Styles */
.uve-control--parallax-settings {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Preview */
.uve-parallax-preview {
    position: relative;
    height: 100px;
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
    background-size: cover;
    background-position: center;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    overflow: hidden;
}

.uve-parallax-preview__overlay {
    position: absolute;
    inset: 0;
    transition: all 0.2s;
}

.uve-parallax-preview__content {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    padding: 0 1rem;
}

.uve-parallax-preview__content--left {
    justify-content: flex-start;
}

.uve-parallax-preview__content--center {
    justify-content: center;
}

.uve-parallax-preview__content--right {
    justify-content: flex-end;
}

.uve-parallax-preview__text {
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
    z-index: 1;
}

.uve-parallax-preview__indicator {
    position: absolute;
    bottom: 0.375rem;
    right: 0.375rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.375rem;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 0.25rem;
    font-size: 0.65rem;
    color: #e0ac7e;
}

.uve-parallax-preview__indicator svg {
    width: 12px;
    height: 12px;
}

/* Parallax Speed */
.uve-parallax-speed {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.uve-parallax-speed-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.65rem;
    color: #64748b;
}

.uve-parallax-speed-value {
    font-weight: 600;
    color: #e0ac7e;
}

/* Overlay Options */
.uve-parallax-overlay-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.5rem;
    background: #1e293b;
    border-radius: 0.25rem;
    margin-top: 0.25rem;
}

.uve-overlay-color-row,
.uve-overlay-opacity-row {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

/* Color Row */
.uve-color-row {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.uve-color-preview-wrapper {
    position: relative;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}

.uve-color-native-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.uve-color-preview {
    width: 100%;
    height: 100%;
    border-radius: 0.25rem;
    border: 2px solid #475569;
}

.uve-input--color-hex {
    width: 80px;
    font-family: monospace;
    font-size: 0.75rem;
}

/* Color Swatches */
.uve-color-swatches {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.uve-color-swatch {
    width: 20px;
    height: 20px;
    border-radius: 0.25rem;
    border: 2px solid #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-color-swatch:nth-child(1) { background: #000000; }
.uve-color-swatch:nth-child(2) { background: #1a1a1a; }
.uve-color-swatch:nth-child(3) { background: #0f172a; }
.uve-color-swatch:nth-child(4) { background: #1e3a5f; }
.uve-color-swatch:nth-child(5) { background: #e0ac7e; }
.uve-color-swatch:nth-child(6) { background: #ef8248; }

.uve-color-swatch:hover {
    transform: scale(1.1);
    border-color: #e2e8f0;
}

/* Opacity Control */
.uve-opacity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.uve-opacity-control .uve-slider {
    flex: 1;
}

.uve-opacity-value {
    width: 40px;
    font-size: 0.75rem;
    color: #94a3b8;
    text-align: right;
}

/* Text Position Grid */
.uve-text-position-grid {
    display: flex;
    gap: 0.375rem;
}

.uve-text-position-btn {
    flex: 1;
    height: 40px;
    background: #1e293b;
    border: 2px solid #334155;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.uve-text-position-btn:hover {
    background: #334155;
}

.uve-text-position-btn--active {
    border-color: #e0ac7e;
    background: rgba(224, 172, 126, 0.1);
}

.uve-text-position-preview {
    display: flex;
    flex-direction: column;
    gap: 3px;
    width: 24px;
}

.uve-text-position-preview--left {
    align-items: flex-start;
}

.uve-text-position-preview--center {
    align-items: center;
}

.uve-text-position-preview--right {
    align-items: flex-end;
}

.uve-text-position-line {
    width: 16px;
    height: 3px;
    background: #64748b;
    border-radius: 1px;
}

.uve-text-position-line--short {
    width: 10px;
}

.uve-text-position-btn--active .uve-text-position-line {
    background: #e0ac7e;
}

/* Extra Options */
.uve-parallax-extra-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Control Hint */
.uve-control__hint {
    font-size: 0.65rem;
    color: #64748b;
    margin-top: 0.25rem;
}

/* Header Row */
.uve-control__header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

/* Toggle (reuse from slider-settings) */
.uve-toggle {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
}

.uve-toggle .uve-checkbox {
    opacity: 0;
    width: 0;
    height: 0;
}

.uve-toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background: #475569;
    border-radius: 20px;
    transition: all 0.2s;
}

.uve-toggle-slider::before {
    position: absolute;
    content: "";
    height: 14px;
    width: 14px;
    left: 3px;
    bottom: 3px;
    background: white;
    border-radius: 50%;
    transition: all 0.2s;
}

.uve-toggle .uve-checkbox:checked + .uve-toggle-slider {
    background: #e0ac7e;
}

.uve-toggle .uve-checkbox:checked + .uve-toggle-slider::before {
    transform: translateX(16px);
}

/* Toggle Row */
.uve-toggle-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.uve-toggle-label {
    font-size: 0.8125rem;
    color: #e2e8f0;
}

/* Actions */
.uve-control__actions {
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-control__actions .uve-btn {
    width: 100%;
    justify-content: center;
}

.uve-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}
</style>

<script>
function uveParallaxSettingsControl(initialValue) {
    return {
        // Main settings
        height: parseInt(initialValue.height) || 500,
        heightUnit: initialValue.heightUnit || 'px',
        speed: parseFloat(initialValue.speed) || 0.5,
        backgroundSize: initialValue.backgroundSize || 'cover',
        textPosition: initialValue.textPosition || 'center',
        fixedBackground: initialValue.fixedBackground ?? false,
        centerContent: initialValue.centerContent ?? true,

        // Overlay
        overlayEnabled: initialValue.overlayEnabled ?? true,
        overlayColor: initialValue.overlayColor || '#000000',
        overlayOpacity: parseInt(initialValue.overlayOpacity) || 50,

        get previewStyle() {
            return `min-height: ${this.height}${this.heightUnit}; background-size: ${this.backgroundSize};`;
        },

        get overlayStyle() {
            if (!this.overlayEnabled) return 'display: none;';
            const rgba = this.hexToRgba(this.overlayColor, this.overlayOpacity / 100);
            return `background-color: ${rgba};`;
        },

        setOverlayColor(color) {
            this.overlayColor = color;
            this.emitChange();
        },

        hexToRgba(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },

        resetToDefaults() {
            this.height = 500;
            this.heightUnit = 'px';
            this.speed = 0.5;
            this.backgroundSize = 'cover';
            this.textPosition = 'center';
            this.fixedBackground = false;
            this.centerContent = true;
            this.overlayEnabled = true;
            this.overlayColor = '#000000';
            this.overlayOpacity = 50;
            this.emitChange();
        },

        emitChange() {
            const value = {
                height: this.height + this.heightUnit,
                speed: this.speed,
                backgroundSize: this.backgroundSize,
                textPosition: this.textPosition,
                fixedBackground: this.fixedBackground,
                centerContent: this.centerContent,
                overlay: this.overlayEnabled ? {
                    color: this.overlayColor,
                    opacity: this.overlayOpacity / 100,
                    rgba: this.hexToRgba(this.overlayColor, this.overlayOpacity / 100),
                } : null,
            };

            this.$wire.updateControlValue('parallax-settings', value);
        }
    }
}
</script>
