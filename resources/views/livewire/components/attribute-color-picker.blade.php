{{--
    AttributeColorPicker Component Template - REDESIGNED

    Production-ready color picker using vanilla-colorful Web Component + Alpine.js.
    Designed for AttributeValue management in ETAP_05b variant system.

    Props:
    - $color (string|null) - Current hex color value (#RRGGBB format)
    - $label (string|null) - Label text for color input
    - $required (bool) - Whether field is required
    - $validationError (string|null) - Current validation error message

    Features:
    - Collapsible color picker panel (click to expand)
    - Real-time color picker with live preview
    - #RRGGBB format validation (client + server)
    - Livewire wire:model binding
    - Alpine.js x-data state management
    - PrestaShop-compatible color format
    - FIXED: z-index issues when nested in modals

    Usage Example:
    <livewire:components.attribute-color-picker
        wire:model="formData.color"
        label="Attribute Color"
        :required="true"
    />
--}}

<div x-data="attributeColorPicker(@js($color ?? '#000000'), @js($validationError !== null))"
     class="color-picker-wrapper">

    {{-- Label (optional) --}}
    @if($label)
        <label class="color-picker-label">
            {{ $label }}
            @if($required)
                <span class="text-red-500 ml-1">*</span>
            @endif
        </label>
    @endif

    {{-- Compact Input Row --}}
    <div class="color-picker-compact-row">
        {{-- Color Swatch Preview (clickable to toggle picker) --}}
        <button type="button"
                @click="showPicker = !showPicker"
                class="color-swatch-btn"
                :style="`background-color: ${colorValue || '#000000'}`"
                :title="showPicker ? 'Zamknij probnik' : 'Otworz probnik kolorow'">
            <span class="sr-only">Toggle color picker</span>
        </button>

        {{-- Hex Color Input --}}
        <input wire:model.live="color"
               type="text"
               placeholder="#000000"
               maxlength="7"
               class="color-input-compact"
               :class="{ 'color-input-error': hasError }"
               @input="handleColorInput($event)"
               x-model="colorValue">

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

    {{-- Collapsible Color Picker Panel --}}
    <div x-show="showPicker"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         @click.outside="showPicker = false"
         class="color-picker-dropdown">

        <div class="color-picker-dropdown-header">
            <span class="color-picker-title">Wybierz kolor</span>
            <span class="color-picker-current" x-text="colorValue || '#000000'"></span>
        </div>

        {{-- vanilla-colorful Web Component --}}
        <div class="color-picker-canvas">
            <hex-color-picker
                :color="colorValue"
                @color-changed="handleColorChanged($event)">
            </hex-color-picker>
        </div>

        {{-- RGB Display --}}
        <div class="color-rgb-display">
            <span class="color-rgb-label">RGB:</span>
            <span class="color-rgb-value" x-text="getRgbFromHex(colorValue)"></span>
        </div>

        {{-- Close button --}}
        <button type="button"
                @click="showPicker = false"
                class="color-picker-close-btn">
            Zamknij
        </button>
    </div>
</div>

{{--
    Note:
    - vanilla-colorful Web Component is loaded in resources/js/app.js
    - Alpine.data('attributeColorPicker') is registered in resources/js/app.js
    - Livewire 3.x requires all Alpine components to be registered globally, not inline
--}}
