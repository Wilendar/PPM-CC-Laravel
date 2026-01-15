{{--
    Color Picker Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do wyboru koloru z presetami PPM brand colors
    Wsparcie dla hex, rgba, opacity
--}}
@props([
    'controlId' => 'color-picker',
    'value' => '',
    'label' => 'Kolor',
    'property' => 'color',
    'options' => [],
    'onChange' => null,
])

@php
    $presets = $options['presets'] ?? [
        '#ef8248' => 'Brand Orange',
        '#e0ac7e' => 'Brand Gold',
        '#1a1a1a' => 'Dark',
        '#f6f6f6' => 'Light Gray',
        '#ffffff' => 'White',
        '#000000' => 'Black',
        '#333333' => 'Text',
        '#666666' => 'Text Light',
        '#10b981' => 'Success',
        '#ef4444' => 'Error',
        '#3b82f6' => 'Info',
        '#f59e0b' => 'Warning',
    ];
    $showAlpha = $options['showAlpha'] ?? true;
@endphp

<div
    class="uve-control uve-control--color-picker"
    x-data="uveColorPickerControl(@js($value), @js($property))"
    wire:ignore.self
>
    <label class="uve-control__label">{{ $label }}</label>

    {{-- Color Input Row --}}
    <div class="uve-color-row">
        <div class="uve-color-preview-wrapper">
            <input
                type="color"
                x-model="hexColor"
                @input="onColorInput()"
                class="uve-color-native-input"
            />
            <div
                class="uve-color-preview"
                :style="'background-color: ' + displayColor"
            ></div>
        </div>

        <input
            type="text"
            x-model="hexColor"
            @input="onHexInput()"
            @blur="validateHex()"
            class="uve-input uve-input--color-hex"
            placeholder="#000000"
        />
    </div>

    {{-- Opacity Slider --}}
    @if($showAlpha)
        <div class="uve-opacity-row">
            <label class="uve-control__label-sm">Przezroczystosc</label>
            <div class="uve-opacity-control">
                <input
                    type="range"
                    x-model="opacity"
                    @input="emitChange()"
                    min="0"
                    max="100"
                    step="1"
                    class="uve-slider"
                />
                <span class="uve-opacity-value" x-text="opacity + '%'"></span>
            </div>
        </div>
    @endif

    {{-- Preset Colors --}}
    <div class="uve-color-presets">
        <span class="uve-presets-label">Kolory PPM:</span>
        <div class="uve-color-swatches">
            @foreach($presets as $colorHex => $colorName)
                <button
                    type="button"
                    @click="setColor('{{ $colorHex }}')"
                    class="uve-color-swatch"
                    :class="{ 'uve-color-swatch--active': hexColor === '{{ $colorHex }}' }"
                    style="background-color: {{ $colorHex }}"
                    title="{{ $colorName }}"
                ></button>
            @endforeach
        </div>
    </div>

    {{-- Transparent Option --}}
    <div class="uve-color-actions">
        <button
            type="button"
            @click="setTransparent()"
            class="uve-btn uve-btn-sm"
            :class="{ 'uve-btn-active': isTransparent }"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Przezroczysty
        </button>
        <button
            type="button"
            @click="clearColor()"
            class="uve-btn uve-btn-sm"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Wyczysc
        </button>
    </div>
</div>

<style>
/* Color Picker Control Styles */
.uve-control--color-picker {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.uve-color-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.uve-color-preview-wrapper {
    position: relative;
    width: 40px;
    height: 40px;
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
    border-radius: 0.375rem;
    border: 2px solid #475569;
    background-image:
        linear-gradient(45deg, #334155 25%, transparent 25%),
        linear-gradient(-45deg, #334155 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #334155 75%),
        linear-gradient(-45deg, transparent 75%, #334155 75%);
    background-size: 8px 8px;
    background-position: 0 0, 0 4px, 4px -4px, -4px 0px;
    position: relative;
    overflow: hidden;
}

.uve-color-preview::after {
    content: '';
    position: absolute;
    inset: 0;
    background-color: inherit;
}

.uve-input--color-hex {
    flex: 1;
    font-family: monospace;
    text-transform: uppercase;
}

/* Opacity Control */
.uve-opacity-row {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.uve-control__label-sm {
    font-size: 0.7rem;
    color: #64748b;
}

.uve-opacity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.uve-slider {
    flex: 1;
    height: 6px;
    appearance: none;
    background: linear-gradient(to right, transparent, #e0ac7e);
    border-radius: 3px;
    cursor: pointer;
}

.uve-slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    background: #e0ac7e;
    border: 2px solid #0f172a;
    border-radius: 50%;
    cursor: pointer;
}

.uve-slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: #e0ac7e;
    border: 2px solid #0f172a;
    border-radius: 50%;
    cursor: pointer;
}

.uve-opacity-value {
    width: 40px;
    font-size: 0.75rem;
    color: #94a3b8;
    text-align: right;
}

/* Color Presets */
.uve-color-presets {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.uve-color-swatches {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.uve-color-swatch {
    width: 24px;
    height: 24px;
    border-radius: 0.25rem;
    border: 2px solid #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-color-swatch:hover {
    transform: scale(1.1);
    border-color: #e2e8f0;
}

.uve-color-swatch--active {
    border-color: #e0ac7e;
    box-shadow: 0 0 0 2px rgba(224, 172, 126, 0.4);
}

/* Color Actions */
.uve-color-actions {
    display: flex;
    gap: 0.375rem;
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-color-actions .uve-btn {
    flex: 1;
    justify-content: center;
    gap: 0.25rem;
}
</style>

<script>
function uveColorPickerControl(initialValue, property) {
    return {
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
            // Auto-add # if missing
            if (this.hexColor && !this.hexColor.startsWith('#')) {
                this.hexColor = '#' + this.hexColor;
            }
            this.emitChange();
        },

        validateHex() {
            // Validate and fix hex format
            const hex = this.hexColor.replace('#', '');
            if (!/^[0-9A-Fa-f]{3}$|^[0-9A-Fa-f]{6}$/.test(hex)) {
                this.hexColor = '#000000';
            } else if (hex.length === 3) {
                // Expand shorthand
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
            this.$wire.updateControlValue(this.property, value);
        }
    }
}
</script>
