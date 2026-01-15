{{--
    Responsive Images Control - ETAP_07f_P5 FAZA PP.3
    Rozne obrazy per breakpoint:
    - Desktop image (>1024px)
    - Tablet image (768px-1024px)
    - Mobile image (<768px)
    Z media pickerem dla kazdego breakpointu
--}}
@props([
    'controlId' => 'responsive-images',
    'value' => [],
    'options' => [],
    'onChange' => null,
    'productMedia' => [],
])

@php
    $breakpoints = $options['breakpoints'] ?? [
        'desktop' => [
            'label' => 'Desktop',
            'icon' => 'desktop',
            'min' => '1024px',
            'description' => '> 1024px',
        ],
        'tablet' => [
            'label' => 'Tablet',
            'icon' => 'tablet',
            'min' => '768px',
            'max' => '1024px',
            'description' => '768px - 1024px',
        ],
        'mobile' => [
            'label' => 'Mobile',
            'icon' => 'mobile',
            'max' => '768px',
            'description' => '< 768px',
        ],
    ];
@endphp

<div
    class="uve-control uve-control--responsive-images"
    x-data="uveResponsiveImagesControl(@js($value), @js($productMedia))"
    wire:ignore.self
>
    {{-- Overview Grid --}}
    <div class="uve-responsive-overview">
        <template x-for="(bp, key) in breakpointsData" :key="key">
            <div
                class="uve-responsive-preview-card"
                :class="{
                    'uve-responsive-preview-card--active': activeBreakpoint === key,
                    'uve-responsive-preview-card--has-image': bp.url
                }"
                @click="activeBreakpoint = key"
            >
                <div class="uve-responsive-preview-card__icon">
                    <template x-if="key === 'desktop'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/>
                            <line x1="8" y1="21" x2="16" y2="21" stroke-width="2"/>
                            <line x1="12" y1="17" x2="12" y2="21" stroke-width="2"/>
                        </svg>
                    </template>
                    <template x-if="key === 'tablet'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/>
                            <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </template>
                    <template x-if="key === 'mobile'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="6" y="2" width="12" height="20" rx="2" stroke-width="2"/>
                            <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </template>
                </div>
                <div class="uve-responsive-preview-card__label" x-text="bp.label"></div>
                <div class="uve-responsive-preview-card__thumb">
                    <template x-if="bp.url">
                        <img :src="bp.url" :alt="bp.label" />
                    </template>
                    <template x-if="!bp.url">
                        <span class="uve-responsive-preview-card__empty">Brak</span>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Active Breakpoint Editor --}}
    <div class="uve-responsive-editor">
        <div class="uve-responsive-editor__header">
            <span class="uve-responsive-editor__title" x-text="breakpointsData[activeBreakpoint].label"></span>
            <span class="uve-responsive-editor__range" x-text="breakpointsData[activeBreakpoint].description"></span>
        </div>

        {{-- Image Preview --}}
        <div class="uve-responsive-editor__preview">
            <template x-if="breakpointsData[activeBreakpoint].url">
                <div class="uve-responsive-editor__image-wrapper">
                    <img
                        :src="breakpointsData[activeBreakpoint].url"
                        :alt="breakpointsData[activeBreakpoint].alt || activeBreakpoint"
                    />
                    <button
                        type="button"
                        @click="clearImage(activeBreakpoint)"
                        class="uve-responsive-editor__clear-btn"
                        title="Usun obraz"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
            <template x-if="!breakpointsData[activeBreakpoint].url">
                <div
                    class="uve-responsive-editor__empty"
                    @click="showMediaPicker = true"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Dodaj obraz dla <span x-text="breakpointsData[activeBreakpoint].label"></span></span>
                </div>
            </template>
        </div>

        {{-- Image Source Selection --}}
        <div class="uve-responsive-editor__source">
            <div class="uve-responsive-source-tabs">
                <button
                    type="button"
                    @click="sourceTab = 'gallery'"
                    class="uve-responsive-source-tab"
                    :class="{ 'uve-responsive-source-tab--active': sourceTab === 'gallery' }"
                >
                    Galeria
                </button>
                <button
                    type="button"
                    @click="sourceTab = 'url'"
                    class="uve-responsive-source-tab"
                    :class="{ 'uve-responsive-source-tab--active': sourceTab === 'url' }"
                >
                    URL
                </button>
            </div>

            {{-- Gallery Source --}}
            <div class="uve-responsive-source-content" x-show="sourceTab === 'gallery'">
                <template x-if="productMedia.length > 0">
                    <div class="uve-responsive-gallery">
                        <template x-for="(media, index) in productMedia" :key="media.id || index">
                            <button
                                type="button"
                                @click="selectImage(activeBreakpoint, media)"
                                class="uve-responsive-gallery__item"
                                :class="{ 'uve-responsive-gallery__item--selected': breakpointsData[activeBreakpoint].url === (media.url || media.thumbnail_url) }"
                            >
                                <img
                                    :src="media.thumbnail_url || media.url"
                                    :alt="media.alt || 'Media'"
                                    loading="lazy"
                                />
                            </button>
                        </template>
                    </div>
                </template>
                <template x-if="productMedia.length === 0">
                    <div class="uve-responsive-empty-gallery">
                        <p>Brak obrazow w galerii</p>
                    </div>
                </template>
            </div>

            {{-- URL Source --}}
            <div class="uve-responsive-source-content" x-show="sourceTab === 'url'">
                <div class="uve-responsive-url-input">
                    <input
                        type="url"
                        x-model="urlInput"
                        class="uve-input"
                        placeholder="https://example.com/image.jpg"
                    />
                    <button
                        type="button"
                        @click="setImageUrl(activeBreakpoint, urlInput)"
                        class="uve-btn uve-btn-sm uve-btn-primary"
                        :disabled="!urlInput"
                    >
                        Ustaw
                    </button>
                </div>
            </div>
        </div>

        {{-- Alt Text --}}
        <div class="uve-responsive-editor__alt" x-show="breakpointsData[activeBreakpoint].url">
            <label class="uve-control__label uve-control__label--sm">Alt text</label>
            <input
                type="text"
                :value="breakpointsData[activeBreakpoint].alt"
                @input="setAltText(activeBreakpoint, $event.target.value)"
                class="uve-input uve-input--sm"
                placeholder="Opis obrazu..."
            />
        </div>

        {{-- Inherit from Desktop Option --}}
        <div class="uve-responsive-editor__inherit" x-show="activeBreakpoint !== 'desktop'">
            <label class="uve-toggle-row">
                <input
                    type="checkbox"
                    :checked="breakpointsData[activeBreakpoint].inherit"
                    @change="toggleInherit(activeBreakpoint)"
                    class="uve-checkbox"
                />
                <span class="uve-toggle-label">Dziedzicz z Desktop</span>
            </label>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="uve-responsive-actions">
        <button
            type="button"
            @click="copyToAll()"
            class="uve-btn uve-btn-sm"
            :disabled="!breakpointsData.desktop.url"
            title="Skopiuj Desktop do wszystkich"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Desktop do wszystkich
        </button>
        <button
            type="button"
            @click="clearAll()"
            class="uve-btn uve-btn-sm"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Wyczysc wszystkie
        </button>
    </div>
</div>

<style>
/* Responsive Images Control Styles */
.uve-control--responsive-images {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Overview Grid */
.uve-responsive-overview {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.uve-responsive-preview-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    background: #1e293b;
    border: 2px solid #334155;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-responsive-preview-card:hover {
    border-color: #475569;
}

.uve-responsive-preview-card--active {
    border-color: #e0ac7e;
    background: rgba(224, 172, 126, 0.1);
}

.uve-responsive-preview-card--has-image {
    background: #1e293b;
}

.uve-responsive-preview-card__icon {
    width: 20px;
    height: 20px;
    color: #64748b;
}

.uve-responsive-preview-card--active .uve-responsive-preview-card__icon {
    color: #e0ac7e;
}

.uve-responsive-preview-card__icon svg {
    width: 100%;
    height: 100%;
}

.uve-responsive-preview-card__label {
    font-size: 0.65rem;
    font-weight: 500;
    color: #94a3b8;
}

.uve-responsive-preview-card--active .uve-responsive-preview-card__label {
    color: #e0ac7e;
}

.uve-responsive-preview-card__thumb {
    width: 100%;
    aspect-ratio: 16/9;
    background: #0f172a;
    border-radius: 0.25rem;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.uve-responsive-preview-card__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.uve-responsive-preview-card__empty {
    font-size: 0.6rem;
    color: #475569;
}

/* Editor */
.uve-responsive-editor {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    overflow: hidden;
}

.uve-responsive-editor__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: #0f172a;
    border-bottom: 1px solid #334155;
}

.uve-responsive-editor__title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #e2e8f0;
}

.uve-responsive-editor__range {
    font-size: 0.7rem;
    color: #64748b;
}

.uve-responsive-editor__preview {
    padding: 0.75rem;
}

.uve-responsive-editor__image-wrapper {
    position: relative;
    border-radius: 0.25rem;
    overflow: hidden;
}

.uve-responsive-editor__image-wrapper img {
    width: 100%;
    max-height: 150px;
    object-fit: contain;
    background: #0f172a;
    display: block;
}

.uve-responsive-editor__clear-btn {
    position: absolute;
    top: 0.375rem;
    right: 0.375rem;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.7);
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-responsive-editor__clear-btn svg {
    width: 14px;
    height: 14px;
    color: white;
}

.uve-responsive-editor__clear-btn:hover {
    background: #ef4444;
}

.uve-responsive-editor__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 2rem 1rem;
    border: 2px dashed #334155;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-responsive-editor__empty:hover {
    border-color: #e0ac7e;
    background: rgba(224, 172, 126, 0.05);
}

.uve-responsive-editor__empty svg {
    width: 24px;
    height: 24px;
    color: #64748b;
}

.uve-responsive-editor__empty span {
    font-size: 0.8125rem;
    color: #94a3b8;
}

/* Source Tabs */
.uve-responsive-editor__source {
    padding: 0 0.75rem 0.75rem;
}

.uve-responsive-source-tabs {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.uve-responsive-source-tab {
    flex: 1;
    padding: 0.375rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: #94a3b8;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-responsive-source-tab:hover {
    color: #e2e8f0;
}

.uve-responsive-source-tab--active {
    background: #334155;
    border-color: #e0ac7e;
    color: #e0ac7e;
}

.uve-responsive-source-content {
    min-height: 60px;
}

/* Gallery */
.uve-responsive-gallery {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.25rem;
    max-height: 120px;
    overflow-y: auto;
}

.uve-responsive-gallery__item {
    aspect-ratio: 1;
    padding: 0;
    background: #0f172a;
    border: 2px solid #334155;
    border-radius: 0.25rem;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-responsive-gallery__item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.uve-responsive-gallery__item:hover {
    border-color: #64748b;
}

.uve-responsive-gallery__item--selected {
    border-color: #e0ac7e;
}

.uve-responsive-empty-gallery {
    padding: 1rem;
    text-align: center;
}

.uve-responsive-empty-gallery p {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
}

/* URL Input */
.uve-responsive-url-input {
    display: flex;
    gap: 0.375rem;
}

.uve-responsive-url-input .uve-input {
    flex: 1;
    font-size: 0.75rem;
}

.uve-btn-primary {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

.uve-btn-primary:hover:not(:disabled) {
    background: #d1975a;
}

.uve-btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Alt Text */
.uve-responsive-editor__alt {
    padding: 0 0.75rem 0.75rem;
}

/* Inherit Option */
.uve-responsive-editor__inherit {
    padding: 0 0.75rem 0.75rem;
}

.uve-toggle-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.uve-toggle-label {
    font-size: 0.8125rem;
    color: #94a3b8;
}

/* Actions */
.uve-responsive-actions {
    display: flex;
    gap: 0.375rem;
}

.uve-responsive-actions .uve-btn {
    flex: 1;
    justify-content: center;
    font-size: 0.7rem;
}

.uve-responsive-actions .uve-btn svg {
    width: 14px;
    height: 14px;
}

/* Shared styles */
.uve-input--sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
}

.uve-control__label--sm {
    font-size: 0.7rem;
    margin-bottom: 0.25rem;
    display: block;
}
</style>

<script>
function uveResponsiveImagesControl(initialValue, productMedia) {
    return {
        productMedia: productMedia || [],
        activeBreakpoint: 'desktop',
        sourceTab: 'gallery',
        urlInput: '',
        showMediaPicker: false,

        breakpointsData: {
            desktop: {
                label: 'Desktop',
                description: '> 1024px',
                url: initialValue?.desktop?.url || null,
                alt: initialValue?.desktop?.alt || '',
                inherit: false,
            },
            tablet: {
                label: 'Tablet',
                description: '768px - 1024px',
                url: initialValue?.tablet?.url || null,
                alt: initialValue?.tablet?.alt || '',
                inherit: initialValue?.tablet?.inherit ?? true,
            },
            mobile: {
                label: 'Mobile',
                description: '< 768px',
                url: initialValue?.mobile?.url || null,
                alt: initialValue?.mobile?.alt || '',
                inherit: initialValue?.mobile?.inherit ?? true,
            },
        },

        selectImage(breakpoint, media) {
            this.breakpointsData[breakpoint].url = media.url || media.thumbnail_url;
            this.breakpointsData[breakpoint].alt = media.alt || '';
            this.breakpointsData[breakpoint].inherit = false;
            this.emitChange();
        },

        setImageUrl(breakpoint, url) {
            if (!url) return;
            this.breakpointsData[breakpoint].url = url;
            this.breakpointsData[breakpoint].inherit = false;
            this.urlInput = '';
            this.emitChange();
        },

        setAltText(breakpoint, alt) {
            this.breakpointsData[breakpoint].alt = alt;
            this.emitChange();
        },

        clearImage(breakpoint) {
            this.breakpointsData[breakpoint].url = null;
            this.breakpointsData[breakpoint].alt = '';
            this.emitChange();
        },

        toggleInherit(breakpoint) {
            this.breakpointsData[breakpoint].inherit = !this.breakpointsData[breakpoint].inherit;
            if (this.breakpointsData[breakpoint].inherit) {
                // Clear custom image when inheriting
                this.breakpointsData[breakpoint].url = null;
                this.breakpointsData[breakpoint].alt = '';
            }
            this.emitChange();
        },

        copyToAll() {
            const desktop = this.breakpointsData.desktop;
            if (!desktop.url) return;

            ['tablet', 'mobile'].forEach(bp => {
                this.breakpointsData[bp].url = desktop.url;
                this.breakpointsData[bp].alt = desktop.alt;
                this.breakpointsData[bp].inherit = false;
            });

            this.emitChange();
        },

        clearAll() {
            Object.keys(this.breakpointsData).forEach(bp => {
                this.breakpointsData[bp].url = null;
                this.breakpointsData[bp].alt = '';
                if (bp !== 'desktop') {
                    this.breakpointsData[bp].inherit = true;
                }
            });
            this.emitChange();
        },

        emitChange() {
            const value = {
                desktop: {
                    url: this.breakpointsData.desktop.url,
                    alt: this.breakpointsData.desktop.alt,
                },
                tablet: {
                    url: this.breakpointsData.tablet.inherit ? null : this.breakpointsData.tablet.url,
                    alt: this.breakpointsData.tablet.alt,
                    inherit: this.breakpointsData.tablet.inherit,
                },
                mobile: {
                    url: this.breakpointsData.mobile.inherit ? null : this.breakpointsData.mobile.url,
                    alt: this.breakpointsData.mobile.alt,
                    inherit: this.breakpointsData.mobile.inherit,
                },
            };

            this.$wire.updateControlValue('responsive-images', value);
        }
    }
}
</script>
