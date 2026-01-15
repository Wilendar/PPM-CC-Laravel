{{--
    Image Settings Control for Property Panel - ETAP_07h

    Controls:
    - Image URL / source with preview and media picker
    - Size presets (full, large, medium, small, custom)
    - Alignment (left, center, right)
    - Object-fit (contain, cover, fill, none, scale-down)
    - Border radius presets
    - Toggles: shadow, lightbox, lazy load

    CSS-FIRST ARCHITECTURE: All styles via CSS classes, no inline styles
--}}
@props([
    'control' => [],
    'value' => [],
    'imageUrl' => '',
    'selectedElementId' => null,
])

@php
    $controlId = $control['type'] ?? 'image-settings';
    $label = $control['label'] ?? 'Ustawienia obrazu';
    $options = $control['options'] ?? [];
    $defaultValue = $control['defaultValue'] ?? [];
    $rawValue = is_array($value) ? $value : [];

    $normalizeCssValue = static function (mixed $val): string {
        if (!is_string($val)) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', $val));
    };

    $normalizeCssNumber = static function (string $val): ?float {
        $val = trim($val);

        if ($val === '') {
            return null;
        }

        if (strtolower($val) === 'auto') {
            return null;
        }

        // Handle px values (computed styles typically come as px)
        if (preg_match('/^(-?\d+(?:\.\d+)?)px$/i', $val, $m)) {
            return (float) $m[1];
        }

        // Handle plain zero (e.g. "0")
        if ($val === '0') {
            return 0.0;
        }

        return null;
    };

    $isCssAuto = static function (string $val): bool {
        return strtolower(trim($val)) === 'auto';
    };

    $isCssZero = static function (string $val) use ($normalizeCssNumber): bool {
        $num = $normalizeCssNumber($val);
        return $num !== null && abs($num) < 0.5;
    };

    // Derive size preset from current CSS width (when possible)
    $widthValue = $normalizeCssValue($rawValue['width'] ?? '');
    $heightValue = $normalizeCssValue($rawValue['height'] ?? '');

    $derivedSize = null;
    $derivedCustomWidth = null;
    $derivedCustomHeight = null;

    if ($widthValue !== '' && strtolower($widthValue) !== 'auto') {
        $widthNoSpaces = str_replace(' ', '', $widthValue);

        $presetByWidth = [
            '100%' => 'full',
            '75%' => 'large',
            '50%' => 'medium',
            '25%' => 'small',
        ];

        if (isset($presetByWidth[$widthNoSpaces])) {
            $derivedSize = $presetByWidth[$widthNoSpaces];
        } else {
            // Any other width (e.g. computed "960px") -> treat as custom
            $derivedSize = 'custom';
            $derivedCustomWidth = $widthValue;

            // Avoid freezing computed pixel height as "customHeight"
            $derivedCustomHeight = 'auto';
            if (!preg_match('/px$/i', $widthNoSpaces) && $heightValue !== '' && strtolower($heightValue) !== 'auto') {
                $derivedCustomHeight = $heightValue;
            }
        }
    }

    // Derive alignment from margins (computed margins are usually px, auto becomes px)
    $marginLeft = $normalizeCssValue($rawValue['marginLeft'] ?? '');
    $marginRight = $normalizeCssValue($rawValue['marginRight'] ?? '');
    $derivedAlignment = null;

    if ($marginLeft !== '' || $marginRight !== '') {
        if ($isCssAuto($marginLeft) && $isCssAuto($marginRight)) {
            $derivedAlignment = 'center';
        } elseif ($isCssAuto($marginLeft) && $isCssZero($marginRight)) {
            $derivedAlignment = 'right';
        } elseif ($isCssZero($marginLeft) && $isCssAuto($marginRight)) {
            $derivedAlignment = 'left';
        } else {
            $ml = $normalizeCssNumber($marginLeft);
            $mr = $normalizeCssNumber($marginRight);
            if ($ml !== null && $mr !== null) {
                if (abs($ml - $mr) < 1.0 && $ml > 0 && $mr > 0) {
                    $derivedAlignment = 'center';
                } elseif ($mr < 1.0 && $ml > 1.0) {
                    $derivedAlignment = 'right';
                } elseif ($ml < 1.0 && $mr > 1.0) {
                    $derivedAlignment = 'left';
                }
            }
        }
    }

    // Extract current values with defaults
    $currentSize = $rawValue['size'] ?? $derivedSize ?? ($defaultValue['size'] ?? 'full');
    $currentCustomWidth = $rawValue['customWidth'] ?? $derivedCustomWidth ?? ($defaultValue['customWidth'] ?? '100%');
    $currentCustomHeight = $rawValue['customHeight'] ?? $derivedCustomHeight ?? ($defaultValue['customHeight'] ?? 'auto');
    $currentAlignment = $rawValue['alignment'] ?? $derivedAlignment ?? ($defaultValue['alignment'] ?? 'center');
    $currentObjectFit = $rawValue['objectFit'] ?? ($defaultValue['objectFit'] ?? 'contain');
    $currentBorderRadius = $rawValue['borderRadius'] ?? ($defaultValue['borderRadius'] ?? '0');
    $boxShadow = $normalizeCssValue($rawValue['boxShadow'] ?? '');
    $currentShadow = $rawValue['shadow'] ?? ($boxShadow !== '' && strtolower($boxShadow) !== 'none');
    $currentLightbox = $rawValue['lightbox'] ?? ($defaultValue['lightbox'] ?? false);
    $currentLazyLoad = $rawValue['lazyLoad'] ?? ($defaultValue['lazyLoad'] ?? true);

    // Image URL - can be passed via props, value array, or from element src attribute
    $currentImageUrl = $imageUrl ?: ($rawValue['src'] ?? ($rawValue['imageUrl'] ?? ($defaultValue['imageUrl'] ?? '')));

    // Size presets
    $sizes = $options['sizes'] ?? [
        'full' => 'Pelny (100%)',
        'large' => 'Duzy (75%)',
        'medium' => 'Sredni (50%)',
        'small' => 'Maly (25%)',
        'custom' => 'Wlasny',
    ];

    // Object fit options
    $objectFits = $options['objectFits'] ?? [
        'contain' => 'Zawiera',
        'cover' => 'Pokrywa',
        'fill' => 'Wypelnia',
        'none' => 'Brak',
        'scale-down' => 'Skaluj',
    ];

    // Border radius presets
    $borderRadiusPresets = $options['borderRadiusPresets'] ?? [
        '0' => 'Brak',
        '0.25rem' => 'XS',
        '0.5rem' => 'S',
        '0.75rem' => 'M',
        '1rem' => 'L',
        '50%' => 'Okrag',
    ];
@endphp

<div
    class="uve-control uve-control--image-settings"
    x-data="uveImageSettingsControl({
        imageUrl: '{{ $currentImageUrl }}',
        size: '{{ $currentSize }}',
        customWidth: '{{ $currentCustomWidth }}',
        customHeight: '{{ $currentCustomHeight }}',
        alignment: '{{ $currentAlignment }}',
        objectFit: '{{ $currentObjectFit }}',
        borderRadius: '{{ $currentBorderRadius }}',
        shadow: {{ $currentShadow ? 'true' : 'false' }},
        lightbox: {{ $currentLightbox ? 'true' : 'false' }},
        lazyLoad: {{ $currentLazyLoad ? 'true' : 'false' }}
    })"
    wire:ignore.self
>
    {{-- Image Preview (clickable for full-size) --}}
    <div
        class="uve-img-preview"
        :class="{ 'uve-img-preview--clickable': imageUrl, 'uve-img-preview--empty': !imageUrl }"
        @click="imageUrl && (showFullPreview = true)"
        :title="imageUrl ? 'Kliknij aby zobaczyc pelny rozmiar' : 'Brak obrazu'"
    >
        <template x-if="imageUrl">
            <img :src="imageUrl" alt="Podglad obrazu" class="uve-img-preview__image"/>
        </template>
        <span class="uve-img-preview__empty-text" x-show="!imageUrl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Brak obrazu</span>
        </span>
        <span class="uve-img-preview__zoom" x-show="imageUrl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
            </svg>
        </span>
    </div>

    {{-- Full-size Preview Modal --}}
    <template x-teleport="body">
        <div
            x-show="showFullPreview"
            x-cloak
            class="uve-img-fullpreview-overlay"
            @click.self="showFullPreview = false"
            @keydown.escape.window="showFullPreview = false"
        >
            <div class="uve-img-fullpreview-container">
                <button
                    type="button"
                    @click="showFullPreview = false"
                    class="uve-img-fullpreview-close"
                    title="Zamknij"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <img
                    :src="imageUrl"
                    alt="Podglad obrazu"
                    class="uve-img-fullpreview-image"
                />
            </div>
        </div>
    </template>

    {{-- Image URL Input --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Zrodlo obrazu</label>
        <div class="uve-img-url-input">
            <input
                type="text"
                x-model="imageUrl"
                @input="emitChange()"
                class="uve-input"
                placeholder="https://..."
            />
            <button
                type="button"
                wire:click="openMediaPicker('{{ $selectedElementId }}')"
                class="uve-btn uve-btn-sm"
                title="Wybierz z biblioteki"
                @disabled(!$selectedElementId)
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Size Presets --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Rozmiar</label>
        <div class="uve-btn-group-full">
            @foreach($sizes as $sizeKey => $sizeLabel)
                <button
                    type="button"
                    @click="setSize('{{ $sizeKey }}')"
                    class="uve-btn uve-btn-sm"
                    :class="{ 'uve-btn-active': size === '{{ $sizeKey }}' }"
                    title="{{ $sizeLabel }}"
                >
                    @if($sizeKey === 'full')
                        <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/>
                        </svg>
                    @elseif($sizeKey === 'large')
                        <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="4" y="4" width="16" height="16" rx="2" stroke-width="2"/>
                        </svg>
                    @elseif($sizeKey === 'medium')
                        <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="6" y="6" width="12" height="12" rx="2" stroke-width="2"/>
                        </svg>
                    @elseif($sizeKey === 'small')
                        <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="8" y="8" width="8" height="8" rx="1" stroke-width="2"/>
                        </svg>
                    @else
                        <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Custom Size (shown when size = 'custom') --}}
    <div class="uve-control__field" x-show="size === 'custom'" x-cloak>
        <label class="uve-control__label">Wymiary wlasne</label>
        <div class="uve-control__row">
            <div class="uve-control__col">
                <label class="uve-control__sublabel">Szer.</label>
                <input
                    type="text"
                    class="uve-input uve-input--sm"
                    x-model="customWidth"
                    @change="emitChange()"
                    placeholder="100%"
                >
            </div>
            <div class="uve-control__col">
                <label class="uve-control__sublabel">Wys.</label>
                <input
                    type="text"
                    class="uve-input uve-input--sm"
                    x-model="customHeight"
                    @change="emitChange()"
                    placeholder="auto"
                >
            </div>
        </div>
    </div>

    {{-- Alignment --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Wyrownanie</label>
        <div class="uve-btn-group-full">
            <button
                type="button"
                @click="setAlignment('left')"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': alignment === 'left' }"
                title="Do lewej"
            >
                <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8M4 18h16"/>
                </svg>
            </button>
            <button
                type="button"
                @click="setAlignment('center')"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': alignment === 'center' }"
                title="Wysrodkowany"
            >
                <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M8 12h8M4 18h16"/>
                </svg>
            </button>
            <button
                type="button"
                @click="setAlignment('right')"
                class="uve-btn uve-btn-sm"
                :class="{ 'uve-btn-active': alignment === 'right' }"
                title="Do prawej"
            >
                <svg class="uve-control__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M12 12h8M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Object Fit --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Dopasowanie</label>
        <select
            class="uve-select"
            x-model="objectFit"
            @change="emitChange()"
        >
            @foreach($objectFits as $fitKey => $fitLabel)
                <option value="{{ $fitKey }}">{{ $fitLabel }}</option>
            @endforeach
        </select>
    </div>

    {{-- Border Radius --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Zaokraglenie</label>
        <div class="uve-size-presets">
            @foreach($borderRadiusPresets as $radiusValue => $radiusLabel)
                <button
                    type="button"
                    @click="setBorderRadius('{{ $radiusValue }}')"
                    class="uve-size-preset-btn"
                    :class="{ 'uve-size-preset-btn--active': borderRadius === '{{ $radiusValue }}' }"
                    title="{{ $radiusLabel }}"
                >
                    {{ $radiusLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Toggles --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Opcje</label>
        <div class="uve-control__toggles">
            <label class="uve-control__toggle">
                <input
                    type="checkbox"
                    class="uve-checkbox"
                    x-model="shadow"
                    @change="emitChange()"
                >
                <span class="uve-control__toggle-text">Cien</span>
            </label>
            <label class="uve-control__toggle">
                <input
                    type="checkbox"
                    class="uve-checkbox"
                    x-model="lightbox"
                    @change="emitChange()"
                >
                <span class="uve-control__toggle-text">Lightbox</span>
            </label>
            <label class="uve-control__toggle">
                <input
                    type="checkbox"
                    class="uve-checkbox"
                    x-model="lazyLoad"
                    @change="emitChange()"
                >
                <span class="uve-control__toggle-text">Lazy Load</span>
            </label>
        </div>
    </div>
</div>

<style>
/* Image Settings Control Styles - PPM CSS-FIRST */
.uve-control--image-settings {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.uve-control__field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.uve-control__label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.uve-control__sublabel {
    font-size: 0.65rem;
    color: #64748b;
    margin-bottom: 0.125rem;
}

.uve-control__row {
    display: flex;
    gap: 0.5rem;
}

.uve-control__col {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.uve-control__icon {
    width: 1rem;
    height: 1rem;
    pointer-events: none; /* FIX: Allow clicks to pass through to parent button */
}

/* Button Group Full Width */
.uve-control--image-settings .uve-btn-group-full {
    display: flex;
}

.uve-control--image-settings .uve-btn-group-full .uve-btn {
    flex: 1;
    border-radius: 0;
    justify-content: center;
    padding: 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s ease;
}

.uve-control--image-settings .uve-btn-group-full .uve-btn:first-child {
    border-radius: 0.375rem 0 0 0.375rem;
}

.uve-control--image-settings .uve-btn-group-full .uve-btn:last-child {
    border-radius: 0 0.375rem 0.375rem 0;
}

.uve-control--image-settings .uve-btn-group-full .uve-btn:not(:last-child) {
    border-right: none;
}

.uve-control--image-settings .uve-btn-group-full .uve-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-control--image-settings .uve-btn-group-full .uve-btn-active,
.uve-control--image-settings .uve-btn-group-full .uve-btn.uve-btn-active {
    background: var(--mpp-primary, #e0ac7e);
    border-color: var(--mpp-primary, #e0ac7e);
    color: #0f172a;
}

/* Size Presets */
.uve-control--image-settings .uve-size-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.uve-control--image-settings .uve-size-preset-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: #94a3b8;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.uve-control--image-settings .uve-size-preset-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-control--image-settings .uve-size-preset-btn--active {
    background: var(--mpp-primary, #e0ac7e);
    border-color: var(--mpp-primary, #e0ac7e);
    color: #0f172a;
}

/* Input and Select */
.uve-control--image-settings .uve-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: #1e293b;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    color: #e2e8f0;
    font-size: 0.8rem;
    transition: border-color 0.15s ease;
}

.uve-control--image-settings .uve-input:focus {
    outline: none;
    border-color: var(--mpp-primary, #e0ac7e);
}

.uve-control--image-settings .uve-input--sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

.uve-control--image-settings .uve-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: #1e293b;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    color: #e2e8f0;
    font-size: 0.8rem;
    cursor: pointer;
    transition: border-color 0.15s ease;
}

.uve-control--image-settings .uve-select:focus {
    outline: none;
    border-color: var(--mpp-primary, #e0ac7e);
}

/* Toggles */
.uve-control__toggles {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.uve-control__toggle {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    cursor: pointer;
}

.uve-control__toggle-text {
    font-size: 0.75rem;
    color: #cbd5e1;
}

.uve-control--image-settings .uve-checkbox {
    width: 1rem;
    height: 1rem;
    accent-color: var(--mpp-primary, #e0ac7e);
    cursor: pointer;
}

/* Image Preview */
.uve-img-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100px;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
    background-image:
        linear-gradient(45deg, #334155 25%, transparent 25%),
        linear-gradient(-45deg, #334155 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #334155 75%),
        linear-gradient(-45deg, transparent 75%, #334155 75%);
    background-size: 10px 10px;
    background-position: 0 0, 0 5px, 5px -5px, -5px 0px;
    position: relative;
    overflow: hidden;
}

.uve-img-preview--clickable {
    cursor: pointer;
}

.uve-img-preview--clickable:hover {
    border-color: var(--mpp-primary, #e0ac7e);
}

.uve-img-preview--empty {
    border-style: dashed;
}

.uve-img-preview__image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.uve-img-preview__empty-text {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #64748b;
    background: rgba(15, 23, 42, 0.8);
    padding: 0.5rem 0.75rem;
    border-radius: 0.25rem;
}

.uve-img-preview__zoom {
    position: absolute;
    bottom: 0.375rem;
    right: 0.375rem;
    background: rgba(15, 23, 42, 0.8);
    padding: 0.25rem;
    border-radius: 0.25rem;
    color: #94a3b8;
    transition: all 0.15s;
}

.uve-img-preview--clickable:hover .uve-img-preview__zoom {
    color: var(--mpp-primary, #e0ac7e);
}

/* Full Preview Modal (Lightbox) */
.uve-img-fullpreview-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 2rem;
}

.uve-img-fullpreview-container {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
}

.uve-img-fullpreview-close {
    position: absolute;
    top: -2rem;
    right: -2rem;
    background: #334155;
    border: none;
    border-radius: 50%;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e2e8f0;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-img-fullpreview-close:hover {
    background: var(--mpp-primary, #e0ac7e);
    color: #0f172a;
}

.uve-img-fullpreview-image {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 0.5rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* URL Input with picker button */
.uve-img-url-input {
    display: flex;
    gap: 0.375rem;
}

.uve-img-url-input .uve-input {
    flex: 1;
}

.uve-img-url-input .uve-btn {
    flex-shrink: 0;
    padding: 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-img-url-input .uve-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

/* x-cloak for Alpine */
[x-cloak] {
    display: none !important;
}
</style>

{{-- Alpine component 'uveImageSettingsControl' defined in resources/js/app.js --}}
