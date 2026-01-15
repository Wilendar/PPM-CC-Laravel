{{--
    Slider Settings Control - ETAP_07f_P5 FAZA PP.3
    Konfiguracja Splide.js dla slajderow:
    - type: slide | loop | fade
    - perPage: liczba slajdow na strone
    - autoplay: wlacz/wylacz + interval
    - arrows/pagination: nawigacja
    - speed: predkosc przejscia
    - gap: przerwa miedzy slajdami
    - breakpoints: responsive config
--}}
@props([
    'controlId' => 'slider-settings',
    'value' => [],
    'options' => [],
    'onChange' => null,
])

@php
    $typeOptions = $options['types'] ?? [
        'slide' => 'Slide',
        'loop' => 'Loop',
        'fade' => 'Fade',
    ];
    $perPageOptions = $options['perPage'] ?? [1, 2, 3, 4, 5, 6];
    $speedOptions = $options['speeds'] ?? [
        300 => '0.3s',
        400 => '0.4s (domyslnie)',
        500 => '0.5s',
        600 => '0.6s',
        800 => '0.8s',
        1000 => '1s',
    ];
@endphp

<div
    class="uve-control uve-control--slider-settings"
    x-data="uveSliderSettingsControl(@js($value))"
    wire:ignore.self
>
    {{-- Preview --}}
    <div class="uve-slider-preview">
        <div class="uve-slider-preview__track">
            <template x-for="i in previewSlides" :key="i">
                <div
                    class="uve-slider-preview__slide"
                    :class="{ 'uve-slider-preview__slide--active': i === 1 }"
                >
                    <span x-text="i"></span>
                </div>
            </template>
        </div>
        <div class="uve-slider-preview__info">
            <span x-text="typeLabel"></span> |
            <span x-text="perPage + '/str'"></span>
        </div>
    </div>

    {{-- Type Selection --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Typ slidera</label>
        <div class="uve-btn-group-full">
            @foreach($typeOptions as $typeVal => $typeLabel)
                <button
                    type="button"
                    @click="setType('{{ $typeVal }}')"
                    class="uve-btn uve-btn-sm"
                    :class="{ 'uve-btn-active': type === '{{ $typeVal }}' }"
                >
                    {{ $typeLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Per Page --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Slajdow na strone</label>
        <div class="uve-slider-perpage">
            <input
                type="range"
                x-model="perPage"
                @input="emitChange()"
                min="1"
                max="6"
                step="1"
                class="uve-slider"
            />
            <span class="uve-slider-value" x-text="perPage"></span>
        </div>
    </div>

    {{-- Autoplay Section --}}
    <div class="uve-control__field">
        <div class="uve-control__header-row">
            <label class="uve-control__label">Autoplay</label>
            <label class="uve-toggle">
                <input
                    type="checkbox"
                    x-model="autoplay"
                    @change="emitChange()"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-slider"></span>
            </label>
        </div>

        <template x-if="autoplay">
            <div class="uve-slider-autoplay-options">
                <label class="uve-control__label uve-control__label--sm">Interval (ms)</label>
                <div class="uve-input-with-unit">
                    <input
                        type="number"
                        x-model="interval"
                        @input="emitChange()"
                        min="1000"
                        max="10000"
                        step="500"
                        class="uve-input"
                    />
                    <span class="uve-input-unit">ms</span>
                </div>

                <label class="uve-toggle-row">
                    <input
                        type="checkbox"
                        x-model="pauseOnHover"
                        @change="emitChange()"
                        class="uve-checkbox"
                    />
                    <span class="uve-toggle-label">Pauza przy hover</span>
                </label>

                <label class="uve-toggle-row">
                    <input
                        type="checkbox"
                        x-model="pauseOnFocus"
                        @change="emitChange()"
                        class="uve-checkbox"
                    />
                    <span class="uve-toggle-label">Pauza przy focus</span>
                </label>
            </div>
        </template>
    </div>

    {{-- Navigation --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Nawigacja</label>
        <div class="uve-slider-nav-options">
            <label class="uve-toggle-row">
                <input
                    type="checkbox"
                    x-model="arrows"
                    @change="emitChange()"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-label">
                    <svg class="uve-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Strzalki
                </span>
            </label>

            <label class="uve-toggle-row">
                <input
                    type="checkbox"
                    x-model="pagination"
                    @change="emitChange()"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-label">
                    <svg class="uve-icon" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="6" cy="12" r="2"/>
                        <circle cx="12" cy="12" r="2"/>
                        <circle cx="18" cy="12" r="2"/>
                    </svg>
                    Kropki (pagination)
                </span>
            </label>
        </div>
    </div>

    {{-- Speed --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Predkosc przejscia</label>
        <select x-model="speed" @change="emitChange()" class="uve-select">
            @foreach($speedOptions as $speedVal => $speedLabel)
                <option value="{{ $speedVal }}">{{ $speedLabel }}</option>
            @endforeach
        </select>
    </div>

    {{-- Gap --}}
    <div class="uve-control__field">
        <label class="uve-control__label">Przerwa miedzy slajdami</label>
        <div class="uve-input-with-unit">
            <input
                type="number"
                x-model="gap"
                @input="emitChange()"
                min="0"
                max="100"
                step="4"
                class="uve-input"
            />
            <select x-model="gapUnit" @change="emitChange()" class="uve-select uve-select--unit">
                <option value="px">px</option>
                <option value="rem">rem</option>
                <option value="%">%</option>
            </select>
        </div>
    </div>

    {{-- Responsive Breakpoints Accordion --}}
    <div class="uve-control__field">
        <button
            type="button"
            @click="showBreakpoints = !showBreakpoints"
            class="uve-accordion-toggle"
        >
            <span>Ustawienia responsywne</span>
            <svg
                class="uve-accordion-icon"
                :class="{ 'uve-accordion-icon--open': showBreakpoints }"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <template x-if="showBreakpoints">
            <div class="uve-slider-breakpoints">
                {{-- Tablet Breakpoint --}}
                <div class="uve-breakpoint-item">
                    <div class="uve-breakpoint-header">
                        <svg class="uve-breakpoint-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                            <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Tablet (&lt;1024px)</span>
                    </div>
                    <div class="uve-breakpoint-fields">
                        <label class="uve-control__label uve-control__label--sm">Slajdow/str</label>
                        <input
                            type="number"
                            x-model="breakpoints.tablet.perPage"
                            @input="emitChange()"
                            min="1"
                            max="4"
                            class="uve-input uve-input--sm"
                        />
                    </div>
                </div>

                {{-- Mobile Breakpoint --}}
                <div class="uve-breakpoint-item">
                    <div class="uve-breakpoint-header">
                        <svg class="uve-breakpoint-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="6" y="2" width="12" height="20" rx="2" stroke-width="2"/>
                            <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Mobile (&lt;768px)</span>
                    </div>
                    <div class="uve-breakpoint-fields">
                        <label class="uve-control__label uve-control__label--sm">Slajdow/str</label>
                        <input
                            type="number"
                            x-model="breakpoints.mobile.perPage"
                            @input="emitChange()"
                            min="1"
                            max="2"
                            class="uve-input uve-input--sm"
                        />
                    </div>
                </div>
            </div>
        </template>
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
/* Slider Settings Control Styles */
.uve-control--slider-settings {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Preview */
.uve-slider-preview {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    padding: 0.75rem;
    overflow: hidden;
}

.uve-slider-preview__track {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.uve-slider-preview__slide {
    flex: 1;
    min-width: 0;
    height: 32px;
    background: #334155;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: #64748b;
    transition: all 0.2s;
}

.uve-slider-preview__slide--active {
    background: #e0ac7e;
    color: #0f172a;
    font-weight: 600;
}

.uve-slider-preview__info {
    font-size: 0.7rem;
    color: #64748b;
    text-align: center;
}

/* Per Page Slider */
.uve-slider-perpage {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.uve-slider-perpage .uve-slider {
    flex: 1;
}

.uve-slider-value {
    width: 28px;
    height: 28px;
    background: #334155;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: #e0ac7e;
}

/* Header Row */
.uve-control__header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

/* Toggle Switch */
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

/* Autoplay Options */
.uve-slider-autoplay-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.5rem;
    background: #1e293b;
    border-radius: 0.25rem;
    margin-top: 0.25rem;
}

/* Navigation Options */
.uve-slider-nav-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-toggle-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.uve-toggle-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: #e2e8f0;
}

.uve-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* Input with Unit */
.uve-input-with-unit {
    display: flex;
    gap: 0.25rem;
}

.uve-input-with-unit .uve-input {
    flex: 1;
}

.uve-input-unit {
    padding: 0.375rem 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    color: #94a3b8;
}

.uve-select--unit {
    width: auto;
    min-width: 60px;
}

/* Accordion */
.uve-accordion-toggle {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    color: #e2e8f0;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-accordion-toggle:hover {
    background: #334155;
}

.uve-accordion-icon {
    width: 16px;
    height: 16px;
    transition: transform 0.2s;
}

.uve-accordion-icon--open {
    transform: rotate(180deg);
}

/* Breakpoints */
.uve-slider-breakpoints {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.uve-breakpoint-item {
    padding: 0.5rem;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.25rem;
}

.uve-breakpoint-header {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.375rem;
}

.uve-breakpoint-icon {
    width: 14px;
    height: 14px;
}

.uve-breakpoint-fields {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.uve-breakpoint-fields .uve-control__label--sm {
    font-size: 0.7rem;
    margin: 0;
}

.uve-input--sm {
    width: 60px;
    padding: 0.25rem 0.375rem;
    font-size: 0.75rem;
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
</style>

<script>
function uveSliderSettingsControl(initialValue) {
    return {
        // Main settings
        type: initialValue.type || 'loop',
        perPage: parseInt(initialValue.perPage) || 3,
        autoplay: initialValue.autoplay ?? true,
        interval: parseInt(initialValue.interval) || 3000,
        pauseOnHover: initialValue.pauseOnHover ?? true,
        pauseOnFocus: initialValue.pauseOnFocus ?? true,
        arrows: initialValue.arrows ?? true,
        pagination: initialValue.pagination ?? true,
        speed: parseInt(initialValue.speed) || 400,
        gap: parseInt(initialValue.gap) || 16,
        gapUnit: initialValue.gapUnit || 'px',

        // Breakpoints
        breakpoints: {
            tablet: {
                perPage: initialValue.breakpoints?.tablet?.perPage || 2,
            },
            mobile: {
                perPage: initialValue.breakpoints?.mobile?.perPage || 1,
            },
        },

        // UI state
        showBreakpoints: false,

        get previewSlides() {
            return Math.min(this.perPage + 1, 6);
        },

        get typeLabel() {
            const labels = { slide: 'Slide', loop: 'Loop', fade: 'Fade' };
            return labels[this.type] || this.type;
        },

        setType(newType) {
            this.type = newType;
            // Fade type only supports perPage: 1
            if (newType === 'fade') {
                this.perPage = 1;
            }
            this.emitChange();
        },

        resetToDefaults() {
            this.type = 'loop';
            this.perPage = 3;
            this.autoplay = true;
            this.interval = 3000;
            this.pauseOnHover = true;
            this.pauseOnFocus = true;
            this.arrows = true;
            this.pagination = true;
            this.speed = 400;
            this.gap = 16;
            this.gapUnit = 'px';
            this.breakpoints = {
                tablet: { perPage: 2 },
                mobile: { perPage: 1 },
            };
            this.emitChange();
        },

        emitChange() {
            const value = {
                type: this.type,
                perPage: parseInt(this.perPage),
                autoplay: this.autoplay,
                interval: parseInt(this.interval),
                pauseOnHover: this.pauseOnHover,
                pauseOnFocus: this.pauseOnFocus,
                arrows: this.arrows,
                pagination: this.pagination,
                speed: parseInt(this.speed),
                gap: this.gap + this.gapUnit,
                breakpoints: {
                    1024: {
                        perPage: parseInt(this.breakpoints.tablet.perPage),
                    },
                    768: {
                        perPage: parseInt(this.breakpoints.mobile.perPage),
                    },
                },
            };

            this.$wire.updateControlValue('slider-settings', value);
        }
    }
}
</script>
