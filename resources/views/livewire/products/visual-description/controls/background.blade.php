{{--
    Background Control - ETAP_07f_P5 FAZA PP.2
    Kontrolka do edycji tla: background-color, background-image, background-size,
    background-position, background-repeat, background-attachment
--}}
@props([
    'controlId' => 'background',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $sizeOptions = $options['sizes'] ?? [
        'auto' => 'Auto',
        'cover' => 'Cover',
        'contain' => 'Contain',
        '100% 100%' => '100%',
    ];
    $repeatOptions = $options['repeats'] ?? [
        'no-repeat' => 'Brak',
        'repeat' => 'Powtarzaj',
        'repeat-x' => 'Poziomo',
        'repeat-y' => 'Pionowo',
    ];
    $attachmentOptions = $options['attachments'] ?? [
        'scroll' => 'Scroll',
        'fixed' => 'Fixed',
        'local' => 'Local',
    ];
@endphp

<div
    class="uve-control uve-control--background"
    x-data="uveBackgroundControl(@js($value))"
    wire:ignore.self
>
    {{-- Background Preview (clickable for full-size) --}}
    <div
        class="uve-bg-preview"
        :class="{ 'uve-bg-preview--clickable': backgroundImage }"
        :style="previewStyle"
        @click="backgroundImage && (showFullPreview = true)"
        :title="backgroundImage ? 'Kliknij aby zobaczyc pelny rozmiar' : ''"
    >
        <span class="uve-bg-preview-text" x-show="!hasBackground">Brak tla</span>
        <span class="uve-bg-preview-zoom" x-show="backgroundImage">
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
            class="uve-bg-fullpreview-overlay"
            @click.self="showFullPreview = false"
            @keydown.escape.window="showFullPreview = false"
        >
            <div class="uve-bg-fullpreview-container">
                <button
                    type="button"
                    @click="showFullPreview = false"
                    class="uve-bg-fullpreview-close"
                    title="Zamknij"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <img
                    :src="backgroundImage"
                    :alt="'Podglad tla'"
                    class="uve-bg-fullpreview-image"
                />
            </div>
        </div>
    </template>

    {{-- Background Type Tabs --}}
    <div class="uve-bg-tabs">
        <button
            type="button"
            @click="activeTab = 'color'"
            class="uve-bg-tab"
            :class="{ 'uve-bg-tab--active': activeTab === 'color' }"
        >
            Kolor
        </button>
        <button
            type="button"
            @click="activeTab = 'image'"
            class="uve-bg-tab"
            :class="{ 'uve-bg-tab--active': activeTab === 'image' }"
        >
            Obraz
        </button>
        <button
            type="button"
            @click="activeTab = 'gradient'"
            class="uve-bg-tab"
            :class="{ 'uve-bg-tab--active': activeTab === 'gradient' }"
        >
            Gradient
        </button>
    </div>

    {{-- Color Tab --}}
    <div class="uve-bg-content" x-show="activeTab === 'color'">
        <div class="uve-control__field">
            <label class="uve-control__label">Kolor tla</label>
            <div class="uve-color-row">
                <div class="uve-color-preview-wrapper">
                    <input
                        type="color"
                        x-model="backgroundColor"
                        @input="emitChange()"
                        class="uve-color-native-input"
                    />
                    <div class="uve-color-preview" :style="'background-color: ' + backgroundColor"></div>
                </div>
                <input
                    type="text"
                    x-model="backgroundColor"
                    @input="emitChange()"
                    class="uve-input uve-input--color-hex"
                    placeholder="#ffffff"
                />
            </div>

            {{-- Color Presets --}}
            <div class="uve-color-swatches">
                <button type="button" @click="setColor('#ffffff')" class="uve-color-swatch" style="background: #ffffff" title="White"></button>
                <button type="button" @click="setColor('#f6f6f6')" class="uve-color-swatch" style="background: #f6f6f6" title="Light Gray"></button>
                <button type="button" @click="setColor('#1a1a1a')" class="uve-color-swatch" style="background: #1a1a1a" title="Dark"></button>
                <button type="button" @click="setColor('#000000')" class="uve-color-swatch" style="background: #000000" title="Black"></button>
                <button type="button" @click="setColor('#e0ac7e')" class="uve-color-swatch" style="background: #e0ac7e" title="Brand Gold"></button>
                <button type="button" @click="setColor('#ef8248')" class="uve-color-swatch" style="background: #ef8248" title="Brand Orange"></button>
                <button type="button" @click="setColor('transparent')" class="uve-color-swatch uve-color-swatch--transparent" title="Transparent">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Image Tab --}}
    <div class="uve-bg-content" x-show="activeTab === 'image'">
        {{-- Image URL --}}
        <div class="uve-control__field">
            <label class="uve-control__label">URL obrazu</label>
            <div class="uve-bg-image-input">
                <input
                    type="text"
                    x-model="backgroundImage"
                    @input="emitChange()"
                    class="uve-input"
                    placeholder="https://..."
                />
                <button
                    type="button"
                    wire:click="openMediaPicker"
                    class="uve-btn uve-btn-sm"
                    title="Wybierz z biblioteki"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Background Size --}}
        <div class="uve-control__field">
            <label class="uve-control__label">Rozmiar</label>
            <div class="uve-btn-group-full">
                @foreach($sizeOptions as $sizeVal => $sizeLabel)
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

        {{-- Background Position --}}
        <div class="uve-control__field">
            <label class="uve-control__label">Pozycja</label>
            <div class="uve-position-grid">
                <template x-for="pos in positions" :key="pos.value">
                    <button
                        type="button"
                        @click="backgroundPosition = pos.value; emitChange()"
                        class="uve-position-btn"
                        :class="{ 'uve-position-btn--active': backgroundPosition === pos.value }"
                        :title="pos.label"
                    >
                        <span class="uve-position-dot"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Background Repeat --}}
        <div class="uve-control__field">
            <label class="uve-control__label">Powtarzanie</label>
            <select x-model="backgroundRepeat" @change="emitChange()" class="uve-select">
                @foreach($repeatOptions as $repeatVal => $repeatLabel)
                    <option value="{{ $repeatVal }}">{{ $repeatLabel }}</option>
                @endforeach
            </select>
        </div>

        {{-- Background Attachment --}}
        <div class="uve-control__field">
            <label class="uve-control__label">Przypiecie</label>
            <div class="uve-btn-group-full">
                @foreach($attachmentOptions as $attachVal => $attachLabel)
                    <button
                        type="button"
                        @click="backgroundAttachment = '{{ $attachVal }}'; emitChange()"
                        class="uve-btn uve-btn-sm"
                        :class="{ 'uve-btn-active': backgroundAttachment === '{{ $attachVal }}' }"
                    >
                        {{ $attachLabel }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Gradient Tab - FIX #14: Full inline gradient editor --}}
    <div class="uve-bg-content" x-show="activeTab === 'gradient'" x-data="uveGradientEditorInline()">
        {{-- Gradient Preview --}}
        <div class="uve-gradient-preview-lg" :style="'background: ' + gradientCss"></div>

        {{-- Gradient Type --}}
        <div class="uve-control__field">
            <label class="uve-control__label">Typ gradientu</label>
            <div class="uve-btn-group-full">
                <button type="button" @click="gradientType = 'linear'; updateParent()" class="uve-btn uve-btn-sm" :class="{ 'uve-btn-active': gradientType === 'linear' }">Liniowy</button>
                <button type="button" @click="gradientType = 'radial'; updateParent()" class="uve-btn uve-btn-sm" :class="{ 'uve-btn-active': gradientType === 'radial' }">Radialny</button>
            </div>
        </div>

        {{-- Angle Control (linear only) --}}
        <div class="uve-control__field" x-show="gradientType === 'linear'">
            <label class="uve-control__label">Kat</label>
            <div class="uve-gradient-angle-row">
                <input type="range" x-model.number="angle" @input="updateParent()" min="0" max="360" class="uve-range" />
                <input type="number" x-model.number="angle" @input="updateParent()" min="0" max="360" class="uve-input uve-input--sm" style="width: 60px" />
                <span class="uve-gradient-angle-unit">deg</span>
            </div>
            <div class="uve-gradient-angle-presets">
                <template x-for="preset in [0, 45, 90, 135, 180, 225, 270, 315]" :key="preset">
                    <button type="button" @click="angle = preset; updateParent()" class="uve-btn uve-btn-xs" :class="{ 'uve-btn-active': angle === preset }" x-text="preset + '\u00B0'"></button>
                </template>
            </div>
        </div>

        {{-- Color Stops --}}
        <div class="uve-control__field">
            <div class="uve-stops-header">
                <label class="uve-control__label">Kolory</label>
                <button type="button" @click="addStop()" class="uve-btn uve-btn-xs" :disabled="colorStops.length >= 5" title="Dodaj kolor">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </button>
            </div>

            {{-- Color stops bar - FIX #14b: Draggable markers --}}
            <div class="uve-gradient-stops-bar" :style="'background: ' + gradientCss">
                <template x-for="(stop, index) in colorStops" :key="index">
                    <div
                        class="uve-gradient-stop-marker"
                        :class="{ 'uve-gradient-stop-marker--selected': selectedStop === index, 'uve-gradient-stop-marker--dragging': draggingIndex === index }"
                        :style="'left: ' + stop.position + '%'"
                        @mousedown="startDrag(index, $event)"
                        @touchstart="startDrag(index, $event)"
                    >
                        <div class="uve-gradient-stop-handle" :style="'background: ' + stop.color"></div>
                    </div>
                </template>
            </div>

            {{-- Color stops list --}}
            <div class="uve-stops-list">
                <template x-for="(stop, index) in colorStops" :key="index">
                    <div class="uve-stop-item" :class="{ 'uve-stop-item--selected': selectedStop === index }" @click="selectedStop = index">
                        <div class="uve-stop-color-wrapper">
                            <input type="color" :value="stop.color.startsWith('#') ? stop.color : '#888888'" @input="stop.color = $event.target.value; updateParent()" class="uve-stop-color-input" />
                            <div class="uve-stop-color-preview" :style="'background: ' + stop.color"></div>
                        </div>
                        <input type="text" x-model="stop.color" @input="updateParent()" class="uve-input uve-input--sm" style="width: 80px" placeholder="#ffffff" />
                        <input type="number" x-model.number="stop.position" @input="updateParent()" min="0" max="100" class="uve-input uve-input--sm" style="width: 50px" />
                        <span class="uve-stop-unit">%</span>
                        <button type="button" @click="removeStop(index)" class="uve-btn uve-btn-xs uve-btn-danger" :disabled="colorStops.length <= 2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- Gradient Presets --}}
        <div class="uve-control__field">
            <label class="uve-control__label">Gotowe gradienty</label>
            <div class="uve-gradient-presets-grid">
                <button type="button" @click="applyPreset('brand')" class="uve-gradient-preset-btn" style="background: linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c08449 100%)" title="Brand"></button>
                <button type="button" @click="applyPreset('cover')" class="uve-gradient-preset-btn" style="background: linear-gradient(180deg, #f6f6f6 70%, #ef8248 70%)" title="Cover"></button>
                <button type="button" @click="applyPreset('dark')" class="uve-gradient-preset-btn" style="background: linear-gradient(180deg, #1a1a1a 0%, #333333 100%)" title="Dark"></button>
                <button type="button" @click="applyPreset('light')" class="uve-gradient-preset-btn" style="background: linear-gradient(180deg, #ffffff 0%, #f6f6f6 100%)" title="Light"></button>
                <button type="button" @click="applyPreset('sunset')" class="uve-gradient-preset-btn" style="background: linear-gradient(135deg, #f6f6f6 0%, #ef8248 100%)" title="Sunset"></button>
                <button type="button" @click="applyPreset('ocean')" class="uve-gradient-preset-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)" title="Ocean"></button>
            </div>
        </div>

        {{-- CSS Output (readonly) --}}
        <div class="uve-control__field">
            <label class="uve-control__label">CSS</label>
            <textarea x-model="gradientCss" readonly class="uve-textarea uve-textarea--gradient" rows="2"></textarea>
        </div>
    </div>

    {{-- Clear Button --}}
    <div class="uve-bg-actions">
        <button
            type="button"
            @click="clearBackground()"
            class="uve-btn uve-btn-sm"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Wyczysc tlo
        </button>
    </div>
</div>

<style>
/* Background Control Styles */
.uve-control--background {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Background Preview */
.uve-bg-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 80px;
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

.uve-bg-preview-text {
    font-size: 0.75rem;
    color: #64748b;
    background: rgba(15, 23, 42, 0.8);
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

/* Tabs */
.uve-bg-tabs {
    display: flex;
    background: #1e293b;
    border-radius: 0.375rem;
    padding: 0.25rem;
}

.uve-bg-tab {
    flex: 1;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    background: transparent;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-bg-tab:hover {
    color: #e2e8f0;
}

.uve-bg-tab--active {
    background: #334155;
    color: #e0ac7e;
}

/* Content */
.uve-bg-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Color Swatches */
.uve-color-swatches {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    margin-top: 0.5rem;
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

.uve-color-swatch--transparent {
    background-image:
        linear-gradient(45deg, #334155 25%, transparent 25%),
        linear-gradient(-45deg, #334155 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #334155 75%),
        linear-gradient(-45deg, transparent 75%, #334155 75%);
    background-size: 6px 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
}

/* Image Input */
.uve-bg-image-input {
    display: flex;
    gap: 0.375rem;
}

.uve-bg-image-input .uve-input {
    flex: 1;
}

/* Position Grid */
.uve-position-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2px;
    width: 80px;
}

.uve-position-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-position-btn:hover {
    background: #475569;
}

.uve-position-btn--active {
    background: #e0ac7e;
    border-color: #e0ac7e;
}

.uve-position-dot {
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
}

.uve-position-btn--active .uve-position-dot {
    background: #0f172a;
}

/* Gradient Note */
.uve-bg-gradient-note {
    font-size: 0.75rem;
    color: #64748b;
    text-align: center;
    padding: 0.5rem;
}

/* FIX #13b: Gradient Preview */
.uve-gradient-preview {
    height: 60px;
    border-radius: 0.375rem;
    border: 2px solid #475569;
    margin-bottom: 0.5rem;
}

/* FIX #14: Gradient Editor Large Preview */
.uve-gradient-preview-lg {
    height: 80px;
    border-radius: 0.375rem;
    border: 2px solid #475569;
    margin-bottom: 0.75rem;
}

/* FIX #14: Gradient Angle Row */
.uve-gradient-angle-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.uve-range {
    flex: 1;
    -webkit-appearance: none;
    height: 6px;
    background: #334155;
    border-radius: 3px;
    outline: none;
}

.uve-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 14px;
    height: 14px;
    background: #e0ac7e;
    border-radius: 50%;
    cursor: pointer;
}

.uve-range::-moz-range-thumb {
    width: 14px;
    height: 14px;
    background: #e0ac7e;
    border-radius: 50%;
    cursor: pointer;
    border: none;
}

.uve-gradient-angle-unit {
    color: #64748b;
    font-size: 0.75rem;
}

.uve-gradient-angle-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

/* FIX #14: Gradient Stops Bar */
.uve-gradient-stops-bar {
    height: 20px;
    border-radius: 0.25rem;
    border: 1px solid #475569;
    position: relative;
    margin-bottom: 0.75rem;
}

.uve-gradient-stop-marker {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    cursor: grab;
    z-index: 1;
    user-select: none;
    touch-action: none;
}

.uve-gradient-stop-marker--selected {
    z-index: 2;
}

.uve-gradient-stop-marker--dragging {
    cursor: grabbing;
    z-index: 3;
}

.uve-gradient-stop-handle {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    transition: transform 0.1s, box-shadow 0.1s;
}

.uve-gradient-stop-marker:hover .uve-gradient-stop-handle,
.uve-gradient-stop-marker--selected .uve-gradient-stop-handle {
    transform: scale(1.2);
    border-color: #e0ac7e;
}

.uve-gradient-stop-marker--dragging .uve-gradient-stop-handle {
    transform: scale(1.3);
    border-color: #e0ac7e;
    box-shadow: 0 2px 6px rgba(224, 172, 126, 0.5);
}

/* FIX #14: Color Stops Header */
.uve-stops-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

/* FIX #14: Color Stops List */
.uve-stops-list {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.uve-stop-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem;
    background: #1e293b;
    border-radius: 0.25rem;
    border: 1px solid transparent;
    transition: all 0.1s;
}

.uve-stop-item--selected {
    border-color: #e0ac7e;
}

.uve-stop-color-wrapper {
    position: relative;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.uve-stop-color-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.uve-stop-color-preview {
    width: 100%;
    height: 100%;
    border-radius: 0.25rem;
    border: 2px solid #475569;
}

.uve-stop-unit {
    color: #64748b;
    font-size: 0.7rem;
}

/* FIX #14: Gradient Presets Grid */
.uve-gradient-presets-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 0.375rem;
}

.uve-gradient-preset-btn {
    aspect-ratio: 1;
    border-radius: 0.25rem;
    border: 2px solid #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-gradient-preset-btn:hover {
    border-color: #e0ac7e;
    transform: scale(1.05);
}

/* FIX #14: Button styles */
.uve-btn-xs {
    padding: 0.25rem 0.375rem;
    font-size: 0.7rem;
}

.uve-btn-danger {
    color: #f87171;
}

.uve-btn-danger:hover {
    background: rgba(248, 113, 113, 0.2);
}

.uve-btn-danger:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

/* FIX #13b: Gradient Textarea */
.uve-textarea--gradient {
    width: 100%;
    background: #1e293b;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    color: #e2e8f0;
    font-size: 0.75rem;
    font-family: monospace;
    padding: 0.5rem;
    resize: vertical;
}

.uve-textarea--gradient:focus {
    outline: none;
    border-color: #e0ac7e;
}

/* Actions */
.uve-bg-actions {
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-bg-actions .uve-btn {
    width: 100%;
    justify-content: center;
}

/* Clickable preview */
.uve-bg-preview--clickable {
    cursor: pointer;
    position: relative;
}

.uve-bg-preview--clickable:hover {
    border-color: #e0ac7e;
}

.uve-bg-preview-zoom {
    position: absolute;
    bottom: 0.375rem;
    right: 0.375rem;
    background: rgba(15, 23, 42, 0.8);
    padding: 0.25rem;
    border-radius: 0.25rem;
    color: #94a3b8;
    transition: all 0.15s;
}

.uve-bg-preview--clickable:hover .uve-bg-preview-zoom {
    color: #e0ac7e;
}

/* Full Preview Modal */
.uve-bg-fullpreview-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 2rem;
}

.uve-bg-fullpreview-container {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
}

.uve-bg-fullpreview-close {
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

.uve-bg-fullpreview-close:hover {
    background: #e0ac7e;
    color: #0f172a;
}

.uve-bg-fullpreview-image {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 0.5rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
</style>

{{-- Alpine component 'uveBackgroundControl' defined in resources/js/app.js --}}
