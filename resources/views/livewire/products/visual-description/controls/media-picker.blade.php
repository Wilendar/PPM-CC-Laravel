{{--
    Media Picker Control - ETAP_07f_P5 FAZA PP.3
    Picker z 3 zakladkami:
    - TAB 1: Galeria produktu (integracja z MediaManager)
    - TAB 2: Upload (drag&drop + progress bar)
    - TAB 3: URL zewnetrzny (input + preview)
    + Podglad wybranego obrazu, przyciski clear/select
--}}
@props([
    'controlId' => 'media-picker',
    'value' => null,
    'options' => [],
    'onChange' => null,
    'productMedia' => [], // Existing product media from MediaManager
    'multiple' => false,
])

@php
    $acceptedTypes = $options['acceptedTypes'] ?? 'image/*';
    $maxSize = $options['maxSize'] ?? 10; // MB
    $placeholder = $options['placeholder'] ?? 'Wybierz obraz...';
@endphp

<div
    class="uve-control uve-control--media-picker"
    x-data="uveMediaPickerControl(@js($value), @js($productMedia), @js($multiple))"
    wire:ignore.self
>
    {{-- Selected Image Preview --}}
    <div class="uve-media-preview" x-show="hasSelection">
        <template x-if="selectedUrl">
            <div class="uve-media-preview__image-wrapper">
                <img
                    :src="selectedUrl"
                    :alt="selectedAlt || 'Selected image'"
                    class="uve-media-preview__image"
                    @error="imageError = true"
                />
                <div class="uve-media-preview__actions">
                    <button
                        type="button"
                        @click="clearSelection()"
                        class="uve-media-preview__btn uve-media-preview__btn--clear"
                        title="Usun obraz"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Empty State --}}
    <div class="uve-media-empty" x-show="!hasSelection" @click="activeTab = 'gallery'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span>{{ $placeholder }}</span>
    </div>

    {{-- Tabs Navigation --}}
    <div class="uve-media-tabs">
        <button
            type="button"
            @click="activeTab = 'gallery'"
            class="uve-media-tab"
            :class="{ 'uve-media-tab--active': activeTab === 'gallery' }"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Galeria
        </button>
        <button
            type="button"
            @click="activeTab = 'upload'"
            class="uve-media-tab"
            :class="{ 'uve-media-tab--active': activeTab === 'upload' }"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Upload
        </button>
        <button
            type="button"
            @click="activeTab = 'url'"
            class="uve-media-tab"
            :class="{ 'uve-media-tab--active': activeTab === 'url' }"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            URL
        </button>
    </div>

    {{-- Tab: Gallery --}}
    <div class="uve-media-content" x-show="activeTab === 'gallery'">
        <template x-if="productMedia.length > 0">
            <div class="uve-media-gallery">
                <template x-for="(media, index) in productMedia" :key="media.id || index">
                    <button
                        type="button"
                        @click="selectFromGallery(media)"
                        class="uve-media-gallery__item"
                        :class="{ 'uve-media-gallery__item--selected': isSelected(media) }"
                    >
                        <img
                            :src="media.thumbnail_url || media.url"
                            :alt="media.alt || 'Media ' + (index + 1)"
                            loading="lazy"
                        />
                        <div class="uve-media-gallery__check" x-show="isSelected(media)">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                            </svg>
                        </div>
                    </button>
                </template>
            </div>
        </template>

        <template x-if="productMedia.length === 0">
            <div class="uve-media-empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p>Brak obrazow w galerii produktu</p>
                <button
                    type="button"
                    @click="activeTab = 'upload'"
                    class="uve-btn uve-btn-sm"
                >
                    Dodaj obraz
                </button>
            </div>
        </template>
    </div>

    {{-- Tab: Upload --}}
    <div class="uve-media-content" x-show="activeTab === 'upload'">
        <div
            class="uve-media-dropzone"
            :class="{ 'uve-media-dropzone--active': isDragging }"
            @dragenter.prevent="isDragging = true"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
            @click="$refs.fileInput.click()"
        >
            <input
                type="file"
                x-ref="fileInput"
                accept="{{ $acceptedTypes }}"
                @change="handleFileSelect($event)"
                class="uve-media-file-input"
                {{ $multiple ? 'multiple' : '' }}
            />

            <template x-if="!isUploading">
                <div class="uve-media-dropzone__content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p>Przeciagnij plik tutaj lub kliknij</p>
                    <span class="uve-media-dropzone__hint">
                        Maks. {{ $maxSize }}MB | JPG, PNG, WebP
                    </span>
                </div>
            </template>

            <template x-if="isUploading">
                <div class="uve-media-upload-progress">
                    <div class="uve-progress-bar">
                        <div
                            class="uve-progress-bar__fill"
                            :style="'width: ' + uploadProgress + '%'"
                        ></div>
                    </div>
                    <span class="uve-media-upload-progress__text" x-text="uploadProgress + '%'"></span>
                </div>
            </template>
        </div>

        <template x-if="uploadError">
            <div class="uve-media-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="uploadError"></span>
            </div>
        </template>
    </div>

    {{-- Tab: URL --}}
    <div class="uve-media-content" x-show="activeTab === 'url'">
        <div class="uve-media-url-form">
            <label class="uve-control__label">URL obrazu</label>
            <div class="uve-media-url-input-row">
                <input
                    type="url"
                    x-model="externalUrl"
                    @input="validateUrl()"
                    class="uve-input"
                    placeholder="https://example.com/image.jpg"
                />
                <button
                    type="button"
                    @click="selectExternalUrl()"
                    class="uve-btn uve-btn-primary"
                    :disabled="!isValidUrl"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            </div>

            {{-- URL Preview --}}
            <template x-if="externalUrl && isValidUrl">
                <div class="uve-media-url-preview">
                    <img
                        :src="externalUrl"
                        alt="Preview"
                        @load="urlPreviewLoaded = true"
                        @error="urlPreviewError = true"
                        x-show="!urlPreviewError"
                    />
                    <div class="uve-media-url-preview__error" x-show="urlPreviewError">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Nie mozna zaladowac obrazu</span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Alt Text Input --}}
    <div class="uve-media-alt" x-show="hasSelection">
        <label class="uve-control__label uve-control__label--sm">Tekst alternatywny (alt)</label>
        <input
            type="text"
            x-model="selectedAlt"
            @input="emitChange()"
            class="uve-input uve-input--sm"
            placeholder="Opis obrazu..."
        />
    </div>
</div>

<style>
/* Media Picker Control Styles */
.uve-control--media-picker {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Preview */
.uve-media-preview {
    position: relative;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    overflow: hidden;
}

.uve-media-preview__image-wrapper {
    position: relative;
}

.uve-media-preview__image {
    width: 100%;
    max-height: 180px;
    object-fit: contain;
    background: #1e293b;
    display: block;
}

.uve-media-preview__actions {
    position: absolute;
    top: 0.375rem;
    right: 0.375rem;
    display: flex;
    gap: 0.25rem;
}

.uve-media-preview__btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.7);
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-media-preview__btn svg {
    width: 16px;
    height: 16px;
    color: white;
}

.uve-media-preview__btn--clear:hover {
    background: #ef4444;
}

/* Empty State */
.uve-media-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.5rem;
    background: #1e293b;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-media-empty:hover {
    border-color: #e0ac7e;
    background: rgba(224, 172, 126, 0.05);
}

.uve-media-empty svg {
    width: 32px;
    height: 32px;
    color: #64748b;
}

.uve-media-empty span {
    font-size: 0.8125rem;
    color: #94a3b8;
}

/* Tabs */
.uve-media-tabs {
    display: flex;
    background: #1e293b;
    border-radius: 0.375rem;
    padding: 0.25rem;
}

.uve-media-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.375rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 500;
    color: #94a3b8;
    background: transparent;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-media-tab svg {
    width: 14px;
    height: 14px;
}

.uve-media-tab:hover {
    color: #e2e8f0;
}

.uve-media-tab--active {
    background: #334155;
    color: #e0ac7e;
}

/* Content */
.uve-media-content {
    min-height: 100px;
}

/* Gallery */
.uve-media-gallery {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.375rem;
    max-height: 200px;
    overflow-y: auto;
    padding: 0.25rem;
}

.uve-media-gallery__item {
    position: relative;
    aspect-ratio: 1;
    padding: 0;
    background: #1e293b;
    border: 2px solid #334155;
    border-radius: 0.25rem;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-media-gallery__item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.uve-media-gallery__item:hover {
    border-color: #64748b;
}

.uve-media-gallery__item--selected {
    border-color: #e0ac7e;
}

.uve-media-gallery__check {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(224, 172, 126, 0.4);
}

.uve-media-gallery__check svg {
    width: 24px;
    height: 24px;
    color: white;
}

/* Empty State */
.uve-media-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.5rem;
    text-align: center;
}

.uve-media-empty-state svg {
    width: 32px;
    height: 32px;
    color: #475569;
}

.uve-media-empty-state p {
    font-size: 0.8125rem;
    color: #64748b;
    margin: 0;
}

/* Dropzone */
.uve-media-dropzone {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 120px;
    padding: 1rem;
    background: #1e293b;
    border: 2px dashed #475569;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-media-dropzone--active {
    border-color: #e0ac7e;
    background: rgba(224, 172, 126, 0.1);
}

.uve-media-dropzone:hover {
    border-color: #64748b;
}

.uve-media-file-input {
    display: none;
}

.uve-media-dropzone__content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    text-align: center;
}

.uve-media-dropzone__content svg {
    width: 32px;
    height: 32px;
    color: #64748b;
}

.uve-media-dropzone__content p {
    font-size: 0.8125rem;
    color: #e2e8f0;
    margin: 0;
}

.uve-media-dropzone__hint {
    font-size: 0.7rem;
    color: #64748b;
}

/* Upload Progress */
.uve-media-upload-progress {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
}

.uve-progress-bar {
    width: 100%;
    height: 8px;
    background: #334155;
    border-radius: 4px;
    overflow: hidden;
}

.uve-progress-bar__fill {
    height: 100%;
    background: linear-gradient(90deg, #e0ac7e 0%, #d1975a 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.uve-media-upload-progress__text {
    font-size: 0.75rem;
    color: #e0ac7e;
    font-weight: 600;
}

/* Error */
.uve-media-error {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 0.25rem;
    margin-top: 0.5rem;
}

.uve-media-error svg {
    width: 16px;
    height: 16px;
    color: #ef4444;
    flex-shrink: 0;
}

.uve-media-error span {
    font-size: 0.75rem;
    color: #f87171;
}

/* URL Form */
.uve-media-url-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.uve-media-url-input-row {
    display: flex;
    gap: 0.375rem;
}

.uve-media-url-input-row .uve-input {
    flex: 1;
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

.uve-btn svg {
    width: 16px;
    height: 16px;
}

/* URL Preview */
.uve-media-url-preview {
    margin-top: 0.5rem;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    overflow: hidden;
    background: #1e293b;
}

.uve-media-url-preview img {
    width: 100%;
    max-height: 120px;
    object-fit: contain;
    display: block;
}

.uve-media-url-preview__error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 1rem;
    color: #ef4444;
}

.uve-media-url-preview__error svg {
    width: 24px;
    height: 24px;
}

.uve-media-url-preview__error span {
    font-size: 0.75rem;
}

/* Alt Text */
.uve-media-alt {
    padding-top: 0.5rem;
    border-top: 1px solid #334155;
}

.uve-input--sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
}

.uve-control__label--sm {
    font-size: 0.7rem;
    margin-bottom: 0.25rem;
}
</style>

<script>
function uveMediaPickerControl(initialValue, productMedia, multiple) {
    return {
        // State
        activeTab: 'gallery',
        productMedia: productMedia || [],
        multiple: multiple || false,

        // Selected
        selectedUrl: initialValue?.url || initialValue || null,
        selectedAlt: initialValue?.alt || '',
        selectedMediaId: initialValue?.id || null,

        // Upload
        isDragging: false,
        isUploading: false,
        uploadProgress: 0,
        uploadError: null,

        // URL
        externalUrl: '',
        isValidUrl: false,
        urlPreviewLoaded: false,
        urlPreviewError: false,

        // Image error
        imageError: false,

        get hasSelection() {
            return !!this.selectedUrl;
        },

        isSelected(media) {
            return this.selectedMediaId === media.id ||
                   this.selectedUrl === (media.url || media.thumbnail_url);
        },

        selectFromGallery(media) {
            this.selectedUrl = media.url || media.thumbnail_url;
            this.selectedAlt = media.alt || '';
            this.selectedMediaId = media.id || null;
            this.imageError = false;
            this.emitChange();
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer?.files;
            if (files?.length > 0) {
                this.uploadFile(files[0]);
            }
        },

        handleFileSelect(event) {
            const files = event.target.files;
            if (files?.length > 0) {
                this.uploadFile(files[0]);
            }
        },

        async uploadFile(file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                this.uploadError = 'Tylko pliki obrazow sa dozwolone';
                return;
            }

            // Validate file size (10MB)
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                this.uploadError = 'Plik jest za duzy (max 10MB)';
                return;
            }

            this.uploadError = null;
            this.isUploading = true;
            this.uploadProgress = 0;

            try {
                // Simulate progress (in real app, this would be actual upload progress)
                const interval = setInterval(() => {
                    if (this.uploadProgress < 90) {
                        this.uploadProgress += 10;
                    }
                }, 100);

                // Call Livewire upload method
                await this.$wire.uploadMediaFile(file).then(url => {
                    clearInterval(interval);
                    this.uploadProgress = 100;

                    if (url) {
                        this.selectedUrl = url;
                        this.selectedAlt = file.name.replace(/\.[^/.]+$/, '');
                        this.emitChange();
                    }

                    setTimeout(() => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                    }, 500);
                });

            } catch (error) {
                this.isUploading = false;
                this.uploadProgress = 0;
                this.uploadError = 'Blad uploadu: ' + (error.message || 'Nieznany blad');
            }
        },

        validateUrl() {
            this.urlPreviewError = false;
            this.urlPreviewLoaded = false;

            try {
                const url = new URL(this.externalUrl);
                this.isValidUrl = ['http:', 'https:'].includes(url.protocol);
            } catch {
                this.isValidUrl = false;
            }
        },

        selectExternalUrl() {
            if (!this.isValidUrl || this.urlPreviewError) return;

            this.selectedUrl = this.externalUrl;
            this.selectedAlt = '';
            this.selectedMediaId = null;
            this.externalUrl = '';
            this.isValidUrl = false;
            this.emitChange();
        },

        clearSelection() {
            this.selectedUrl = null;
            this.selectedAlt = '';
            this.selectedMediaId = null;
            this.imageError = false;
            this.emitChange();
        },

        emitChange() {
            const value = this.selectedUrl ? {
                url: this.selectedUrl,
                alt: this.selectedAlt,
                id: this.selectedMediaId,
            } : null;

            this.$wire.updateControlValue('media-picker', value);
        }
    }
}
</script>
