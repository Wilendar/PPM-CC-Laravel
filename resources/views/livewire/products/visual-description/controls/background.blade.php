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

    {{-- Gradient Tab --}}
    <div class="uve-bg-content" x-show="activeTab === 'gradient'">
        <p class="uve-bg-gradient-note">
            Uzyj kontrolki "Gradient" do szczegolowej edycji gradientow.
        </p>
        <button
            type="button"
            wire:click="openGradientEditor"
            class="uve-btn uve-btn-sm w-full"
        >
            Otworz edytor gradientow
        </button>
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
