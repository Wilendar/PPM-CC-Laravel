{{--
    Typography Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji typografii: font-size, font-weight, line-height, letter-spacing,
    text-transform, text-decoration, text-align
--}}
@props([
    'controlId' => 'typography',
    'value' => [],
    'options' => [],
    'onChange' => null,
    'selectedElementId' => null,
])

@php
    $fontWeights = $options['fontWeights'] ?? [
        '100' => 'Thin', '200' => 'Extra Light', '300' => 'Light',
        '400' => 'Normal', '500' => 'Medium', '600' => 'Semi Bold',
        '700' => 'Bold', '800' => 'Extra Bold', '900' => 'Black',
    ];
    $fontFamilies = $options['fontFamilies'] ?? [
        'inherit' => 'Dziedzicz',
        'system-ui, sans-serif' => 'System',
        'Arial, sans-serif' => 'Arial',
        'Georgia, serif' => 'Georgia',
        'monospace' => 'Monospace',
    ];
    $textTransforms = $options['textTransforms'] ?? [
        'none' => 'Aa', 'uppercase' => 'AA', 'lowercase' => 'aa', 'capitalize' => 'Aa',
    ];
    $textDecorations = $options['textDecorations'] ?? [
        'none' => 'Brak', 'underline' => 'U', 'line-through' => 'S',
    ];
    $textAligns = $options['textAligns'] ?? [
        'left' => 'left', 'center' => 'center', 'right' => 'right', 'justify' => 'justify',
    ];
    $units = $options['units'] ?? ['px', 'rem', 'em', '%', 'vw'];

    // Font size presets
    $fontSizePresets = ['12px', '14px', '16px', '18px', '20px', '24px', '32px', '48px', '64px'];
@endphp

<div
    class="uve-control uve-control--typography"
    x-data="uveTypographyControl(@js($value), @js($units))"
    wire:key="typography-control-{{ $selectedElementId ?? 'none' }}"
>
    {{-- Font Size --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Rozmiar czcionki</label>
        <div class="uve-typography-row">
            <input
                type="text"
                x-model.debounce.300ms="fontSize"
                class="uve-input uve-input--sm"
                placeholder="16"
            />
            <select x-model="fontSizeUnit" class="uve-select uve-select--unit">
                <template x-for="unit in units" :key="unit">
                    <option :value="unit" x-text="unit"></option>
                </template>
            </select>
        </div>
        <div class="uve-size-presets">
            @foreach($fontSizePresets as $size)
                <button
                    type="button"
                    @click="setFontSize('{{ $size }}')"
                    class="uve-size-preset-btn"
                    :class="{ 'uve-size-preset-btn--active': fontSize + fontSizeUnit === '{{ $size }}' }"
                >
                    {{ str_replace('px', '', $size) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Font Weight --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Waga czcionki</label>
        <select x-model="fontWeight" class="uve-select">
            @foreach($fontWeights as $weightVal => $weightLabel)
                <option value="{{ $weightVal }}">{{ $weightLabel }} ({{ $weightVal }})</option>
            @endforeach
        </select>
    </div>

    {{-- Font Family --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Rodzina czcionki</label>
        <select x-model="fontFamily" class="uve-select">
            @foreach($fontFamilies as $familyVal => $familyLabel)
                <option value="{{ $familyVal }}">{{ $familyLabel }}</option>
            @endforeach
        </select>
    </div>

    {{-- Line Height --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Wysokosc linii</label>
        <div class="uve-typography-row">
            <input
                type="text"
                x-model.debounce.300ms="lineHeight"
                class="uve-input uve-input--sm"
                placeholder="1.5"
            />
            <select x-model="lineHeightUnit" class="uve-select uve-select--unit">
                <option value="">-</option>
                <template x-for="unit in units" :key="unit">
                    <option :value="unit" x-text="unit"></option>
                </template>
            </select>
        </div>
    </div>

    {{-- Letter Spacing --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Odstep miedzy literami</label>
        <div class="uve-typography-row">
            <input
                type="text"
                x-model.debounce.300ms="letterSpacing"
                class="uve-input uve-input--sm"
                placeholder="0"
            />
            <select x-model="letterSpacingUnit" class="uve-select uve-select--unit">
                <option value="px">px</option>
                <option value="em">em</option>
                <option value="rem">rem</option>
            </select>
        </div>
    </div>

    {{-- Text Transform --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Transformacja tekstu</label>
        <div class="uve-btn-group-full">
            @foreach($textTransforms as $transformVal => $transformLabel)
                <button
                    type="button"
                    @click="textTransform = '{{ $transformVal }}'"
                    class="uve-btn uve-btn-sm"
                    :class="{ 'uve-btn-active': textTransform === '{{ $transformVal }}' }"
                    title="{{ $transformVal }}"
                >
                    {{ $transformLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Text Decoration --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Dekoracja tekstu</label>
        <div class="uve-btn-group-full">
            <button
                type="button"
                @click="textDecoration = 'none'"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': textDecoration === 'none' }"
                title="Brak"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <button
                type="button"
                @click="textDecoration = 'underline'"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': textDecoration === 'underline' }"
                title="Podkreslenie"
            >
                <span class="uve-text-deco-preview" style="text-decoration: underline;">U</span>
            </button>
            <button
                type="button"
                @click="textDecoration = 'line-through'"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': textDecoration === 'line-through' }"
                title="Przekreslenie"
            >
                <span class="uve-text-deco-preview" style="text-decoration: line-through;">S</span>
            </button>
        </div>
    </div>

    {{-- Text Align --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Wyrownanie tekstu</label>
        <div class="uve-btn-group-full">
            <button
                type="button"
                @click="textAlign = 'left'"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': textAlign === 'left' }"
                title="Do lewej"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h14"></path>
                </svg>
            </button>
            <button
                type="button"
                @click="textAlign = 'center'"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': textAlign === 'center' }"
                title="Wycentruj"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M5 18h14"></path>
                </svg>
            </button>
            <button
                type="button"
                @click="textAlign = 'right'"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': textAlign === 'right' }"
                title="Do prawej"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M6 18h14"></path>
                </svg>
            </button>
            <button
                type="button"
                @click="textAlign = 'justify'"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': textAlign === 'justify' }"
                title="Wyjustuj"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>
</div>

<style>
/* Typography Control Styles */
.uve-control--typography {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.uve-control__field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.uve-typography-row {
    display: flex;
    gap: 0.375rem;
}

.uve-input--sm {
    flex: 1;
}

.uve-select--unit {
    width: 60px;
    flex-shrink: 0;
}

/* Size Presets */
.uve-size-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.375rem;
}

.uve-size-preset-btn {
    padding: 0.125rem 0.375rem;
    font-size: 0.65rem;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-size-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-size-preset-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

/* Text Decoration Preview */
.uve-text-deco-preview {
    font-weight: 600;
    font-size: 0.875rem;
}

/* Button Group Full Width */
.uve-btn-group-full {
    display: flex;
}

.uve-btn-group-full .uve-btn {
    flex: 1;
    border-radius: 0;
    justify-content: center;
    padding: 0.5rem;
}

.uve-btn-group-full .uve-btn:first-child {
    border-radius: 0.375rem 0 0 0.375rem;
}

.uve-btn-group-full .uve-btn:last-child {
    border-radius: 0 0.375rem 0.375rem 0;
}

.uve-btn-group-full .uve-btn:not(:last-child) {
    border-right: none;
}
</style>
