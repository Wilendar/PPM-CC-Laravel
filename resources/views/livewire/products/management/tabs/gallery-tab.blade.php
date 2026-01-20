{{-- GalleryTab - Product Gallery Management --}}
{{-- ETAP_07d Phase 5: Livewire Components --}}
{{-- Included in ProductForm as @include('livewire.products.management.tabs.gallery-tab') --}}

<div class="gallery-tab">
    {{-- NOTE: ActiveOperationsBar removed - import progress should only show in ProductList --}}
    {{-- Gallery has its own $isSyncing indicator for media sync operations --}}

    {{-- ETAP_07d: Media Conflict/Error Alert --}}
    @php
        $mediaConflict = null;
        $mediaErrors = [];

        // Check for media conflicts in product_shop_data
        if ($productId && isset($product)) {
            foreach ($product->shopData ?? [] as $shopData) {
                $conflictData = $shopData->conflict_data ?? [];
                if (!empty($conflictData['media_conflict']) && !($conflictData['media_conflict']['resolved'] ?? false)) {
                    $mediaConflict = $conflictData['media_conflict'];
                    break;
                }
            }
        }
    @endphp

    @if($mediaConflict)
        <div class="media-alert media-alert-warning">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <p class="font-medium text-yellow-200">Wykryto konflikt zdjec</p>
                    <p class="text-sm text-yellow-300/80">{{ $mediaConflict['message'] ?? 'Produkt ma zdjecia z innego sklepu. Uzyj przycisku "Pobierz z PrestaShop" aby porownac i wybrac ktore zachowac.' }}</p>
                </div>
                <button type="button" wire:click="openImportModal" class="media-btn media-btn-primary media-btn-sm">
                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Porownaj zdjecia
                </button>
            </div>
        </div>
    @endif

    {{-- Header with sync controls --}}
    <div class="media-gallery-header">
        <div>
            <span class="media-gallery-title">Galeria produktu</span>
            <span class="media-gallery-count">({{ $mediaCount }}/{{ $maxImages }} zdjec)</span>
        </div>

        {{-- PrestaShop Sync Controls --}}
        @if($productId && $shops->count() > 0)
            <div class="media-gallery-controls">
                {{-- Button to open import modal --}}
                <button type="button"
                        wire:click="openImportModal"
                        class="media-btn media-btn-secondary">
                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Pobierz z PrestaShop
                </button>

                {{-- Apply Pending Changes Button - ETAP_07d: Deferred sync --}}
                @if($this->hasPendingShopChanges())
                    <button type="button"
                            wire:click="applyPendingShopChanges"
                            wire:loading.attr="disabled"
                            class="media-btn media-btn-primary animate-pulse">
                        <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        <span wire:loading.remove wire:target="applyPendingShopChanges">
                            Zastosuj zmiany synchronizacji ({{ count($pendingShopChanges) }})
                        </span>
                        <span wire:loading wire:target="applyPendingShopChanges">
                            Synchronizowanie...
                        </span>
                    </button>

                    <button type="button"
                            wire:click="discardPendingShopChanges"
                            wire:loading.attr="disabled"
                            class="media-btn media-btn-secondary">
                        <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Anuluj zmiany
                    </button>
                @endif

                {{-- ETAP_08.6: ERP Pending Changes Button --}}
                @if($hasPendingErpChanges ?? false)
                    <button type="button"
                            wire:click="applyPendingErpChanges"
                            wire:loading.attr="disabled"
                            class="media-btn media-btn-erp animate-pulse">
                        <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                        <span wire:loading.remove wire:target="applyPendingErpChanges">
                            Zastosuj zmiany ERP ({{ count($pendingErpChanges) }})
                        </span>
                        <span wire:loading wire:target="applyPendingErpChanges">
                            Synchronizowanie ERP...
                        </span>
                    </button>

                    <button type="button"
                            wire:click="discardPendingErpChanges"
                            wire:loading.attr="disabled"
                            class="media-btn media-btn-secondary">
                        <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Anuluj ERP
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Upload Zone --}}
    @if($productId)
        <div class="media-upload-zone {{ $mediaCount >= $maxImages ? 'is-disabled' : '' }}"
             x-data="{
                 isDragover: false,
                 handleDrop(e) {
                     if ({{ $mediaCount >= $maxImages ? 'true' : 'false' }}) return;
                     this.isDragover = false;
                     const files = Array.from(e.dataTransfer.files);
                     const imageFiles = files.filter(f => f.type.startsWith('image/'));

                     if (imageFiles.length > 0) {
                         $wire.uploadMultiple('newPhotos', imageFiles, () => {
                             // Success callback
                             console.log('Upload completed');
                         }, (error) => {
                             // Error callback
                             console.error('Upload failed:', error);
                         });
                     }
                 }
             }"
             x-on:dragover.prevent="isDragover = true"
             x-on:dragleave.prevent="isDragover = false"
             x-on:drop.prevent="handleDrop($event)"
             :class="{ 'is-dragover': isDragover }"
             @click="$refs.fileInput.click()">

            <svg class="media-upload-zone-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>

            @if($mediaCount < $maxImages)
                <p class="media-upload-zone-text">Przeciagnij zdjecia lub kliknij aby wybrac</p>
                <p class="media-upload-zone-hint">
                    Max {{ $maxImages - $mediaCount }} zdjec | JPG, PNG, WebP, GIF | Max 10MB
                </p>

                <div class="media-upload-zone-buttons">
                    <button type="button" @click.stop="$refs.fileInput.click()"
                            class="media-btn media-btn-primary media-btn-sm">
                        <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Wybierz pliki
                    </button>

                    <button type="button" @click.stop="$refs.folderInput.click()"
                            class="media-btn media-btn-secondary media-btn-sm">
                        <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        Wybierz folder
                    </button>
                </div>
            @else
                <p class="media-upload-zone-text">Osiagnieto limit zdjec ({{ $maxImages }})</p>
            @endif

            <input type="file" x-ref="fileInput" class="media-upload-input"
                   wire:model="newPhotos" accept="image/jpeg,image/png,image/webp,image/gif"
                   multiple {{ $mediaCount >= $maxImages ? 'disabled' : '' }} />

            <input type="file" x-ref="folderInput" class="media-upload-input"
                   wire:model="folderUpload" accept="image/jpeg,image/png,image/webp,image/gif"
                   webkitdirectory directory multiple {{ $mediaCount >= $maxImages ? 'disabled' : '' }} />
        </div>

        {{-- Upload Progress --}}
        @if($isUploading)
            <div class="media-upload-progress">
                <div class="media-upload-progress-bar">
                    <div class="media-upload-progress-fill" style="width: 50%"></div>
                </div>
                <p class="media-upload-progress-text">Przesylanie zdjec...</p>
            </div>
        @endif

        {{-- Upload Errors --}}
        @if(!empty($uploadErrors))
            <div class="media-upload-errors">
                @foreach($uploadErrors as $error)
                    <p class="media-upload-error">{{ $error }}</p>
                @endforeach
            </div>
        @endif
    @else
        <div class="media-gallery-empty">
            <p>Zapisz produkt aby moc dodawac zdjecia</p>
        </div>
    @endif

    {{-- Bulk Actions Toolbar --}}
    @if(count($selectedIds) > 0)
        <div class="bulk-actions-toolbar">
            <div class="bulk-actions-info">
                <span class="bulk-actions-count">Zaznaczono: {{ count($selectedIds) }}</span>
            </div>

            <div class="bulk-actions-buttons">
                {{-- Bulk Sync Dropdown --}}
                @if($shops->count() > 0)
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" @click="open = !open"
                                class="media-btn media-btn-primary media-btn-sm">
                            <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            Wyslij do PrestaShop
                        </button>

                        <div x-show="open" @click.away="open = false"
                             x-transition
                             class="absolute bottom-full mb-1 left-0 w-48 rounded-md shadow-lg bg-gray-800 ring-1 ring-black ring-opacity-5 z-10">
                            <div class="py-1">
                                @foreach($shops as $shop)
                                    <button type="button"
                                            wire:click="bulkSyncToPrestaShop({{ $shop->id }})"
                                            @click="open = false"
                                            class="block w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">
                                        {{ $shop->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <button type="button" wire:click="bulkDelete"
                        wire:confirm="Czy na pewno usunac zaznaczone zdjecia?"
                        class="media-btn media-btn-danger media-btn-sm">
                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Usun zaznaczone
                </button>

                <button type="button" wire:click="clearSelection"
                        class="media-btn media-btn-secondary media-btn-sm">
                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Odznacz
                </button>
            </div>
        </div>
    @endif

    {{-- Gallery Grid --}}
    @if($media->count() > 0)
        <div class="media-gallery-grid mt-4">
            @foreach($media as $item)
                <div wire:key="media-item-{{ $item->id }}" class="media-gallery-item {{ $item->is_primary ? 'is-primary' : '' }} {{ in_array($item->id, $selectedIds) ? 'is-selected' : '' }}">
                    {{-- Image Wrapper - contains image, overlay, badges, actions --}}
                    <div class="media-gallery-item-image-wrapper">
                        {{-- Selection Checkbox --}}
                        <input type="checkbox"
                               wire:model.live="selectedIds"
                               value="{{ $item->id }}"
                               onclick="event.stopPropagation()"
                               class="media-gallery-item-checkbox"
                               title="Zaznacz do operacji grupowych" />

                        {{-- Primary Badge --}}
                        @if($item->is_primary)
                            <span class="media-gallery-item-badge">Glowne</span>
                        @endif

                        {{-- Sync Status Icon - below primary badge --}}
                        @php
                            $syncStatusClass = 'sync-status-pending';
                            $syncStatusTitle = 'Oczekuje na synchronizacje';

                            if ($item->sync_status === 'synced') {
                                $syncStatusClass = 'sync-status-synced';
                                $syncStatusTitle = 'Zsynchronizowane';
                            } elseif ($item->sync_status === 'error') {
                                $syncStatusClass = 'sync-status-error';
                                $syncStatusTitle = 'Blad synchronizacji';
                            }
                        @endphp
                        <div class="media-sync-status-icon {{ $syncStatusClass }}" title="{{ $syncStatusTitle }}" style="top: {{ $item->is_primary ? '2.25rem' : '0.5rem' }};">
                            @if($item->sync_status === 'synced')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @elseif($item->sync_status === 'error')
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @else
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Image (clickable for lightbox) --}}
                        <img src="{{ $item->thumbnailUrl ?? $item->url }}"
                             alt="{{ $item->original_name }}"
                             class="media-gallery-item-image"
                             wire:click="openLightbox({{ $item->id }})"
                             title="Kliknij aby powiekszyc"
                             loading="lazy" />

                        {{-- Overlay --}}
                        <div class="media-gallery-item-overlay"></div>

                        {{-- Actions --}}
                        <div class="media-gallery-item-actions">
                            {{-- Reorder arrows --}}
                            @if(!$loop->first)
                                <button type="button" wire:click="moveUp({{ $item->id }})"
                                        class="media-btn media-btn-secondary media-btn-sm" title="Przesun w gore">
                                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    </svg>
                                </button>
                            @endif

                            @if(!$loop->last)
                                <button type="button" wire:click="moveDown({{ $item->id }})"
                                        class="media-btn media-btn-secondary media-btn-sm" title="Przesun w dol">
                                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            @endif

                            @if(!$item->is_primary)
                                <button type="button" wire:click="setPrimary({{ $item->id }})"
                                        class="media-btn media-btn-primary media-btn-sm" title="Ustaw jako glowne">
                                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </button>
                            @endif

                            <button type="button" wire:click="confirmDelete({{ $item->id }})"
                                    class="media-btn media-btn-danger media-btn-sm" title="Usun">
                                <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Shop Assignment Checkboxes - UNDER the image --}}
                    @if($shops->count() > 0)
                        <div class="media-shop-assignments">
                            @foreach($shops as $shop)
                                @php
                                    $storeKey = 'store_' . $shop->id;
                                    $key = "{$item->id}:{$shop->id}";
                                    $isSynced = isset($syncStatus[$item->id][$storeKey]['ps_image_id'])
                                                && $syncStatus[$item->id][$storeKey]['ps_image_id'];
                                    $isPendingSync = isset($pendingShopChanges[$key]) && $pendingShopChanges[$key] === 'sync';
                                    $isPendingUnsync = isset($pendingShopChanges[$key]) && $pendingShopChanges[$key] === 'unsync';
                                    $isPending = $isPendingSync || $isPendingUnsync;

                                    // Visual state (optimistic UI)
                                    $displayChecked = ($isSynced && !$isPendingUnsync) || $isPendingSync;

                                    // CSS classes
                                    $classes = [];
                                    if ($displayChecked) $classes[] = 'is-synced';
                                    if ($isPending) $classes[] = 'is-pending';
                                    $classString = implode(' ', $classes);

                                    // Title
                                    if ($isPendingSync) {
                                        $title = 'Oczekuje na wyslanie do '.$shop->name;
                                    } elseif ($isPendingUnsync) {
                                        $title = 'Oczekuje na usuniecie z '.$shop->name;
                                    } elseif ($isSynced) {
                                        $title = 'Kliknij aby usunac z '.$shop->name;
                                    } else {
                                        $title = 'Kliknij aby wyslac do '.$shop->name;
                                    }
                                @endphp
                                <label class="media-shop-checkbox {{ $classString }}"
                                       title="{{ $title }}">
                                    <input type="checkbox"
                                           wire:click="toggleShopAssignment({{ $item->id }}, {{ $shop->id }})"
                                           {{ $displayChecked ? 'checked' : '' }}
                                           class="media-shop-checkbox-input" />
                                    <span class="media-shop-checkbox-label">
                                        {{ Str::limit($shop->name, 10) }}
                                        @if($isPending)
                                            <span class="pending-indicator">⏳</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    {{-- ETAP_08.6: ERP Assignment Checkboxes - UNDER PrestaShop --}}
                    {{-- ETAP_08.8: Skip ERP types that don't support images (e.g. Subiekt GT) --}}
                    @if(!empty($erpConnections))
                        @php
                            // Filter out ERP connections that don't support images
                            $erpWithImageSupport = collect($erpConnections)->filter(function($conn) {
                                // Subiekt GT does NOT support image storage
                                return ($conn['erp_type'] ?? '') !== 'subiekt_gt';
                            });
                        @endphp

                        @if($erpWithImageSupport->count() > 0)
                            <div class="media-erp-assignments">
                                @foreach($erpWithImageSupport as $connection)
                                    @php
                                        $connectionKey = 'connection_' . $connection['id'];
                                        $erpKey = "{$item->id}:{$connection['id']}";
                                        $erpMapping = $erpSyncStatus[$item->id][$connectionKey] ?? null;
                                        $isErpSynced = $erpMapping && ($erpMapping['status'] ?? null) === 'synced';
                                        $isErpPendingSync = isset($pendingErpChanges[$erpKey]) && $pendingErpChanges[$erpKey] === 'sync';
                                        $isErpPendingUnsync = isset($pendingErpChanges[$erpKey]) && $pendingErpChanges[$erpKey] === 'unsync';
                                        $isErpPending = $isErpPendingSync || $isErpPendingUnsync;

                                        // Visual state (optimistic UI)
                                        $displayErpChecked = ($isErpSynced && !$isErpPendingUnsync) || $isErpPendingSync;

                                        // CSS classes
                                        $erpClasses = ['media-erp-checkbox'];
                                        if ($displayErpChecked) $erpClasses[] = 'is-synced';
                                        if ($isErpPending) $erpClasses[] = 'is-pending';
                                        $erpClassString = implode(' ', $erpClasses);

                                        // Title
                                        if ($isErpPendingSync) {
                                            $erpTitle = 'Oczekuje na wyslanie do '.$connection['instance_name'];
                                        } elseif ($isErpPendingUnsync) {
                                            $erpTitle = 'Oczekuje na usuniecie z '.$connection['instance_name'];
                                        } elseif ($isErpSynced) {
                                            $erpTitle = 'Kliknij aby usunac z '.$connection['instance_name'];
                                        } else {
                                            $erpTitle = 'Kliknij aby wyslac do '.$connection['instance_name'];
                                        }

                                        // Icon based on ERP type
                                        $erpIcon = match($connection['erp_type'] ?? 'baselinker') {
                                            'baselinker' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4',
                                            default => 'M13 10V3L4 14h7v7l9-11h-7z',
                                        };
                                    @endphp
                                    <label class="{{ $erpClassString }}" title="{{ $erpTitle }}">
                                        <input type="checkbox"
                                               wire:click="toggleErpAssignment({{ $item->id }}, {{ $connection['id'] }})"
                                               {{ $displayErpChecked ? 'checked' : '' }}
                                               class="media-erp-checkbox-input" />
                                        <span class="media-erp-checkbox-label">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $erpIcon }}"/>
                                            </svg>
                                            {{ Str::limit($connection['instance_name'], 8) }}
                                            @if($isErpPending)
                                                <span class="pending-indicator">⏳</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @elseif($productId)
        <div class="media-gallery-empty mt-4">
            <p>Brak zdjec w galerii</p>
            <p class="text-sm mt-2">Przeciagnij zdjecia powyzej lub pobierz z PrestaShop</p>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($deleteMediaId)
        <div class="media-delete-modal" wire:click.self="cancelDelete">
            <div class="media-delete-modal-content">
                <h3 class="media-delete-modal-title">Potwierdz usuniecie zdjecia</h3>

                <div class="media-delete-modal-options">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="deleteScope" value="ppm" class="text-primary-600" />
                        <span class="text-gray-300">Usun tylko z PPM</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="deleteScope" value="prestashop" class="text-primary-600" />
                        <span class="text-gray-300">Usun tylko z PrestaShop</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="deleteScope" value="both" class="text-primary-600" />
                        <span class="text-gray-300">Usun z obu systemow</span>
                    </label>
                </div>

                <div class="media-delete-modal-actions">
                    <button type="button" wire:click="cancelDelete" class="media-btn media-btn-secondary">
                        Anuluj
                    </button>
                    <button type="button" wire:click="executeDelete" class="media-btn media-btn-danger">
                        Usun zdjecie
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Lightbox Modal - teleported to body for proper fullscreen display --}}
    @if($lightboxUrl)
        <template x-teleport="body">
            <div class="media-lightbox"
                 x-data
                 @click.self="$wire.closeLightbox()"
                 @keydown.escape.window="$wire.closeLightbox()">
                <div class="media-lightbox-content" @click.stop>
                    <button type="button"
                            @click="$wire.closeLightbox()"
                            class="media-lightbox-close"
                            title="Zamknij (ESC)">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <img src="{{ $lightboxUrl }}" alt="{{ $lightboxName }}" class="media-lightbox-image" />
                    <p class="media-lightbox-name">{{ $lightboxName }}</p>
                </div>
            </div>
        </template>
    @endif

    {{-- IMPORT MODAL - ETAP_07d: Advanced Import from PrestaShop --}}
    {{-- Using x-teleport to break out of parent stacking context --}}
    @if($showImportModal)
        <template x-teleport="body">
            <div class="media-import-modal-backdrop"
                 x-data
                 @click.self="$wire.closeImportModal()">
                <div class="media-import-modal" @click.stop>
                    {{-- Modal Header --}}
                    <div class="media-import-modal-header">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <div>
                                <h3 class="media-import-modal-title">Importuj zdjecia z PrestaShop</h3>
                                <p class="media-import-modal-subtitle">Porownaj i zaimportuj zdjecia z wybranych sklepow</p>
                            </div>
                        </div>
                        <button type="button" @click="$wire.closeImportModal()" class="media-import-modal-close">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Shop Selection --}}
                    <div class="media-import-shop-selection">
                        <p class="text-sm text-gray-400 mb-2">Wybierz sklepy do pobrania zdjec:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($shops as $shop)
                                <label class="media-import-shop-checkbox {{ in_array($shop->id, $importModalShops) ? 'is-selected' : '' }}">
                                    <input type="checkbox"
                                           @click="$wire.toggleShopForFetch({{ $shop->id }})"
                                           {{ in_array($shop->id, $importModalShops) ? 'checked' : '' }}
                                           class="sr-only" />
                                    <span class="media-import-shop-name">{{ $shop->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="button"
                                @click="$wire.fetchShopImages()"
                                class="media-btn media-btn-primary mt-3"
                                {{ empty($importModalShops) ? 'disabled' : '' }}>
                            <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Pobierz zdjecia
                        </button>
                    </div>

                    {{-- Images Loading State --}}
                    @if($isLoadingShopImages)
                        <div class="media-import-loading">
                            <svg class="animate-spin h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-400 mt-2">Pobieranie zdjec ze sklepow...</p>
                        </div>
                    @endif

                    {{-- Images Grid --}}
                    @if(!empty($importShopImages) && !$isLoadingShopImages)
                        {{-- Quick Actions Header --}}
                        <div class="media-import-actions-header">
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-gray-400">
                                    Znaleziono {{ $this->getTotalImagesFound() }} zdjec
                                </span>
                                @if($this->getTotalImagesFound() > 0)
                                    <button type="button" @click="$wire.selectAllForImport()" class="text-sm text-blue-400 hover:text-blue-300">
                                        Zaznacz wszystkie do importu
                                    </button>
                                    <button type="button" @click="$wire.deselectAllImport()" class="text-sm text-gray-400 hover:text-gray-300">
                                        Odznacz wszystkie
                                    </button>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <span class="w-3 h-3 rounded bg-green-500/30 border border-green-500"></span>
                                    Import
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-3 h-3 rounded bg-red-500/30 border border-red-500"></span>
                                    Usun
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="w-3 h-3 rounded bg-gray-500/30 border border-gray-500"></span>
                                    Juz w PPM
                                </span>
                            </div>
                        </div>

                        {{-- Shop Images Sections --}}
                        <div class="media-import-shops-grid">
                            @foreach($importShopImages as $shopId => $shopData)
                                <div class="media-import-shop-section">
                                    <div class="media-import-shop-header">
                                        <h4 class="media-import-shop-title">
                                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            {{ $shopData['shop_name'] ?? 'Sklep' }}
                                        </h4>
                                        <span class="text-xs text-gray-500">{{ count($shopData['images'] ?? []) }} zdjec</span>
                                    </div>

                                    @if(!empty($shopData['error']))
                                        <div class="media-import-shop-error">
                                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>{{ $shopData['error'] }}</span>
                                        </div>
                                    @elseif(empty($shopData['images']))
                                        <div class="media-import-shop-empty">
                                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span>Brak zdjec dla tego produktu</span>
                                        </div>
                                    @else
                                        <div class="media-import-images-grid">
                                            @foreach($shopData['images'] as $image)
                                                @php
                                                    $key = "{$shopId}:{$image['id']}";
                                                    $isSelectedImport = isset($selectedImportImages[$key]);
                                                    $isSelectedDelete = isset($selectedDeleteImages[$key]);
                                                    $existsInPpm = $image['exists_in_ppm'] ?? false;
                                                @endphp
                                                <div class="media-import-image-item {{ $isSelectedImport ? 'is-import' : '' }} {{ $isSelectedDelete ? 'is-delete' : '' }} {{ $existsInPpm ? 'is-in-ppm' : '' }}">
                                                    {{-- Image --}}
                                                    <div class="media-import-image-wrapper">
                                                        <img src="{{ $image['url'] }}" alt="Image {{ $image['id'] }}" loading="lazy" />

                                                        {{-- Cover/Primary Badge --}}
                                                        @if($image['is_cover'] ?? false)
                                                            <span class="media-import-cover-badge">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                                </svg>
                                                                Glowne
                                                            </span>
                                                        @endif

                                                        {{-- In PPM Badge --}}
                                                        @if($existsInPpm)
                                                            <span class="media-import-ppm-badge">W PPM</span>
                                                        @endif

                                                        {{-- Shop Label --}}
                                                        <span class="media-import-shop-label">{{ Str::limit($shopData['shop_name'], 12) }}</span>
                                                    </div>

                                                    {{-- Action Buttons --}}
                                                    <div class="media-import-image-actions">
                                                        @if(!$existsInPpm)
                                                            <button type="button"
                                                                    @click="$wire.toggleImportSelection({{ $shopId }}, {{ $image['id'] }})"
                                                                    class="media-import-action-btn {{ $isSelectedImport ? 'is-active-import' : '' }}"
                                                                    title="Importuj do PPM">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                                </svg>
                                                            </button>
                                                        @endif
                                                        <button type="button"
                                                                @click="$wire.toggleDeleteSelection({{ $shopId }}, {{ $image['id'] }})"
                                                                class="media-import-action-btn {{ $isSelectedDelete ? 'is-active-delete' : '' }}"
                                                                title="Usun z PrestaShop">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Modal Footer --}}
                    <div class="media-import-modal-footer">
                        <div class="flex items-center gap-2 text-sm text-gray-400">
                            @if($this->getImportCount() > 0)
                                <span class="text-green-400">{{ $this->getImportCount() }} do importu</span>
                            @endif
                            @if($this->getDeleteCount() > 0)
                                <span class="text-red-400">{{ $this->getDeleteCount() }} do usuniecia</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="$wire.closeImportModal()"
                                    class="media-btn media-btn-secondary">
                                Anuluj
                            </button>

                            @if($this->getDeleteCount() > 0)
                                <button type="button"
                                        @click="if(confirm('Czy na pewno usunac {{ $this->getDeleteCount() }} zdjec z PrestaShop?')) $wire.deleteSelectedFromPrestaShop()"
                                        class="media-btn media-btn-danger">
                                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Usun zaznaczone ({{ $this->getDeleteCount() }})
                                </button>
                            @endif

                            @if($this->getImportCount() > 0 || $this->getTotalImagesFound() > 0)
                                <button type="button"
                                        @click="$wire.importSelectedImages()"
                                        class="media-btn media-btn-primary"
                                        {{ $this->getImportCount() === 0 ? 'disabled' : '' }}>
                                    <svg class="media-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    @if($this->getImportCount() > 0)
                                        Importuj zaznaczone ({{ $this->getImportCount() }})
                                    @else
                                        Importuj zaznaczone
                                    @endif
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
