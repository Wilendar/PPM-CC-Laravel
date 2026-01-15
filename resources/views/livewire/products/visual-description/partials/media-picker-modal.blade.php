{{-- Media Picker Modal for Visual Description Editor --}}
{{-- ETAP_07f Faza 7: Media Integration --}}

@props([
    'showModal' => false,
    'fieldIndex' => null,
    'fieldName' => null,
    'productId' => null,
    'multiple' => false,
])

<div
    x-data="{
        activeTab: 'gallery',
        selectedImages: [],
        urlInput: '',
        urlPreview: '',
        urlValid: null,
        cdnPath: '',
        isUploading: false,
        uploadProgress: 0,

        setTab(tab) {
            this.activeTab = tab;
        },

        toggleImage(imageUrl) {
            if ({{ $multiple ? 'true' : 'false' }}) {
                const idx = this.selectedImages.indexOf(imageUrl);
                if (idx > -1) {
                    this.selectedImages.splice(idx, 1);
                } else {
                    this.selectedImages.push(imageUrl);
                }
            } else {
                this.selectedImages = [imageUrl];
            }
        },

        isSelected(imageUrl) {
            return this.selectedImages.includes(imageUrl);
        },

        validateUrl() {
            const url = this.urlInput.trim();
            if (!url) {
                this.urlValid = null;
                this.urlPreview = '';
                return;
            }

            const img = new Image();
            img.onload = () => {
                this.urlValid = true;
                this.urlPreview = url;
            };
            img.onerror = () => {
                this.urlValid = false;
                this.urlPreview = '';
            };
            img.src = url;
        },

        selectUrlImage() {
            if (this.urlValid && this.urlInput.trim()) {
                this.selectedImages = [this.urlInput.trim()];
            }
        },

        buildCdnUrl() {
            const base = 'https://mm.mpptrade.pl/products';
            const sku = '{{ $this->product?->sku ?? '' }}';
            return `${base}/${sku}/images/${this.cdnPath}`;
        },

        confirmSelection() {
            if (this.selectedImages.length > 0) {
                $wire.setMediaPickerSelection(
                    {{ $fieldIndex ?? 'null' }},
                    '{{ $fieldName ?? '' }}',
                    {{ $multiple ? 'true' : 'false' }} ? this.selectedImages : this.selectedImages[0]
                );
            }
            $wire.closeMediaPicker();
        }
    }"
    x-show="$wire.showMediaPicker"
    x-cloak
    class="ve-media-picker-backdrop"
    @click.self="$wire.closeMediaPicker()"
    @keydown.escape.window="$wire.closeMediaPicker()"
>
    <div class="ve-media-picker-modal" @click.stop>
        {{-- Header --}}
        <div class="ve-media-picker-header">
            <div class="ve-media-picker-title">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ $multiple ? 'Wybierz obrazy' : 'Wybierz obraz' }}</span>
            </div>
            <button type="button" @click="$wire.closeMediaPicker()" class="ve-media-picker-close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tabs --}}
        <div class="ve-media-picker-tabs">
            <button
                type="button"
                @click="setTab('gallery')"
                :class="{ 've-media-picker-tab--active': activeTab === 'gallery' }"
                class="ve-media-picker-tab"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Galeria produktu
            </button>
            <button
                type="button"
                @click="setTab('upload')"
                :class="{ 've-media-picker-tab--active': activeTab === 'upload' }"
                class="ve-media-picker-tab"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Upload
            </button>
            <button
                type="button"
                @click="setTab('url')"
                :class="{ 've-media-picker-tab--active': activeTab === 'url' }"
                class="ve-media-picker-tab"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                URL zewnetrzny
            </button>
            <button
                type="button"
                @click="setTab('cdn')"
                :class="{ 've-media-picker-tab--active': activeTab === 'cdn' }"
                class="ve-media-picker-tab"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                CDN MPP
            </button>
        </div>

        {{-- Tab Content --}}
        <div class="ve-media-picker-content">
            {{-- Tab: Galeria produktu --}}
            <div x-show="activeTab === 'gallery'" x-cloak class="ve-media-picker-panel">
                @if($this->product && $this->product->media->count() > 0)
                    <div class="ve-media-picker-grid">
                        @foreach($this->product->media as $media)
                            <div
                                class="ve-media-picker-item"
                                :class="{ 've-media-picker-item--selected': isSelected('{{ $media->url }}') }"
                                @click="toggleImage('{{ $media->url }}')"
                            >
                                <img
                                    src="{{ $media->thumbnailUrl ?? $media->url }}"
                                    alt="{{ $media->original_name }}"
                                    loading="lazy"
                                    class="ve-media-picker-image"
                                />
                                <div class="ve-media-picker-check" x-show="isSelected('{{ $media->url }}')">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                @if($media->is_primary)
                                    <span class="ve-media-picker-badge">Glowne</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="ve-media-picker-empty">
                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="mt-2 text-gray-400">Brak zdjec w galerii produktu</p>
                        <p class="text-sm text-gray-500">Dodaj zdjecia w zakladce Galeria</p>
                    </div>
                @endif
            </div>

            {{-- Tab: Upload --}}
            <div x-show="activeTab === 'upload'" x-cloak class="ve-media-picker-panel">
                <div
                    class="ve-media-picker-dropzone"
                    x-data="{ isDragover: false }"
                    @dragover.prevent="isDragover = true"
                    @dragleave.prevent="isDragover = false"
                    @drop.prevent="
                        isDragover = false;
                        const files = $event.dataTransfer.files;
                        if (files.length > 0) {
                            isUploading = true;
                            $wire.uploadMediaForBlock(files[0]).then(() => {
                                isUploading = false;
                            });
                        }
                    "
                    :class="{ 've-media-picker-dropzone--active': isDragover }"
                >
                    <input
                        type="file"
                        id="media-picker-upload"
                        class="sr-only"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        @change="
                            if ($event.target.files.length > 0) {
                                isUploading = true;
                                $wire.uploadMediaForBlock($event.target.files[0]).then(() => {
                                    isUploading = false;
                                });
                            }
                        "
                    />

                    <div x-show="!isUploading" class="ve-media-picker-dropzone-content">
                        <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="mt-2 text-gray-300">Przeciagnij plik lub</p>
                        <label for="media-picker-upload" class="ve-media-picker-upload-btn">
                            Wybierz plik
                        </label>
                        <p class="mt-2 text-xs text-gray-500">JPG, PNG, WebP, GIF | Max 10MB</p>
                    </div>

                    <div x-show="isUploading" class="ve-media-picker-uploading">
                        <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-gray-400">Przesylanie...</p>
                    </div>
                </div>
            </div>

            {{-- Tab: URL zewnetrzny --}}
            <div x-show="activeTab === 'url'" x-cloak class="ve-media-picker-panel">
                <div class="ve-media-picker-url-form">
                    <label class="ve-media-picker-label">URL obrazu</label>
                    <div class="ve-media-picker-url-input-wrapper">
                        <input
                            type="url"
                            x-model="urlInput"
                            @input.debounce.500ms="validateUrl()"
                            placeholder="https://example.com/image.jpg"
                            class="ve-media-picker-url-input"
                        />
                        <div class="ve-media-picker-url-status">
                            <template x-if="urlValid === true">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </template>
                            <template x-if="urlValid === false">
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </template>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div x-show="urlPreview" class="ve-media-picker-url-preview">
                        <img :src="urlPreview" alt="Preview" class="ve-media-picker-url-preview-image" />
                    </div>

                    <button
                        type="button"
                        @click="selectUrlImage()"
                        :disabled="!urlValid"
                        class="ve-media-picker-url-select-btn"
                    >
                        Uzyj tego obrazu
                    </button>
                </div>
            </div>

            {{-- Tab: CDN MPP --}}
            <div x-show="activeTab === 'cdn'" x-cloak class="ve-media-picker-panel">
                <div class="ve-media-picker-cdn-form">
                    <div class="ve-media-picker-cdn-info">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>CDN MPP: mm.mpptrade.pl</span>
                    </div>

                    <label class="ve-media-picker-label">Sciezka bazowa</label>
                    <div class="ve-media-picker-cdn-base">
                        <span class="ve-media-picker-cdn-base-text">
                            https://mm.mpptrade.pl/products/{{ $this->product?->sku ?? '[SKU]' }}/images/
                        </span>
                    </div>

                    <label class="ve-media-picker-label">Nazwa pliku</label>
                    <input
                        type="text"
                        x-model="cdnPath"
                        placeholder="nazwa_pliku.jpg"
                        class="ve-media-picker-cdn-input"
                    />

                    {{-- Built URL Preview --}}
                    <div x-show="cdnPath" class="ve-media-picker-cdn-preview">
                        <label class="ve-media-picker-label">Pelny URL</label>
                        <code class="ve-media-picker-cdn-url" x-text="buildCdnUrl()"></code>
                    </div>

                    <button
                        type="button"
                        @click="
                            if (cdnPath) {
                                selectedImages = [buildCdnUrl()];
                            }
                        "
                        :disabled="!cdnPath"
                        class="ve-media-picker-cdn-select-btn"
                    >
                        Uzyj tego URL
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="ve-media-picker-footer">
            <div class="ve-media-picker-selection-info">
                <template x-if="selectedImages.length > 0">
                    <span class="text-sm text-green-400">
                        Wybrano: <span x-text="selectedImages.length"></span> {{ $multiple ? 'obrazow' : 'obraz' }}
                    </span>
                </template>
            </div>
            <div class="ve-media-picker-footer-actions">
                <button
                    type="button"
                    @click="$wire.closeMediaPicker()"
                    class="ve-media-picker-btn ve-media-picker-btn--secondary"
                >
                    Anuluj
                </button>
                <button
                    type="button"
                    @click="confirmSelection()"
                    :disabled="selectedImages.length === 0"
                    class="ve-media-picker-btn ve-media-picker-btn--primary"
                >
                    Wybierz
                </button>
            </div>
        </div>
    </div>
</div>
