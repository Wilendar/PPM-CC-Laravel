{{--
    Unified Visual Editor (UVE) - ETAP_07f_P5
    3-panelowy layout: Lewy (bloki/elementy) | Srodek (canvas) | Prawy (properties/layers)
--}}
<div
    class="uve-container"
    x-data="uveEditor()"
    x-on:keydown.escape.window="handleEscape()"
    x-on:keydown.ctrl.z.window.prevent="$wire.undo()"
    x-on:keydown.ctrl.y.window.prevent="$wire.redo()"
    x-on:keydown.ctrl.s.window.prevent="$wire.save()"
    x-on:keydown.delete.window="handleDelete()"
    x-on:keydown.ctrl.d.window.prevent="handleDuplicate()"
    x-on:keydown.enter.window="handleEnter()"
    x-on:uve-dragstart.window="handleDragStart($event.detail.event, $event.detail.elementId)"
    x-on:uve-dragend.window="handleDragEnd($event.detail.event)"
    x-on:uve-dragover.window="handleDragOver($event.detail.event, $event.detail.elementId)"
    x-on:uve-dragleave.window="handleDragLeave($event.detail.event)"
    x-on:uve-drop.window="handleDrop($event.detail.event, $event.detail.elementId, $event.detail.parentId)"
    x-on:uve-inline-edit.window="startInlineEdit($event.detail.elementId)"
>
    {{-- TOOLBAR --}}
    <div class="uve-toolbar">
        <div class="uve-toolbar-left">
            {{-- Save --}}
            <button
                type="button"
                wire:click="save"
                class="uve-btn uve-btn-primary"
                title="Zapisz (Ctrl+S)"
            >
                <x-heroicon-o-check class="w-4 h-4" />
                <span>Zapisz</span>
                @if($isDirty)
                    <span class="uve-dirty-indicator"></span>
                @endif
            </button>

            {{-- Revert Changes --}}
            <button
                type="button"
                wire:click="revertChanges"
                wire:confirm="Czy na pewno chcesz cofnac wszystkie zmiany? Utracisz wszystkie niezapisane modyfikacje."
                @disabled(!$isDirty)
                class="uve-btn uve-btn-danger"
                title="Cofnij wszystkie zmiany"
            >
                <x-heroicon-o-arrow-path class="w-4 h-4" />
                <span>Cofnij zmiany</span>
            </button>

            {{-- Undo/Redo --}}
            <div class="uve-btn-group">
                <button
                    type="button"
                    wire:click="undo"
                    @disabled(!$this->canUndo)
                    class="uve-btn"
                    title="Cofnij (Ctrl+Z)"
                >
                    <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    wire:click="redo"
                    @disabled(!$this->canRedo)
                    class="uve-btn"
                    title="Ponow (Ctrl+Y)"
                >
                    <x-heroicon-o-arrow-uturn-right class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="uve-toolbar-center">
            {{-- View Mode --}}
            <div class="uve-btn-group">
                <button
                    type="button"
                    wire:click="setViewMode('edit')"
                    class="uve-btn {{ $viewMode === 'edit' ? 'uve-btn-active' : '' }}"
                >
                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                    <span>Edycja</span>
                </button>
                <button
                    type="button"
                    wire:click="setViewMode('preview')"
                    class="uve-btn {{ $viewMode === 'preview' ? 'uve-btn-active' : '' }}"
                >
                    <x-heroicon-o-eye class="w-4 h-4" />
                    <span>Podglad</span>
                </button>
                <button
                    type="button"
                    wire:click="setViewMode('code')"
                    class="uve-btn {{ $viewMode === 'code' ? 'uve-btn-active' : '' }}"
                >
                    <x-heroicon-o-code-bracket class="w-4 h-4" />
                    <span>Kod</span>
                </button>
            </div>
        </div>

        <div class="uve-toolbar-right">
            {{-- Shop Switcher (multi-store) --}}
            @if(count($this->availableShops) > 1)
                <div class="uve-shop-switcher" x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        class="uve-btn uve-btn-shop"
                        title="Przelacz sklep"
                    >
                        <x-heroicon-o-building-storefront class="w-4 h-4" />
                        <span>{{ $this->shop?->name ?? 'Sklep' }}</span>
                        <x-heroicon-o-chevron-down class="w-3 h-3 ml-1" />
                    </button>

                    <div
                        x-show="open"
                        @click.away="open = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="uve-shop-dropdown"
                        style="display: none;"
                    >
                        @foreach($this->availableShops as $availableShop)
                            <button
                                type="button"
                                wire:click="switchShop({{ $availableShop['id'] }})"
                                @click="open = false"
                                class="uve-shop-item {{ $availableShop['is_active'] ? 'uve-shop-item-active' : '' }}"
                            >
                                <span class="uve-shop-name">{{ $availableShop['name'] }}</span>
                                @if($availableShop['has_description'])
                                    <span class="uve-shop-badge uve-shop-badge-has-desc" title="Ma opis wizualny">
                                        <x-heroicon-o-document-text class="w-3 h-3" />
                                    </span>
                                @else
                                    <span class="uve-shop-badge uve-shop-badge-no-desc" title="Brak opisu">
                                        <x-heroicon-o-document class="w-3 h-3" />
                                    </span>
                                @endif
                                @if($availableShop['is_active'])
                                    <x-heroicon-o-check class="w-4 h-4 text-green-400" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Single shop indicator --}}
                <div class="uve-shop-indicator">
                    <x-heroicon-o-building-storefront class="w-4 h-4" />
                    <span>{{ $this->shop?->name ?? 'Sklep' }}</span>
                </div>
            @endif

            {{-- Preview Device (only in preview mode) --}}
            @if($viewMode === 'preview')
                <div class="uve-btn-group">
                    <button
                        type="button"
                        wire:click="setPreviewDevice('desktop')"
                        class="uve-btn {{ $previewDevice === 'desktop' ? 'uve-btn-active' : '' }}"
                        title="Desktop"
                    >
                        <x-heroicon-o-computer-desktop class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        wire:click="setPreviewDevice('tablet')"
                        class="uve-btn {{ $previewDevice === 'tablet' ? 'uve-btn-active' : '' }}"
                        title="Tablet"
                    >
                        <x-heroicon-o-device-tablet class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        wire:click="setPreviewDevice('mobile')"
                        class="uve-btn {{ $previewDevice === 'mobile' ? 'uve-btn-active' : '' }}"
                        title="Mobile"
                    >
                        <x-heroicon-o-device-phone-mobile class="w-4 h-4" />
                    </button>
                </div>
            @endif

            {{-- Import --}}
            <button
                type="button"
                wire:click="openImportModal('html')"
                class="uve-btn"
                title="Import"
            >
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                <span>Import</span>
            </button>

            {{-- CSS Sync --}}
            @if($this->isCssSyncAvailable())
                <div class="uve-css-sync-group">
                    <button
                        type="button"
                        wire:click="syncCss"
                        class="uve-btn {{ $cssSyncInProgress ? 'uve-btn-syncing' : '' }}"
                        @disabled($cssSyncInProgress)
                        title="Synchronizuj CSS do PrestaShop"
                    >
                        @if($cssSyncInProgress)
                            <span class="uve-sync-spinner"></span>
                            <span>Sync...</span>
                        @else
                            <x-heroicon-o-arrow-path class="w-4 h-4" />
                            <span>CSS Sync</span>
                        @endif
                    </button>

                    {{-- Auto-sync toggle --}}
                    <button
                        type="button"
                        wire:click="toggleAutoSyncCss"
                        class="uve-btn uve-btn-sm {{ $autoSyncCss ? 'uve-btn-active' : '' }}"
                        title="{{ $autoSyncCss ? 'Auto-sync wlaczony' : 'Auto-sync wylaczony' }}"
                    >
                        <x-heroicon-o-bolt class="w-4 h-4" />
                    </button>
                </div>
            @endif

            {{-- CSS Sync Status --}}
            @if($cssSyncStatus)
                <div class="uve-css-sync-status {{ $cssSyncError ? 'uve-css-sync-error' : 'uve-css-sync-success' }}">
                    @if($cssSyncError)
                        <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                    @else
                        <x-heroicon-o-check-circle class="w-4 h-4" />
                    @endif
                    <span class="uve-sync-status-text">{{ $cssSyncStatus }}</span>
                </div>
            @endif

            {{-- Panel toggles --}}
            <div class="uve-btn-group">
                <button
                    type="button"
                    wire:click="toggleBlockPalette"
                    class="uve-btn {{ $showBlockPalette ? 'uve-btn-active' : '' }}"
                    title="Panel blokow"
                >
                    <x-heroicon-o-squares-plus class="w-4 h-4" />
                </button>
                <button
                    type="button"
                    wire:click="togglePropertiesPanel"
                    class="uve-btn {{ $showPropertiesPanel ? 'uve-btn-active' : '' }}"
                    title="Panel wlasciwosci"
                >
                    <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="uve-main">
        {{-- LEFT PANEL: Blocks / Elements --}}
        @if($showBlockPalette)
            <div class="uve-panel uve-panel-left">
                <div class="uve-panel-header">
                    <h3>{{ $this->isEditingBlock ? 'Elementy' : 'Bloki' }}</h3>
                </div>
                <div class="uve-panel-content">
                    @if($this->isEditingBlock)
                        {{-- Element Palette (when editing block) --}}
                        @include('livewire.products.visual-description.partials.uve-element-palette')
                    @else
                        {{-- Block Palette --}}
                        @include('livewire.products.visual-description.partials.uve-block-palette')
                    @endif
                </div>
            </div>
        @endif

        {{-- CENTER: Canvas --}}
        <div class="uve-canvas-wrapper">
            @if($viewMode === 'edit')
                {{-- Edit Canvas - FAZA 4.5.1: Iframe-based 1:1 Preview with editing --}}
                <div
                    class="uve-edit-canvas"
                    x-data="uveEditCanvas()"
                    x-on:uve-preview-refresh.window="refreshIframe()"
                    x-on:uve-media-applied.window="refreshIframe()"
                    wire:key="canvas-edit-iframe"
                >
                    {{-- Device selector for edit mode --}}
                    <div class="uve-edit-device-selector">
                        <button
                            type="button"
                            wire:click="setPreviewDevice('desktop')"
                            class="uve-btn uve-btn-sm {{ $previewDevice === 'desktop' ? 'uve-btn-active' : '' }}"
                            title="Desktop"
                        >
                            <x-heroicon-o-computer-desktop class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            wire:click="setPreviewDevice('tablet')"
                            class="uve-btn uve-btn-sm {{ $previewDevice === 'tablet' ? 'uve-btn-active' : '' }}"
                            title="Tablet"
                        >
                            <x-heroicon-o-device-tablet class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            wire:click="setPreviewDevice('mobile')"
                            class="uve-btn uve-btn-sm {{ $previewDevice === 'mobile' ? 'uve-btn-active' : '' }}"
                            title="Mobile"
                        >
                            <x-heroicon-o-device-phone-mobile class="w-4 h-4" />
                        </button>
                    </div>

                    {{-- Interactive Edit Iframe --}}
                    <div
                        class="uve-edit-iframe-container"
                        style="width: {{ $this->previewWidth }}; {{ $previewDevice !== 'desktop' ? 'margin: 1rem auto;' : '' }}"
                    >
                        @if(count($blocks) > 0)
                            <iframe
                                wire:ignore
                                x-ref="editFrame"
                                id="uve-edit-iframe-{{ $this->getId() }}"
                                srcdoc="{{ $this->editableIframeContent }}"
                                class="uve-edit-iframe"
                                sandbox="allow-scripts allow-same-origin"
                                @load="onFrameLoad()"
                            ></iframe>

                            {{-- Selection Overlay (outside iframe) --}}
                            <div
                                class="uve-selection-overlay"
                                x-ref="overlay"
                                x-show="selectedRect"
                                x-bind:style="overlayStyle"
                                x-cloak
                            >
                                <div class="uve-selection-info" x-show="selectedElementType" :style="toolbarPositionStyle">
                                    <span x-text="selectedElementType" class="uve-selection-type"></span>
                                    <span x-text="selectedBlockType" class="uve-selection-block-type" x-show="selectedBlockType"></span>
                                </div>
                                <div class="uve-selection-actions" :style="toolbarPositionStyle">
                                    <button
                                        type="button"
                                        @click="editElement"
                                        class="uve-action-btn"
                                        title="Edytuj tekst (dblclick)"
                                        x-show="canEditText"
                                    >
                                        <x-heroicon-o-pencil class="w-3 h-3" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="duplicateElement"
                                        class="uve-action-btn"
                                        title="Duplikuj blok (Ctrl+D)"
                                    >
                                        <x-heroicon-o-document-duplicate class="w-3 h-3" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="deleteElement"
                                        class="uve-action-btn uve-action-btn-danger"
                                        title="Usun blok (Delete)"
                                    >
                                        <x-heroicon-o-trash class="w-3 h-3" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="scrollToElement"
                                        class="uve-action-btn"
                                        title="Przewin do elementu"
                                    >
                                        <x-heroicon-o-viewfinder-circle class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="uve-empty-state">
                                <x-heroicon-o-document-plus class="w-12 h-12 text-gray-300" />
                                <p class="text-gray-500 mt-2">Brak blokow</p>
                                <p class="text-gray-400 text-sm">Dodaj blok z panelu po lewej stronie</p>
                            </div>
                        @endif
                    </div>

                    {{-- Edit mode status --}}
                    <div class="uve-edit-status" x-show="selectedElementId">
                        <span class="uve-edit-status-label">Zaznaczony:</span>
                        <span class="uve-edit-status-value" x-text="selectedElementId"></span>
                    </div>
                </div>
            @elseif($viewMode === 'preview')
                {{-- Preview IFRAME - Full height with proper viewport simulation --}}
                <div class="uve-preview-wrapper">
                    <div
                        class="uve-preview-container"
                        style="max-width: {{ $this->previewWidth }}; margin: 0 auto;"
                    >
                        <iframe
                            id="uve-preview-iframe-{{ $this->getId() }}"
                            srcdoc="{{ $this->iframeContent }}"
                            class="uve-preview-iframe"
                            sandbox="allow-scripts allow-same-origin"
                        ></iframe>
                    </div>
                </div>
            @else
                {{-- Code View --}}
                <div class="uve-code-view">
                    <pre><code>{{ $this->previewHtml }}</code></pre>
                </div>
            @endif
        </div>

        {{-- RIGHT PANEL: Properties / Layers --}}
        @if($showPropertiesPanel)
            <div class="uve-panel uve-panel-right">
                {{-- Panel Tabs --}}
                <div class="uve-panel-tabs">
                    <button
                        type="button"
                        wire:click="setActiveRightPanel('properties')"
                        class="uve-panel-tab {{ $activeRightPanel === 'properties' ? 'active' : '' }}"
                    >
                        Wlasciwosci
                    </button>
                    <button
                        type="button"
                        wire:click="setActiveRightPanel('layers')"
                        class="uve-panel-tab {{ $activeRightPanel === 'layers' ? 'active' : '' }}"
                    >
                        Warstwy
                    </button>
                </div>

                <div class="uve-panel-content">
                    @if($activeRightPanel === 'properties')
                        {{-- NEW Property Panel V2 with CSS Controls --}}
                        @include('livewire.products.visual-description.partials.uve-property-panel-v2', [
                            'panelConfig' => $this->panelConfig(),
                            'activeTab' => $activeTab,
                            'hoverState' => $hoverState,
                            'currentDevice' => $currentDevice,
                            'elementStyles' => $elementStyles,
                            'selectedElementId' => $this->selectedElementId,
                        ])
                    @else
                        {{-- Layers Panel --}}
                        @include('livewire.products.visual-description.partials.uve-layers-panel')
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- MODALS --}}
    @if($showImportModal)
        @include('livewire.products.visual-description.partials.uve-import-modal')
    @endif

    {{-- Media Picker Modal - ETAP_07h PP.3 --}}
    @if($showUveMediaPicker)
        <div class="uve-modal-overlay" wire:click.self="closeMediaPicker">
            <div class="uve-modal uve-modal--media-picker"
                x-data="uveMediaPickerModal()"
                x-on:uve-upload-complete.window="handleUploadComplete($event.detail)"
                x-on:uve-media-selected.window="handleMediaSelected($event.detail)"
            >
                <div class="uve-modal__header">
                    <h3 class="uve-modal__title">Wybierz obraz</h3>
                    <button type="button" wire:click="closeMediaPicker" class="uve-modal__close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="uve-modal__body">
                    {{-- Tabs --}}
                    <div class="uve-media-tabs">
                        <button type="button" @click="setActiveTab('gallery')" class="uve-media-tab" :class="{ 'uve-media-tab--active': activeTab === 'gallery' }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Galeria produktu
                        </button>
                        <button type="button" @click="setActiveTab('upload')" class="uve-media-tab" :class="{ 'uve-media-tab--active': activeTab === 'upload' }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Upload
                        </button>
                        <button type="button" @click="setActiveTab('url')" class="uve-media-tab" :class="{ 'uve-media-tab--active': activeTab === 'url' }">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            URL
                        </button>
                    </div>

                    {{-- Gallery Tab --}}
                    <div x-show="activeTab === 'gallery'" class="uve-media-content">
                        @if($this->productMediaForPicker && count($this->productMediaForPicker) > 0)
                            <div class="uve-media-grid">
                                @foreach($this->productMediaForPicker as $media)
                                    <div
                                        class="uve-media-item"
                                        :class="{ 'uve-media-item--selected': selectedMediaId === {{ $media['id'] }} }"
                                        wire:key="media-{{ $media['id'] }}"
                                    >
                                        <img
                                            src="{{ $media['thumbnail_url'] ?? $media['url'] }}"
                                            alt="{{ $media['alt'] ?? '' }}"
                                            @click="selectMedia({{ $media['id'] }}, '{{ addslashes($media['url']) }}')"
                                        >
                                        {{-- Delete button on hover --}}
                                        <button
                                            type="button"
                                            class="uve-media-item__delete"
                                            @click.stop="confirmDelete({{ $media['id'] }})"
                                            title="Usun z galerii"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                        {{-- Selection checkmark --}}
                                        <div class="uve-media-item__check" x-show="selectedMediaId === {{ $media['id'] }}">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="uve-media-empty">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p>Brak obrazow w galerii produktu</p>
                            </div>
                        @endif
                    </div>

                    {{-- Upload Tab --}}
                    <div x-show="activeTab === 'upload'" class="uve-media-content">
                        <div
                            class="uve-upload-dropzone"
                            :class="{ 'uve-upload-dropzone--dragover': isDragging, 'uve-upload-dropzone--uploading': isUploading }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)"
                        >
                            {{-- Normal state --}}
                            <template x-if="!isUploading">
                                <div class="uve-upload-dropzone__content">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    <p>Przeciagnij plik tutaj</p>
                                    <p class="uve-upload-dropzone__hint">lub</p>
                                    <label class="uve-btn uve-btn-primary">
                                        <input type="file" wire:model.live="mediaUploadFile" accept="image/*" class="sr-only">
                                        Wybierz z dysku
                                    </label>
                                </div>
                            </template>

                            {{-- Uploading state --}}
                            <template x-if="isUploading">
                                <div class="uve-upload-dropzone__uploading">
                                    <svg class="w-10 h-10 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p>Przesylanie pliku...</p>
                                </div>
                            </template>
                        </div>

                        @if($uploadProgress > 0 && $uploadProgress < 100)
                            <div class="uve-upload-progress">
                                <div class="uve-upload-progress__bar" style="width: {{ $uploadProgress }}%"></div>
                            </div>
                        @endif
                    </div>

                    {{-- URL Tab --}}
                    <div x-show="activeTab === 'url'" class="uve-media-content">
                        <div class="uve-url-input">
                            <label class="uve-control__label">URL obrazu</label>
                            <div class="uve-url-input__row">
                                <input
                                    type="text"
                                    wire:model.defer="mediaUrl"
                                    class="uve-input"
                                    placeholder="https://example.com/image.jpg"
                                >
                                <button type="button" wire:click="setExternalUrl(mediaUrl)" class="uve-btn uve-btn-primary">
                                    Zastosuj
                                </button>
                            </div>
                        </div>

                        {{-- Preview --}}
                        <div class="uve-url-preview" x-show="$wire.mediaUrl">
                            <img :src="$wire.mediaUrl" alt="Preview" x-on:error="$el.style.display='none'">
                        </div>
                    </div>
                </div>

                <div class="uve-modal__footer">
                    <button type="button" wire:click="closeMediaPicker" class="uve-btn">
                        Anuluj
                    </button>
                    <button
                        type="button"
                        class="uve-btn uve-btn-primary"
                        x-show="selectedMediaId !== null"
                        @click="applyMedia()"
                    >
                        Wybierz
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay - only for long operations (NOT for selection changes) --}}
    <div wire:loading.flex wire:target="save, syncCss, executeImport, importFromPrestaShop, compileAllBlocks" class="uve-loading-overlay">
        <div class="uve-loading-spinner"></div>
    </div>

    <style>
/* UVE Container - Dark Theme (PPM) */
.uve-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 64px);
    background: #0f172a;
    position: relative;
}

/* Toolbar */
.uve-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-bottom: 1px solid #334155;
    gap: 1rem;
    flex-shrink: 0;
}

.uve-toolbar-left,
.uve-toolbar-center,
.uve-toolbar-right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Buttons - Dark Theme */
.uve-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #e2e8f0;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-btn:hover:not(:disabled) {
    background: #475569;
    border-color: #64748b;
}

.uve-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.uve-btn-primary {
    color: white;
    background: linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c08449 100%);
    border-color: #d1975a;
}

.uve-btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #d1975a 0%, #c08449 50%, #a06839 100%);
}

.uve-btn-danger {
    color: #fecaca;
    background: #991b1b;
    border-color: #dc2626;
}

.uve-btn-danger:hover:not(:disabled) {
    background: #b91c1c;
}

.uve-btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.uve-btn-active {
    background: rgba(224, 172, 126, 0.15);
    border-color: #e0ac7e;
    color: #e0ac7e;
}

.uve-btn-group {
    display: flex;
}

.uve-btn-group .uve-btn {
    border-radius: 0;
}

.uve-btn-group .uve-btn:first-child {
    border-radius: 0.375rem 0 0 0.375rem;
}

.uve-btn-group .uve-btn:last-child {
    border-radius: 0 0.375rem 0.375rem 0;
}

.uve-btn-group .uve-btn:not(:last-child) {
    border-right: none;
}

.uve-dirty-indicator {
    width: 6px;
    height: 6px;
    background: #ef4444;
    border-radius: 50%;
    margin-left: 0.25rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* CSS Sync Styles */
.uve-css-sync-group {
    display: flex;
    gap: 2px;
}

.uve-btn-sm {
    padding: 0.5rem;
}

.uve-btn-syncing {
    pointer-events: none;
    opacity: 0.8;
}

.uve-sync-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.uve-css-sync-status {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 0.375rem;
    animation: fadeIn 0.3s ease;
}

.uve-css-sync-success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.4);
}

.uve-css-sync-error {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.4);
}

.uve-sync-status-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Shop Switcher (Multi-Store) */
.uve-shop-switcher {
    position: relative;
}

.uve-btn-shop {
    min-width: 140px;
    justify-content: space-between;
}

.uve-shop-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.25rem;
    min-width: 200px;
    background: #1e293b;
    border: 1px solid #475569;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    z-index: 50;
    overflow: hidden;
}

.uve-shop-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    color: #e2e8f0;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background 0.15s;
    text-align: left;
}

.uve-shop-item:hover {
    background: #334155;
}

.uve-shop-item-active {
    background: rgba(224, 172, 126, 0.1);
    border-left: 3px solid #e0ac7e;
}

.uve-shop-name {
    flex: 1;
}

.uve-shop-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.125rem;
    border-radius: 0.25rem;
}

.uve-shop-badge-has-desc {
    color: #10b981;
}

.uve-shop-badge-no-desc {
    color: #6b7280;
}

.uve-shop-indicator {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #94a3b8;
    background: #334155;
    border-radius: 0.375rem;
}

/* Main Layout */
.uve-main {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* Panels - Dark Theme */
.uve-panel {
    background: #1e293b;
    border-right: 1px solid #334155;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.uve-panel-left {
    width: 280px;
    flex-shrink: 0;
}

.uve-panel-right {
    width: 320px;
    flex-shrink: 0;
    border-right: none;
    border-left: 1px solid #334155;
}

.uve-panel-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #334155;
}

.uve-panel-header h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #e2e8f0;
    margin: 0;
}

.uve-panel-content {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.uve-panel-tabs {
    display: flex;
    border-bottom: 1px solid #334155;
}

.uve-panel-tab {
    flex: 1;
    padding: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #94a3b8;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-panel-tab:hover {
    color: #e2e8f0;
}

.uve-panel-tab.active {
    color: #e0ac7e;
    border-bottom: 2px solid #e0ac7e;
    margin-bottom: -1px;
}

.uve-panel-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 2rem;
    text-align: center;
}

/* Canvas - Light background for content preview */
.uve-canvas-wrapper {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    background: #0f172a;
}

.uve-canvas {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    min-height: 400px;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.2);
    padding: 1rem;
}

.uve-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    color: #64748b;
}

/* Preview */
.uve-preview-wrapper {
    flex: 1;
    overflow: hidden;
    padding: 1rem;
    background: #0a0f1a;
}

.uve-preview-container {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    /* Dynamic height - fill available viewport minus header/toolbar */
    min-height: calc(100vh - 220px);
}

.uve-preview-iframe {
    width: 100%;
    height: 100%;
    min-height: calc(100vh - 220px);
    border: none;
}

/* Code View */
.uve-code-view {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 0.5rem;
    padding: 1rem;
    overflow: auto;
}

.uve-code-view pre {
    margin: 0;
}

.uve-code-view code {
    color: #e2e8f0;
    font-family: 'Fira Code', monospace;
    font-size: 0.875rem;
    white-space: pre-wrap;
}

/* Loading */
.uve-loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
}

.uve-loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #334155;
    border-top-color: #e0ac7e;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Block Palette Categories - Dark Theme */
.uve-palette-category {
    margin-bottom: 1.5rem;
}

.uve-palette-category-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.uve-palette-items {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.uve-palette-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 0.5rem;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
    color: #94a3b8;
    font-size: 0.75rem;
}

.uve-palette-item:hover {
    background: #475569;
    border-color: #e0ac7e;
    color: #e2e8f0;
}

.uve-palette-item svg {
    width: 1.25rem;
    height: 1.25rem;
    margin-bottom: 0.25rem;
}

/* Form Inputs - Dark Theme */
.uve-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #e2e8f0;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    transition: all 0.15s;
}

.uve-input:focus {
    outline: none;
    border-color: #e0ac7e;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.15);
}

.uve-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    margin-bottom: 0.25rem;
}

/* Select Inputs - Dark Theme */
.uve-select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #e2e8f0;
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
    padding-right: 2rem;
    transition: all 0.15s;
}

.uve-select:focus {
    outline: none;
    border-color: #e0ac7e;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.15);
}

.uve-select option {
    background: #0f172a;
    color: #e2e8f0;
    padding: 0.5rem;
}

.uve-select--unit {
    width: auto;
    min-width: 4rem;
    padding: 0.375rem 1.75rem 0.375rem 0.5rem;
    font-size: 0.75rem;
}

/* Control Fields */
.uve-control__field {
    margin-bottom: 0.75rem;
}

.uve-control__label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    margin-bottom: 0.375rem;
}

/* Typography Row Layout */
.uve-typography-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.uve-typography-row .uve-input {
    flex: 1;
}

.uve-typography-row .uve-select--unit {
    flex-shrink: 0;
}

/* Small Input Variant */
.uve-input--sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
}

/* Button Groups */
.uve-btn-group-full {
    display: flex;
    gap: 0.25rem;
}

.uve-btn {
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-btn:hover {
    background: #334155;
    color: #e2e8f0;
}

.uve-btn-active,
.uve-btn.uve-btn-active {
    background: #e0ac7e;
    color: #0f172a;
    border-color: #e0ac7e;
}

.uve-btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
}

/* Size Preset Buttons */
.uve-size-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.uve-size-preset-btn {
    padding: 0.125rem 0.375rem;
    font-size: 0.65rem;
    color: #64748b;
    background: transparent;
    border: 1px solid #334155;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-size-preset-btn:hover {
    background: #334155;
    color: #94a3b8;
}

.uve-size-preset-btn--active {
    background: rgba(224, 172, 126, 0.2);
    border-color: #e0ac7e;
    color: #e0ac7e;
}

/* Property Groups */
.uve-property-group {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #334155;
}

.uve-property-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.uve-property-group-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.75rem;
}

/* =====================
   DRAG & DROP STYLES
   ===================== */

.uve-dragging {
    opacity: 0.5;
    cursor: grabbing !important;
}

.uve-drop-before {
    position: relative;
}

.uve-drop-before::before {
    content: '';
    position: absolute;
    top: -2px;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #e0ac7e, #d1975a);
    border-radius: 2px;
    z-index: 10;
    animation: dropIndicator 0.3s ease;
}

.uve-drop-after {
    position: relative;
}

.uve-drop-after::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #e0ac7e, #d1975a);
    border-radius: 2px;
    z-index: 10;
    animation: dropIndicator 0.3s ease;
}

.uve-drop-inside {
    outline: 2px dashed #e0ac7e !important;
    outline-offset: -2px;
    background-color: rgba(224, 172, 126, 0.1) !important;
}

@keyframes dropIndicator {
    from {
        opacity: 0;
        transform: scaleX(0.5);
    }
    to {
        opacity: 1;
        transform: scaleX(1);
    }
}

/* Drag handle on hover */
.uve-element[draggable="true"]:hover::before {
    content: '\2630';
    position: absolute;
    left: -20px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 12px;
    cursor: grab;
    opacity: 0.7;
}

.uve-element[draggable="true"]:hover::before:active {
    cursor: grabbing;
}

/* =====================
   INLINE EDITING STYLES
   ===================== */

.uve-inline-editing {
    outline: 2px solid #e0ac7e !important;
    outline-offset: 2px;
    background-color: rgba(255, 255, 255, 0.95) !important;
    padding: 4px 8px;
    border-radius: 4px;
    min-width: 50px;
    cursor: text !important;
}

.uve-inline-editing:focus {
    box-shadow: 0 0 0 4px rgba(224, 172, 126, 0.3);
}

/* Hint for double-click */
.uve-element-heading:not(.uve-inline-editing):hover::after,
.uve-element-text:not(.uve-inline-editing):hover::after,
.uve-element-button:not(.uve-inline-editing):hover::after {
    content: 'Kliknij 2x aby edytowac';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #e2e8f0;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
    z-index: 100;
}

.uve-element-heading:not(.uve-inline-editing):hover:hover::after,
.uve-element-text:not(.uve-inline-editing):hover:hover::after,
.uve-element-button:not(.uve-inline-editing):hover:hover::after {
    opacity: 1;
}

/* =====================
   FAZA 4.5.1: EDIT MODE IFRAME
   ===================== */

.uve-edit-canvas {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}

.uve-edit-device-selector {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.5rem;
    background: #1e293b;
    border-bottom: 1px solid #334155;
}

.uve-edit-iframe-container {
    flex: 1;
    width: 100%;
    background: white;
    border-radius: 0.5rem;
    margin: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    position: relative;
    transition: width 0.3s ease, margin 0.3s ease;
    /* Dynamic height - fill available viewport minus header/toolbar */
    min-height: calc(100vh - 220px);
    isolation: isolate; /* Create stacking context for z-index to work */
}

.uve-edit-iframe {
    width: 100%;
    height: 100%;
    min-height: calc(100vh - 220px);
    border: none;
    display: block;
    position: relative;
    z-index: 1; /* Low z-index so overlay can be above */
}

/* Selection Overlay - positioned outside iframe */
.uve-selection-overlay {
    position: absolute;
    pointer-events: none;
    border: 2px solid #e0ac7e;
    border-radius: 4px;
    background: rgba(224, 172, 126, 0.1);
    z-index: 9999; /* High z-index to stay above iframe content */
    transition: all 0.15s ease;
}

.uve-selection-info {
    position: absolute;
    /* Position controlled by Alpine.js infoStyle - default at TOP */
    left: 0;
    display: flex;
    gap: 0.25rem;
    font-size: 11px;
    font-family: system-ui, sans-serif;
    background: rgba(30, 41, 59, 0.95);
    padding: 4px 8px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    z-index: 10000; /* Ensure always on top */
}

.uve-selection-type {
    background: #e0ac7e;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    text-transform: capitalize;
}

.uve-selection-block-type {
    background: #475569;
    color: #e2e8f0;
    padding: 2px 8px;
    border-radius: 3px;
}

.uve-selection-actions {
    position: absolute;
    /* Position controlled by Alpine.js actionsStyle - default at TOP */
    right: 0;
    display: flex;
    gap: 4px;
    pointer-events: auto;
    background: rgba(30, 41, 59, 0.95);
    padding: 4px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    z-index: 10000; /* Ensure always on top */
}

.uve-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 4px;
    color: #e2e8f0;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-action-btn:hover {
    background: #e0ac7e;
    border-color: #d1975a;
    color: white;
}

.uve-action-btn-danger:hover {
    background: #dc2626;
    border-color: #b91c1c;
    color: white;
}

/* Edit status bar */
.uve-edit-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #1e293b;
    border-top: 1px solid #334155;
    font-size: 0.75rem;
}

.uve-edit-status-label {
    color: #64748b;
}

.uve-edit-status-value {
    color: #e0ac7e;
    font-family: monospace;
}

/* Hide with x-cloak */
[x-cloak] {
    display: none !important;
}

/* ========================================
   MEDIA PICKER MODAL - ETAP_07h PP.3
   ======================================== */
.uve-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.uve-modal {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.5rem;
    width: 90%;
    max-width: 700px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.uve-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #334155;
}

.uve-modal__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #f1f5f9;
    margin: 0;
}

.uve-modal__close {
    color: #94a3b8;
    background: transparent;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    border-radius: 0.25rem;
    transition: all 0.15s;
}

.uve-modal__close:hover {
    color: #f1f5f9;
    background: #334155;
}

.uve-modal__body {
    flex: 1;
    min-height: 0; /* Critical for flexbox overflow to work */
    overflow-y: auto;
    padding: 1.25rem;
}

.uve-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #334155;
}

/* Media Tabs */
.uve-media-tabs {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1rem;
    background: #0f172a;
    padding: 0.25rem;
    border-radius: 0.375rem;
}

.uve-media-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #94a3b8;
    background: transparent;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-media-tab:hover {
    color: #e2e8f0;
}

.uve-media-tab--active {
    background: #334155;
    color: #e0ac7e;
}

/* Media Content */
.uve-media-content {
    min-height: 200px;
}

/* Media Grid (Gallery) */
.uve-media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
}

.uve-media-item {
    aspect-ratio: 1;
    border: 2px solid #334155;
    border-radius: 0.375rem;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
}

.uve-media-item:hover {
    border-color: #e0ac7e;
    transform: scale(1.02);
}

.uve-media-item--selected {
    border-color: #e0ac7e;
    box-shadow: 0 0 0 3px rgba(224, 172, 126, 0.3);
}

.uve-media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Delete button - hidden by default, shown on hover */
.uve-media-item__delete {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(220, 38, 38, 0.9);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.15s;
    z-index: 2;
}

.uve-media-item:hover .uve-media-item__delete {
    opacity: 1;
}

.uve-media-item__delete:hover {
    background: #dc2626;
}

/* Selection checkmark */
.uve-media-item__check {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0ac7e;
    color: #1e293b;
    border-radius: 50%;
    z-index: 1;
}

/* Empty State */
.uve-media-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #64748b;
    text-align: center;
}

.uve-media-empty svg {
    margin-bottom: 1rem;
    color: #475569;
}

/* Upload Dropzone */
.uve-upload-dropzone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2rem;
    border: 2px dashed #475569;
    border-radius: 0.5rem;
    background: #0f172a;
    text-align: center;
    color: #94a3b8;
    transition: all 0.2s;
}

.uve-upload-dropzone--dragover {
    border-color: #e0ac7e;
    background: rgba(224, 172, 126, 0.1);
}

.uve-upload-dropzone svg {
    margin-bottom: 1rem;
    color: #64748b;
}

.uve-upload-dropzone__hint {
    font-size: 0.75rem;
    margin: 0.5rem 0;
}

/* Upload Progress */
.uve-upload-progress {
    margin-top: 1rem;
    height: 6px;
    background: #334155;
    border-radius: 3px;
    overflow: hidden;
}

.uve-upload-progress__bar {
    height: 100%;
    background: linear-gradient(90deg, #e0ac7e, #d1975a);
    transition: width 0.3s ease;
}

/* URL Input */
.uve-url-input {
    margin-bottom: 1rem;
}

.uve-url-input__row {
    display: flex;
    gap: 0.5rem;
}

.uve-url-input__row .uve-input {
    flex: 1;
}

/* URL Preview */
.uve-url-preview {
    margin-top: 1rem;
    border: 1px solid #334155;
    border-radius: 0.375rem;
    overflow: hidden;
    max-height: 200px;
}

.uve-url-preview img {
    width: 100%;
    height: auto;
    object-fit: contain;
}
</style>

<script>
function uveEditor() {
    return {
        // Drag & Drop State
        draggedElementId: null,
        dragOverElementId: null,
        dropPosition: null, // 'before', 'after', 'inside'

        // Inline Editing State
        editingElementId: null,
        originalContent: '',

        init() {
            // Listen for keyboard shortcuts
            this.$watch('$wire.editingBlockIndex', (value) => {
                // Cancel inline edit when block changes
                if (this.editingElementId) {
                    this.cancelInlineEdit();
                }
            });

            // Close inline edit on click outside
            document.addEventListener('click', (e) => {
                if (this.editingElementId && !e.target.closest('.uve-inline-editor')) {
                    this.finishInlineEdit();
                }
            });
        },

        // =====================
        // KEYBOARD HANDLERS
        // =====================

        handleEscape() {
            // First check inline editing
            if (this.editingElementId) {
                this.cancelInlineEdit();
                return;
            }
            // If editing block, freeze it with save
            const editingIndex = @js($editingBlockIndex);
            if (editingIndex !== null) {
                this.$wire.freezeBlock(editingIndex, true);
            }
        },

        handleDelete() {
            // Don't delete if inline editing
            if (this.editingElementId) return;

            const selectedElementId = @js($selectedElementId);
            const selectedBlockIndex = @js($selectedBlockIndex);
            const editingIndex = @js($editingBlockIndex);

            if (editingIndex !== null && selectedElementId) {
                this.$wire.removeElement(selectedElementId);
            } else if (selectedBlockIndex !== null) {
                if (confirm('Czy na pewno chcesz usunac ten blok?')) {
                    this.$wire.removeBlock(selectedBlockIndex);
                }
            }
        },

        handleDuplicate() {
            const selectedElementId = @js($selectedElementId);
            const selectedBlockIndex = @js($selectedBlockIndex);
            const editingIndex = @js($editingBlockIndex);

            if (editingIndex !== null && selectedElementId) {
                this.$wire.duplicateElement(selectedElementId);
            } else if (selectedBlockIndex !== null) {
                this.$wire.duplicateBlock(selectedBlockIndex);
            }
        },

        handleEnter() {
            const selectedBlockIndex = @js($selectedBlockIndex);
            const editingIndex = @js($editingBlockIndex);

            if (selectedBlockIndex !== null && editingIndex === null) {
                this.$wire.unfreezeBlock(selectedBlockIndex);
            }
        },

        // =====================
        // DRAG & DROP
        // =====================

        handleDragStart(event, elementId) {
            this.draggedElementId = elementId;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', elementId);

            // Add dragging class
            event.target.classList.add('uve-dragging');
        },

        handleDragEnd(event) {
            event.target.classList.remove('uve-dragging');
            this.clearDropIndicators();
            this.draggedElementId = null;
            this.dragOverElementId = null;
            this.dropPosition = null;
        },

        handleDragOver(event, elementId) {
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';

            if (elementId === this.draggedElementId) return;

            const rect = event.target.getBoundingClientRect();
            const y = event.clientY - rect.top;
            const height = rect.height;

            // Determine drop position
            let position = 'after';
            if (y < height * 0.25) {
                position = 'before';
            } else if (y > height * 0.75) {
                position = 'after';
            } else {
                // Check if target can have children (container types)
                const type = event.target.dataset.elementType;
                if (['container', 'row', 'column', 'div'].includes(type)) {
                    position = 'inside';
                } else {
                    position = y < height / 2 ? 'before' : 'after';
                }
            }

            if (this.dragOverElementId !== elementId || this.dropPosition !== position) {
                this.clearDropIndicators();
                this.dragOverElementId = elementId;
                this.dropPosition = position;
                event.target.classList.add(`uve-drop-${position}`);
            }
        },

        handleDragLeave(event) {
            event.target.classList.remove('uve-drop-before', 'uve-drop-after', 'uve-drop-inside');
        },

        handleDrop(event, targetElementId, targetParentId) {
            event.preventDefault();
            this.clearDropIndicators();

            if (!this.draggedElementId || this.draggedElementId === targetElementId) return;

            // Determine position based on dropPosition
            if (this.dropPosition === 'inside') {
                // Move into container
                this.$wire.moveElement(this.draggedElementId, targetElementId, 0);
            } else {
                // Move before/after
                const position = this.dropPosition === 'before' ? 0 : 1;
                // We need to move relative to target - this requires parent info
                this.$wire.moveElementRelative(this.draggedElementId, targetElementId, this.dropPosition);
            }

            this.draggedElementId = null;
        },

        clearDropIndicators() {
            document.querySelectorAll('.uve-drop-before, .uve-drop-after, .uve-drop-inside').forEach(el => {
                el.classList.remove('uve-drop-before', 'uve-drop-after', 'uve-drop-inside');
            });
        },

        // =====================
        // INLINE EDITING (WYSIWYG)
        // =====================

        startInlineEdit(elementId) {
            // Only for text elements
            const element = document.querySelector(`[data-element-id="${elementId}"]`);
            if (!element) return;

            const type = element.dataset.elementType;
            if (!['heading', 'text', 'button', 'link', 'paragraph'].includes(type)) return;

            this.editingElementId = elementId;
            this.originalContent = element.textContent.trim();

            // Make element editable
            element.setAttribute('contenteditable', 'true');
            element.classList.add('uve-inline-editing');
            element.focus();

            // Select all text
            const range = document.createRange();
            range.selectNodeContents(element);
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);

            // Handle Enter key to finish editing
            element.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.finishInlineEdit();
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    this.cancelInlineEdit();
                }
            }, { once: false });
        },

        finishInlineEdit() {
            if (!this.editingElementId) return;

            const element = document.querySelector(`[data-element-id="${this.editingElementId}"]`);
            if (!element) return;

            const newContent = element.textContent.trim();

            // Remove contenteditable
            element.removeAttribute('contenteditable');
            element.classList.remove('uve-inline-editing');

            // Update via Livewire if changed
            if (newContent !== this.originalContent) {
                this.$wire.updateElementContent(this.editingElementId, newContent);
            }

            this.editingElementId = null;
            this.originalContent = '';
        },

        cancelInlineEdit() {
            if (!this.editingElementId) return;

            const element = document.querySelector(`[data-element-id="${this.editingElementId}"]`);
            if (element) {
                element.textContent = this.originalContent;
                element.removeAttribute('contenteditable');
                element.classList.remove('uve-inline-editing');
            }

            this.editingElementId = null;
            this.originalContent = '';
        },
    }
}

/**
 * Convert camelCase to kebab-case for CSS properties
 * CRITICAL: PHP sends mixed formats (camelCase from Canvas, kebab-case from formatToCss)
 * setProperty() ONLY works with kebab-case!
 */
function camelToKebab(str) {
    return str.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
}

/**
 * Global function for PHP $this->js() to call directly
 * Applies styles to element in iframe
 */
window.uveApplyStyles = function(data) {
    console.log('[UVE Global] uveApplyStyles called:', data);
    const { elementId, styles, hoverStyles, controlId, clientSeq } = data;

    // Ignore stale server updates for image-settings (rapid clicks can queue Livewire requests)
    if (controlId === 'image-settings' && typeof clientSeq === 'number') {
        window.__uveLastAppliedSeq = window.__uveLastAppliedSeq || {};
        const key = `${elementId}|${controlId}`;
        const lastApplied = window.__uveLastAppliedSeq[key] || 0;
        const latestLocal = window.__uveClientSeq || 0;

        if (clientSeq < lastApplied || (latestLocal && clientSeq < latestLocal)) {
            console.log('[UVE Global] Ignoring stale image-settings apply', {
                elementId,
                controlId,
                clientSeq,
                lastApplied,
                latestLocal,
            });
            return;
        }

        window.__uveLastAppliedSeq[key] = clientSeq;
    }

    // Find the iframe by class (ID is dynamic)
    const iframe = document.querySelector('.uve-edit-iframe');
    if (!iframe || !iframe.contentDocument) {
        console.warn('[UVE Global] Iframe not found or not accessible');
        return;
    }

    // Find element(s) in iframe (data-uve-id can be duplicated e.g. slider clones)
    const elements = iframe.contentDocument.querySelectorAll(`[data-uve-id="${elementId}"]`);
    if (!elements || elements.length === 0) {
        console.warn('[UVE Global] Element not found in iframe:', elementId);
        return;
    }

    // Apply styles - comprehensive handling for all Property Panel controls
    console.log('[UVE Global] Applying styles to element(s):', elementId, { count: elements.length }, styles);
    elements.forEach((element) => {
        if (styles) {
            // Properties with default values - when reset to default, REMOVE inline style
            const defaultValues = {
                'font-size': '16px',
                'font-weight': '400',
                'line-height': '',
                'letter-spacing': '',
                'text-transform': 'none',
                'text-decoration': 'none',
                'text-align': 'left'
            };

            // FIX #14f: Check if background styles should be applied to child element
            // When childBackgroundSource is set, gradient comes from child (e.g. .pd-cover__picture)
            const childBgSource = styles['childBackgroundSource'] || styles['child-background-source'];
            let bgTargetElement = element;

            if (childBgSource) {
                // Find the child element that actually has the background
                const childEl = element.querySelector(childBgSource);
                if (childEl) {
                    bgTargetElement = childEl;
                    console.log('[UVE Global] FIX #14f: Background will be applied to child:', childBgSource);
                } else {
                    console.warn('[UVE Global] FIX #14f: Child element not found:', childBgSource);
                }
            }

            // Background-related properties (to apply to bgTargetElement)
            const bgProps = ['background-image', 'background-color', 'background-size',
                             'background-position', 'background-repeat', 'background-attachment'];

            Object.entries(styles).forEach(([prop, value]) => {
                // Skip empty/null values
                if (value === null || value === undefined || value === '') return;

                // Skip metadata properties (not actual CSS)
                if (prop === 'childBackgroundSource' || prop === 'child-background-source') return;

                // CRITICAL FIX: Convert camelCase to kebab-case
                // PHP sends mixed formats - setProperty() only accepts kebab-case!
                const cssProp = camelToKebab(prop);

                // FIX #14f: Determine target element for this property
                const targetEl = bgProps.includes(cssProp) ? bgTargetElement : element;

                // For properties with defaults - handle reset properly
                if (defaultValues[cssProp] !== undefined) {
                    if (value !== defaultValues[cssProp]) {
                        // Non-default value - apply it
                        targetEl.style.setProperty(cssProp, value);
                        console.log('[UVE Global] Set style:', cssProp, '=', value, targetEl === bgTargetElement && childBgSource ? '(on child)' : '');
                    } else {
                        // Default value - REMOVE inline style so CSS cascade takes over
                        targetEl.style.removeProperty(cssProp);
                        console.log('[UVE Global] Reset to default (removed):', cssProp);
                    }
                    return;
                }

                // All other properties - apply directly
                targetEl.style.setProperty(cssProp, value);
                console.log('[UVE Global] Set style:', cssProp, '=', value, targetEl === bgTargetElement && childBgSource ? '(on child)' : '');
            });
        }

        // Apply hover styles (stored as data attribute for CSS :hover pseudo-class injection)
        if (hoverStyles && Object.keys(hoverStyles).length > 0) {
            console.log('[UVE Global] Storing hover styles:', hoverStyles);
            element.dataset.uveHoverStyles = JSON.stringify(hoverStyles);
        }
    });

    console.log('[UVE Global] Styles applied successfully');
};

/**
 * FAZA 4.5.1: Alpine.js component for Edit Mode Iframe communication
 * Handles postMessage between parent (PPM) and child (iframe)
 */
function uveEditCanvas() {
    return {
        // Selection state
        selectedElementId: null,
        selectedElementType: null,
        selectedBlockType: null,
        selectedEditable: null,
        selectedRect: null,
        selectedContent: null,
        selectedStyles: {},  // CRITICAL: Canvas styles for Panel synchronization

        // Iframe reference
        frameReady: false,

        // Helper: Parse block index from element ID (mirrors PHP parseBlockIndexFromUveId)
        // Format: "block-0", "block-1", "block-0-heading-0", etc.
        parseBlockIndex(elementId) {
            if (!elementId) return null;
            const match = elementId.match(/^block-(\d+)/);
            if (!match) return null;

            const domIndex = parseInt(match[1], 10);
            const blocksCount = this.$wire.blocks?.length || 0;

            // Direct match - DOM index exists in Livewire blocks
            if (domIndex < blocksCount) {
                return domIndex;
            }

            // Fallback: If only 1 block exists (raw-html case), all DOM blocks belong to it
            if (blocksCount === 1) {
                return 0;
            }

            return null;
        },

        // Computed: can edit text?
        get canEditText() {
            return this.selectedEditable && this.selectedEditable.includes('text');
        },

        // Computed: overlay positioning style
        get overlayStyle() {
            if (!this.selectedRect) return 'display: none;';

            // Get iframe position to offset the rect
            const iframe = this.$refs.editFrame;
            if (!iframe) return 'display: none;';

            const iframeRect = iframe.getBoundingClientRect();
            const container = iframe.parentElement;
            const containerRect = container.getBoundingClientRect();

            // Calculate position relative to container
            const left = this.selectedRect.left + (iframeRect.left - containerRect.left);
            const top = this.selectedRect.top + (iframeRect.top - containerRect.top);

            return `left: ${left}px; top: ${top}px; width: ${this.selectedRect.width}px; height: ${this.selectedRect.height}px;`;
        },

        // Computed: toolbar position style (for info and actions)
        // Default: TOP of selection, fallback: inside top when near edge
        get toolbarPositionStyle() {
            if (!this.selectedRect) return 'bottom: 100%; margin-bottom: 4px;';

            const iframe = this.$refs.editFrame;
            if (!iframe) return 'bottom: 100%; margin-bottom: 4px;';

            const iframeRect = iframe.getBoundingClientRect();
            const container = iframe.parentElement;
            const containerRect = container.getBoundingClientRect();

            // Calculate overlay position relative to container
            const overlayTop = this.selectedRect.top + (iframeRect.top - containerRect.top);
            const toolbarHeight = 36; // approx height of toolbar

            // Default: toolbar above selection (bottom: 100%)
            if (overlayTop >= toolbarHeight) {
                return 'bottom: 100%; margin-bottom: 4px;';
            }

            // Fallback: when too close to top edge, show inside selection at top
            return 'top: 4px;';
        },

        init() {
            // Listen for postMessage from iframe
            window.addEventListener('message', this.handleMessage.bind(this));

            // Listen for Livewire events to refresh iframe (property panel changes)
            // Use Livewire.on() instead of $wire.on() - works globally for browser events
            Livewire.on('uve-preview-refresh', () => {
                console.log('[UVE] uve-preview-refresh received');
                this.refreshIframe();
            });

            // Backup listener for media changes via browser CustomEvent (from $this->js())
            window.addEventListener('uve-media-applied', () => {
                console.log('[UVE] uve-media-applied browser event received');
                this.refreshIframe();
            });

            // Listen for style sync events - apply styles directly to DOM for instant feedback
            // Livewire 3.x: dispatch() sends browser events, listen with Livewire.on()
            Livewire.on('uve-sync-styles', (data) => {
                console.log('[UVE] uve-sync-styles event received, data:', data);
                // In Livewire 3.x with dispatch(), data comes as first param
                const eventData = Array.isArray(data) ? data[0] : data;
                console.log('[UVE] Parsed eventData:', eventData);
                this.applyStylesToElement(eventData?.elementId, eventData?.styles, eventData?.hoverStyles);
            });

            // ETAP_07h FIX #4: Listen for layer panel selection
            Livewire.on('uve-select-element-from-layers', (data) => {
                console.log('[UVE] uve-select-element-from-layers received, data:', data);
                const eventData = Array.isArray(data) ? data[0] : data;
                const elementId = eventData?.elementId;
                if (elementId) {
                    this.selectElementInIframe(elementId);
                    this.selectedElementId = elementId;
                }
            });

            // Cleanup on destroy
            this.$cleanup = () => {
                window.removeEventListener('message', this.handleMessage.bind(this));
            };
        },

        // Refresh iframe content after property panel changes
        async refreshIframe() {
            console.log('[UVE Canvas] refreshIframe() called');
            const iframe = this.$refs.editFrame;
            if (!iframe) {
                console.log('[UVE Canvas] No iframe ref found!');
                return;
            }

            // Get fresh HTML from Livewire component
            console.log('[UVE Canvas] Fetching fresh HTML from $wire.getEditModeHtml()...');
            const newSrcdoc = await this.$wire.getEditModeHtml();
            console.log('[UVE Canvas] Got new HTML, length:', newSrcdoc?.length);

            // Update iframe with new content
            iframe.srcdoc = newSrcdoc;
            this.frameReady = false;

            // Wait for iframe to load, then restore selection
            const savedElementId = this.selectedElementId;
            iframe.onload = () => {
                this.frameReady = true;
                if (savedElementId) {
                    // Re-select the element after refresh
                    setTimeout(() => {
                        this.selectElementInIframe(savedElementId);
                    }, 100);
                }
            };
        },

        // Select element in iframe by ID
        selectElementInIframe(elementId) {
            const iframe = this.$refs.editFrame;
            if (!iframe || !iframe.contentWindow) return;

            iframe.contentWindow.postMessage({
                type: 'uve:select-element',
                elementId: elementId
            }, '*');
        },

        // Apply styles directly to element in iframe for instant feedback
        applyStylesToElement(elementId, styles, hoverStyles) {
            const iframe = this.$refs.editFrame;
            if (!iframe || !iframe.contentDocument) return;

            const element = iframe.contentDocument.querySelector(`[data-uve-id="${elementId}"]`);
            if (!element) {
                console.log('[UVE] Element not found in iframe:', elementId);
                return;
            }

            console.log('[UVE] Applying styles to element:', elementId, styles);

            // Apply each CSS property (styles are already in kebab-case from PHP)
            if (styles && typeof styles === 'object') {
                Object.entries(styles).forEach(([prop, value]) => {
                    if (value !== null && value !== '' && value !== undefined) {
                        element.style.setProperty(prop, value);
                        console.log('[UVE] Set style:', prop, '=', value);
                    } else {
                        element.style.removeProperty(prop);
                    }
                });
            }

            // Store hover styles as data attribute for CSS pseudo-class handling
            if (hoverStyles && typeof hoverStyles === 'object' && Object.keys(hoverStyles).length > 0) {
                element.dataset.uveHoverStyles = JSON.stringify(hoverStyles);
            }
        },

        handleMessage(event) {
            const data = event.data;
            if (!data || !data.type || !data.type.startsWith('uve:')) return;

            switch (data.type) {
                case 'uve:select':
                    this.onElementSelected(data);
                    break;

                case 'uve:deselected':
                    this.clearSelection();
                    break;

                case 'uve:content-changed':
                    this.onContentChanged(data);
                    break;

                case 'uve:editing-started':
                    console.log('[UVE Parent] Inline editing started:', data.elementId);
                    break;

                case 'uve:delete-request':
                    this.onDeleteRequest(data);
                    break;

                case 'uve:updated':
                    console.log('[UVE Parent] Element updated:', data.elementId);
                    break;

                case 'uve:rect-update':
                    // Update overlay position on scroll
                    if (data.elementId === this.selectedElementId) {
                        this.selectedRect = data.rect;
                    }
                    break;
            }
        },

        onFrameLoad() {
            this.frameReady = true;
            console.log('[UVE Parent] Edit iframe loaded');
        },

        onElementSelected(data) {
            const prevElementId = this.selectedElementId;

            this.selectedElementId = data.elementId;
            this.selectedElementType = data.elementType;
            this.selectedBlockType = data.blockType;
            this.selectedEditable = data.editable;
            this.selectedRect = data.rect;
            this.selectedContent = data.content;
            // CRITICAL: Store Canvas styles for Panel synchronization
            this.selectedStyles = data.styles || {};

            // Only sync to Livewire if selection actually changed (prevents flicker)
            if (prevElementId !== data.elementId) {
                // Use $wire.$set() instead of method call to avoid full re-render
                // This prevents flickering when changing selection
                const blockIndex = this.parseBlockIndex(data.elementId);
                this.$wire.$set('selectedElementId', data.elementId);
                if (blockIndex !== null) {
                    this.$wire.$set('selectedBlockIndex', blockIndex);
                }
                // CRITICAL: Send Canvas styles to Panel for synchronization
                // This ensures Panel always shows the actual Canvas state
                // PP.0.4: Also send blockType for block-specific controls
                this.$wire.dispatch('element-selected', {
                    elementId: data.elementId,
                    canvasStyles: data.styles || {},
                    blockType: data.blockType || null
                });
            }
        },

        clearSelection() {
            if (this.selectedElementId === null) return; // Already cleared

            this.selectedElementId = null;
            this.selectedElementType = null;
            this.selectedBlockType = null;
            this.selectedEditable = null;
            this.selectedRect = null;
            this.selectedContent = null;

            // Use $wire.$set() instead of method call to avoid full re-render
            // This prevents flickering when clearing selection
            this.$wire.$set('selectedElementId', null);
            this.$wire.$set('selectedBlockIndex', null);
        },

        onContentChanged(data) {
            // Update Livewire state with new content
            this.$wire.updateElementContentFromIframe(data.elementId, data.content);
        },

        onDeleteRequest(data) {
            if (confirm('Czy na pewno chcesz usunac ten blok?')) {
                this.$wire.removeBlockFromIframe(data.elementId);
            }
        },

        // Action: Start inline edit in iframe
        editElement() {
            if (!this.selectedElementId || !this.$refs.editFrame) return;

            this.$refs.editFrame.contentWindow.postMessage({
                type: 'uve:start-edit',
                elementId: this.selectedElementId
            }, '*');
        },

        // Action: Scroll to element in iframe
        scrollToElement() {
            if (!this.selectedElementId || !this.$refs.editFrame) return;

            this.$refs.editFrame.contentWindow.postMessage({
                type: 'uve:scroll-to',
                elementId: this.selectedElementId
            }, '*');
        },

        // Action: Duplicate selected block
        duplicateElement() {
            if (!this.selectedElementId) return;

            this.$wire.duplicateBlockFromIframe(this.selectedElementId);

            // Clear selection after duplicate (new block will have different ID)
            this.clearSelection();
        },

        // Action: Delete selected block
        deleteElement() {
            if (!this.selectedElementId) return;

            if (confirm('Czy na pewno chcesz usunac ten blok?')) {
                this.$wire.removeBlockFromIframe(this.selectedElementId);
                this.clearSelection();
            }
        },

        // Action: Update element in iframe (for properties panel)
        updateInIframe(elementId, content, styles) {
            if (!this.$refs.editFrame) return;

            this.$refs.editFrame.contentWindow.postMessage({
                type: 'uve:update',
                elementId: elementId,
                content: content,
                styles: styles
            }, '*');
        },

        // Action: Deselect in iframe
        deselectInIframe() {
            if (!this.$refs.editFrame) return;

            this.$refs.editFrame.contentWindow.postMessage({
                type: 'uve:deselect'
            }, '*');

            this.clearSelection();
        }
    }
}

// Media Picker Modal Alpine Component - ETAP_07h PP.3
function uveMediaPickerModal() {
    return {
        activeTab: @entangle('mediaPickerActiveTab').live,
        isDragging: false,
        isUploading: false,
        selectedMediaId: null,
        selectedMediaUrl: null,

        init() {
            // Sync initial tab from Livewire
            this.activeTab = this.$wire.get('mediaPickerActiveTab') || 'gallery';
        },

        setActiveTab(tab) {
            this.activeTab = tab;
            this.$wire.set('mediaPickerActiveTab', tab);
        },

        selectMedia(mediaId, mediaUrl) {
            // Toggle selection - if same media clicked again, apply to element
            if (this.selectedMediaId === mediaId) {
                // Double-click behavior: apply media to element
                this.applyMedia();
            } else {
                // First click: just select
                this.selectedMediaId = mediaId;
                this.selectedMediaUrl = mediaUrl;
                console.log('[UVE MediaPicker] Selected media - id:', mediaId, 'url:', mediaUrl);
            }
        },

        applyMedia() {
            // Use this.selectedMediaId directly to avoid scope issues
            const mediaId = this.selectedMediaId;
            const mediaUrl = this.selectedMediaUrl;

            console.log('[UVE MediaPicker] Applying media - id:', mediaId, 'url:', mediaUrl);

            if (!mediaId) {
                console.error('[UVE MediaPicker] ERROR: No media selected!');
                return;
            }

            // Call Livewire method with media data, then trigger iframe refresh via event
            this.$wire.selectFromGallery({
                id: mediaId,
                url: mediaUrl,
                alt: ''
            }).then(() => {
                console.log('[UVE MediaPicker] selectFromGallery completed, dispatching uve-media-applied event');
                // Dispatch event that canvas component listens for (init at line ~2326)
                // Cannot call refreshIframe() directly - it's in a DIFFERENT Alpine component (canvas)
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('uve-media-applied'));
                    console.log('[UVE MediaPicker] uve-media-applied event dispatched');
                }, 100);
            });
        },

        confirmDelete(mediaId) {
            if (confirm('Czy na pewno chcesz usunac ten obraz z galerii?')) {
                console.log('[UVE MediaPicker] Deleting media:', mediaId);
                this.$wire.deleteFromGallery(mediaId);
                // Clear selection if deleted item was selected
                if (this.selectedMediaId === mediaId) {
                    this.selectedMediaId = null;
                    this.selectedMediaUrl = null;
                }
            }
        },

        handleUploadComplete(detail) {
            console.log('[UVE MediaPicker] Upload complete event:', detail);
            this.isUploading = false;
            // Switch to gallery tab and select the uploaded media
            this.activeTab = 'gallery';
            this.selectedMediaId = detail.mediaId;
            this.selectedMediaUrl = detail.mediaUrl;
        },

        handleMediaSelected(detail) {
            console.log('[UVE MediaPicker] Media selected event:', detail);
            this.selectedMediaId = detail.mediaId;
        },

        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;

            if (files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    this.isUploading = true;
                    console.log('[UVE MediaPicker] Starting drag & drop upload:', file.name);

                    // Trigger Livewire upload - uses Livewire 3.x $wire.upload() API
                    this.$wire.upload('mediaUploadFile', file,
                        // Success callback
                        (uploadedFilename) => {
                            console.log('[UVE MediaPicker] Upload complete:', uploadedFilename);
                            // handleUpload() is called automatically via updatedMediaUploadFile hook
                            // Auto-switch is handled by uve-upload-complete event
                        },
                        // Error callback
                        (error) => {
                            console.error('[UVE MediaPicker] Upload error:', error);
                            this.isUploading = false;
                            alert('Blad uploadu: ' + (error || 'Nieznany blad'));
                        },
                        // Progress callback
                        (event) => {
                            // Livewire 3.x uses event.detail.progress (0-100)
                            const progress = event.detail?.progress || 0;
                            console.log('[UVE MediaPicker] Progress:', progress);
                            this.$wire.set('uploadProgress', progress);
                        }
                    );
                } else {
                    alert('Wybierz plik obrazu (JPG, PNG, GIF, WebP)');
                }
            }
        }
    }
}
</script>
</div>
