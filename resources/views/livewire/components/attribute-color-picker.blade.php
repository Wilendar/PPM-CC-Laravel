{{--
    AttributeColorPicker Component Template

    Production-ready color picker using vanilla-colorful Web Component + Alpine.js.
    Designed for AttributeValue management in ETAP_05b variant system.

    Props:
    - $color (string|null) - Current hex color value (#RRGGBB format)
    - $label (string|null) - Label text for color input
    - $required (bool) - Whether field is required
    - $validationError (string|null) - Current validation error message

    Features:
    - Real-time color picker with live preview
    - #RRGGBB format validation (client + server)
    - Livewire wire:model binding
    - Alpine.js x-data state management
    - PrestaShop-compatible color format

    Usage Example:
    <livewire:components.attribute-color-picker
        wire:model="formData.color"
        label="Attribute Color"
        :required="true"
    />
--}}

<div x-data="attributeColorPicker(@js($color ?? '#000000'), @js($validationError !== null))" class="color-picker-container">
    {{-- Label (optional) --}}
    @if($label)
        <label class="color-picker-label">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif

    {{-- Color Preview & Input Group --}}
    <div class="color-picker-input-group">
        {{-- Color Swatch Preview --}}
        <div
            class="color-swatch"
            :style="`background-color: ${colorValue || '#000000'}`"
            :title="`Current color: ${colorValue}`"
        >
            <span class="sr-only">Color preview</span>
        </div>

        {{-- Hex Color Input --}}
        <input
            wire:model.live="color"
            type="text"
            placeholder="#000000"
            maxlength="7"
            class="color-input"
            :class="{ 'color-input-error': hasError }"
            @input="handleColorInput($event)"
            x-model="colorValue"
        >

        {{-- Format Hint --}}
        <span class="color-format-hint">#RRGGBB</span>
    </div>

    {{-- Validation Error Message --}}
    @if($validationError)
        <div class="color-error" role="alert">
            <svg class="color-error-icon" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $validationError }}</span>
        </div>
    @endif

    {{-- Color Picker Web Component (vanilla-colorful) --}}
    <div class="color-picker-component">
        <div class="color-picker-header">
            <span class="color-picker-title">Select Color</span>
            <span class="color-picker-current" x-text="colorValue || '#000000'"></span>
        </div>

        {{-- vanilla-colorful Web Component --}}
        <div class="color-picker-canvas">
            <hex-color-picker
                :color="colorValue"
                @color-changed="handleColorChanged($event)"
            ></hex-color-picker>
        </div>

        {{-- RGB Display (informational) --}}
        <div class="color-rgb-display">
            <span class="color-rgb-label">RGB:</span>
            <span class="color-rgb-value" x-text="getRgbFromHex(colorValue)"></span>
        </div>
    </div>
</div>

{{--
    Note:
    - vanilla-colorful Web Component is loaded in resources/js/app.js
    - Alpine.data('attributeColorPicker') is registered in resources/js/app.js
    - Livewire 3.x requires all Alpine components to be registered globally, not inline
--}}
