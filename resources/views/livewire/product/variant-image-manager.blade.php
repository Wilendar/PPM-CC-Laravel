<div class="variant-image-manager">
    {{-- Header --}}
    <div class="manager-header">
        <h3 class="text-h3">Zdjęcia wariantu: {{ $this->variant->name }}</h3>
        <div class="text-sm text-gray-400">SKU: {{ $this->variant->sku }}</div>
    </div>

    {{-- Upload Section --}}
    <div class="upload-section">
        <div class="upload-dropzone"
             x-data="{
                 isDragging: false,
                 uploading: @entangle('isUploading')
             }"
             x-on:drop.prevent="isDragging = false"
             x-on:dragover.prevent="isDragging = true"
             x-on:dragleave.prevent="isDragging = false"
             :class="{ 'is-dragging': isDragging }">

            <input type="file"
                   wire:model="uploadedImages"
                   multiple
                   accept="image/*"
                   id="image-upload-{{ $variantId }}"
                   class="upload-input">

            <label for="image-upload-{{ $variantId }}" class="upload-label">
                <div class="upload-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <p class="upload-text">Przeciągnij i upuść zdjęcia lub kliknij aby wybrać</p>
                <p class="upload-hint">Max 10MB per zdjęcie, wiele plików wspierane</p>
            </label>

            {{-- Loading state --}}
            <div wire:loading wire:target="uploadedImages" class="upload-loading">
                <div class="spinner"></div>
                <span>Przesyłanie zdjęć...</span>
            </div>
        </div>

        {{-- Upload errors --}}
        @error('uploadedImages.*')
            <div class="error-message">{{ $message }}</div>
        @enderror

        {{-- Upload button (shown when files selected) --}}
        @if(count($uploadedImages) > 0)
            <div class="upload-actions">
                <button wire:click="uploadImages"
                        class="btn-enterprise-primary"
                        wire:loading.attr="disabled"
                        wire:target="uploadImages">
                    <span wire:loading.remove wire:target="uploadImages">
                        Wgraj {{ count($uploadedImages) }} zdjęć
                    </span>
                    <span wire:loading wire:target="uploadImages">
                        Wysyłanie...
                    </span>
                </button>
                <button wire:click="$set('uploadedImages', [])"
                        class="btn-enterprise-secondary"
                        wire:loading.attr="disabled">
                    Anuluj
                </button>
            </div>
        @endif
    </div>

    {{-- Image Gallery --}}
    <div class="image-gallery">
        @forelse($this->images as $image)
            <div class="image-card" wire:key="variant-image-{{ $variantId }}-{{ $image->id }}">
                <div class="image-wrapper">
                    <img src="{{ $image->getUrl() }}"
                         alt="{{ $image->filename }}"
                         class="variant-image">

                    {{-- Primary badge --}}
                    @if($image->is_cover)
                        <span class="badge-primary">★ Główne</span>
                    @endif

                    {{-- Zoom button --}}
                    <button wire:click="openLightbox({{ $image->id }})"
                            class="btn-zoom"
                            type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                        </svg>
                    </button>
                </div>

                {{-- Image actions --}}
                <div class="image-actions">
                    <button wire:click="setPrimary({{ $image->id }})"
                            class="btn-action-sm btn-primary-action"
                            wire:loading.attr="disabled"
                            wire:target="setPrimary">
                        @if($image->is_cover)
                            ★ Główne
                        @else
                            Ustaw jako główne
                        @endif
                    </button>

                    <div class="reorder-buttons">
                        <button wire:click="reorderImage({{ $image->id }}, 'up')"
                                class="btn-action-sm btn-reorder-action"
                                wire:loading.attr="disabled"
                                wire:target="reorderImage">
                            ↑
                        </button>
                        <button wire:click="reorderImage({{ $image->id }}, 'down')"
                                class="btn-action-sm btn-reorder-action"
                                wire:loading.attr="disabled"
                                wire:target="reorderImage">
                            ↓
                        </button>
                    </div>

                    <button wire:click="deleteImage({{ $image->id }})"
                            wire:confirm="Czy na pewno chcesz usunąć to zdjęcie?"
                            class="btn-action-sm btn-delete-action"
                            wire:loading.attr="disabled"
                            wire:target="deleteImage">
                        Usuń
                    </button>
                </div>

                {{-- Loading overlay --}}
                <div wire:loading wire:target="setPrimary({{ $image->id }}), reorderImage({{ $image->id }}, 'up'), reorderImage({{ $image->id }}, 'down'), deleteImage({{ $image->id }})"
                     class="loading-overlay">
                    <div class="spinner"></div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="empty-text">Brak zdjęć dla tego wariantu</p>
                <p class="empty-hint">Użyj sekcji uploadu powyżej aby dodać pierwsze zdjęcia</p>
            </div>
        @endforelse
    </div>

    {{-- Lightbox Modal --}}
    @if($selectedImageId)
        @php
            $selectedImage = $this->images->firstWhere('id', $selectedImageId);
        @endphp

        @if($selectedImage)
            <div class="lightbox-modal"
                 x-data="{ open: true }"
                 x-show="open"
                 x-on:click.self="$wire.closeLightbox()"
                 x-on:keydown.escape.window="$wire.closeLightbox()"
                 x-trap.inert.noscroll="open">

                <div class="modal-overlay"></div>

                <div class="modal-content">
                    <button wire:click="closeLightbox"
                            class="btn-close-modal"
                            type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <img src="{{ $selectedImage->getUrl() }}"
                         alt="{{ $selectedImage->filename }}"
                         class="lightbox-image">

                    <div class="lightbox-info">
                        <p class="lightbox-filename">{{ $selectedImage->filename }}</p>
                        @if($selectedImage->is_cover)
                            <span class="lightbox-badge">★ Główne zdjęcie</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
