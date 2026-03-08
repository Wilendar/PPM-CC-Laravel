{{--
    CompatibilityManagement - Tile-Based Vehicle Compatibility UI
    ETAP_05d FAZA 3 - Per-shop compatibility editing

    Features:
    - Tile-based vehicle selection (click = toggle)
    - Per-shop filtering
    - Advanced filters (category, manufacturer, shop assignment, compat count)
    - Filter presets (save/load)
    - Smart suggestions panel
    - Bulk actions
    - Collapsible brand sections
--}}
<div class="compatibility-management-panel">
    @vite(['resources/css/products/compatibility-tiles.css'])

    {{-- Tab Navigation --}}
    <div class="tabs-enterprise">
        <button class="tab-enterprise {{ $activeTab === 'published' ? 'active' : '' }}"
                wire:click="switchTab('published')">
            <i class="fas fa-link icon"></i>
            <span>Dopasowania</span>
        </button>
        @if($this->canAccessPendingTab())
            <button class="tab-enterprise {{ $activeTab === 'pending' ? 'active' : '' }}"
                    wire:click="switchTab('pending')">
                <i class="fas fa-clock icon"></i>
                <span>Przed publikacja</span>
                @if($pendingCount > 0)
                    <span class="compat-tab-badge">{{ $pendingCount }}</span>
                @endif
            </button>
        @endif
    </div>

    @if($activeTab === 'published')
    {{-- Header --}}
    <div class="compat-panel-header">
        <div class="compat-header-content">
            <div class="compat-header-title">
                <i class="fas fa-link"></i>
                <div>
                    <h1>Dopasowania Czesci Zamiennych</h1>
                    <p>Zarzadzanie globalnymi dopasowaniami czesci do pojazdow - centralna baza dla wszystkich sklepow</p>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="compat-stats">
                <div class="compat-stat">
                    <span class="compat-stat-value">{{ $statistics['total_compatibilities'] ?? 0 }}</span>
                    <span class="compat-stat-label">Dopasowan</span>
                </div>
                <div class="compat-stat">
                    <span class="compat-stat-value">{{ $statistics['unique_products'] ?? 0 }}</span>
                    <span class="compat-stat-label">Czesci</span>
                </div>
                <div class="compat-stat">
                    <span class="compat-stat-value">{{ $statistics['unique_vehicles'] ?? 0 }}</span>
                    <span class="compat-stat-label">Pojazdow</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Bar (shared partial) --}}
    @include('livewire.admin.compatibility.partials.filters-bar', [
        'searchModel' => 'searchPart',
        'brands' => $brands,
        'showShopContext' => true,
        'shops' => $shops,
        'showNoMatches' => true,
        'showAdvancedFilters' => true,
    ])

    {{-- Bulk Action Bar --}}
    @include('livewire.admin.compatibility.partials.bulk-action-bar')

    {{-- Main Content: Two-Column Layout --}}
    <div class="compat-main-content"
         x-data="{
             isDragging: false,
             startX: 0,
             startWidth: 0,
             onMouseDown(e) {
                 this.isDragging = true;
                 this.startX = e.clientX;
                 this.startWidth = this.$refs.partsCol.offsetWidth;
                 e.preventDefault();
             },
             onMouseMove(e) {
                 if (!this.isDragging) return;
                 const diff = e.clientX - this.startX;
                 const newWidth = Math.max(280, Math.min(this.startWidth + diff, window.innerWidth * 0.5));
                 this.$refs.partsCol.style.width = newWidth + 'px';
             },
             onMouseUp() {
                 this.isDragging = false;
             }
         }"
         @mousemove.window="onMouseMove($event)"
         @mouseup.window="onMouseUp()"
    >
        {{-- Left: Parts List --}}
        <div class="compat-parts-column" x-ref="partsCol">
            <div class="compat-parts-header">
                <h3>
                    <i class="fas fa-puzzle-piece"></i>
                    Lista Czesci
                </h3>
                <div class="compat-parts-header-actions">
                    {{-- Select All on Page --}}
                    <label class="compat-checkbox-label" title="Zaznacz wszystkie na stronie">
                        <input
                            type="checkbox"
                            wire:model.live="selectAllOnPage"
                            class="compat-checkbox"
                        />
                        <span>Zaznacz</span>
                    </label>
                </div>
            </div>

            <div class="compat-parts-list">
                @forelse($parts as $part)
                    <div
                        wire:key="part-{{ $part->id }}"
                        class="compat-part-item {{ $editingProductId === $part->id ? 'compat-part-item--active' : '' }} {{ $this->productHasUnsavedChanges($part->id) ? 'compat-part-item--unsaved' : '' }}"
                        wire:click="editPart({{ $part->id }})"
                    >
                        <div class="compat-part-checkbox" @click.stop>
                            <input
                                type="checkbox"
                                wire:model.live="selectedPartIds"
                                value="{{ $part->id }}"
                                wire:key="select-part-{{ $part->id }}"
                                class="compat-checkbox"
                            />
                        </div>

                        <div class="compat-part-info">
                            <span class="compat-part-sku">
                                {{ $part->sku }}
                                @if($this->productHasUnsavedChanges($part->id))
                                    <span class="compat-unsaved-badge" title="Niezapisane zmiany">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </span>
                                @endif
                            </span>
                            <span class="compat-part-name">{{ Str::limit($part->name, 40) }}</span>
                        </div>

                        <div class="compat-part-counts">
                            <span class="compat-count-badge-original" title="Oryginal">
                                {{ $part->original_count }}
                            </span>
                            <span class="compat-count-badge-zamiennik" title="Zamiennik">
                                {{ $part->replacement_count }}
                            </span>
                        </div>

                        <div class="compat-part-status">
                            @if($part->sync_status === 'synced')
                                <span class="compat-status-badge compat-status-synced" title="{{ $part->sync_shop_names ?? '' }}">
                                    <i class="fas fa-check-circle"></i>
                                    {{ $part->sync_shops_count }} {{ $part->sync_shops_count === 1 ? 'sklep' : 'sklepy' }}
                                </span>
                            @else
                                <span class="compat-status-badge compat-status-not-published">
                                    <i class="fas fa-times-circle"></i>
                                    Brak
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="compat-parts-empty">
                        <i class="fas fa-search"></i>
                        <p>Brak czesci spelniajacych kryteria</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="compat-parts-pagination">
                {{ $parts->links() }}
            </div>
        </div>

        {{-- Resize Handle --}}
        <div class="compat-resize-handle"
             :class="{ 'is-dragging': isDragging }"
             @mousedown="onMouseDown($event)"
        ></div>

        {{-- Right: Vehicle Tiles --}}
        <div class="compat-vehicles-column">
            @if($editingProductId)
                @php
                    $editingPart = $parts->firstWhere('id', $editingProductId);
                @endphp

                {{-- Editing Header --}}
                <div class="compat-vehicles-header">
                    <div class="compat-editing-info">
                        <h3>
                            <i class="fas fa-cog"></i>
                            Edycja dopasowań:
                        </h3>
                        <span class="compat-editing-sku">{{ $editingPart?->sku ?? 'Produkt' }}</span>
                    </div>

                    <div class="compat-selection-counts">
                        <span class="compat-count-label">
                            <i class="fas fa-circle compat-icon-original"></i>
                            Oryginal: {{ $this->getOriginalCount() }}
                        </span>
                        <span class="compat-count-label">
                            <i class="fas fa-circle compat-icon-zamiennik"></i>
                            Zamiennik: {{ $this->getZamiennikCount() }}
                        </span>
                    </div>
                </div>

                {{-- Suggestions Panel --}}
                @if($showSuggestions && $suggestions->count() > 0)
                    <div class="suggestions-panel">
                        <div class="suggestions-panel__header">
                            <h4>
                                <i class="fas fa-lightbulb"></i>
                                Sugestie AI ({{ $suggestions->count() }})
                            </h4>
                            <button wire:click="$toggle('showSuggestions')" class="suggestions-toggle">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="suggestions-panel__list">
                            @foreach($suggestions as $suggestion)
                                <div class="suggestion-item" wire:key="sugg-{{ $suggestion->id }}">
                                    <div class="suggestion-info">
                                        <span class="suggestion-vehicle">
                                            {{ $suggestion->vehicleModel?->brand }} {{ $suggestion->vehicleModel?->model }}
                                        </span>
                                        <span class="suggestion-score">
                                            {{ number_format($suggestion->confidence_score * 100, 0) }}%
                                        </span>
                                    </div>
                                    <div class="suggestion-actions">
                                        <button
                                            wire:click="applySuggestion({{ $suggestion->id }}, 'original')"
                                            class="suggestion-btn suggestion-btn--original"
                                            title="Dodaj jako Oryginal"
                                        >
                                            <i class="fas fa-plus"></i> O
                                        </button>
                                        <button
                                            wire:click="applySuggestion({{ $suggestion->id }}, 'replacement')"
                                            class="suggestion-btn suggestion-btn--replacement"
                                            title="Dodaj jako Zamiennik"
                                        >
                                            <i class="fas fa-plus"></i> Z
                                        </button>
                                        <button
                                            wire:click="dismissSuggestion({{ $suggestion->id }})"
                                            class="suggestion-btn suggestion-btn--dismiss"
                                            title="Odrzuc"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Vehicle Tiles Grid by Brand --}}
                <div class="compat-tiles-container">
                    @forelse($vehiclesGrouped as $brand => $brandVehicles)
                        <div class="brand-section" wire:key="brand-{{ $brand }}">
                            <div
                                class="brand-section__header"
                                wire:click="toggleBrandCollapse('{{ $brand }}')"
                            >
                                <div class="brand-section__title">
                                    <i class="fas fa-chevron-{{ $this->isBrandCollapsed($brand) ? 'right' : 'down' }}"></i>
                                    <span>{{ $brand }}</span>
                                    <span class="brand-section__count">({{ $brandVehicles->count() }})</span>
                                </div>
                                <div class="brand-section__actions">
                                    <button
                                        wire:click.stop="selectAllInBrand('{{ $brand }}')"
                                        class="brand-btn brand-btn--select"
                                        title="Zaznacz wszystkie w marce"
                                    >
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                    <button
                                        wire:click.stop="deselectAllInBrand('{{ $brand }}')"
                                        class="brand-btn brand-btn--deselect"
                                        title="Odznacz wszystkie w marce"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            @unless($this->isBrandCollapsed($brand))
                                <div class="vehicle-tiles-grid">
                                    @foreach($brandVehicles as $vehicle)
                                        @php
                                            $isApproved = $this->isOriginal($vehicle->id) || $this->isZamiennik($vehicle->id);
                                            $isAiSuggested = isset($suggestedVehicleScores[$vehicle->id]) && !$isApproved;
                                            $aiScore = $isAiSuggested ? ($suggestedVehicleScores[$vehicle->id] ?? null) : null;
                                        @endphp
                                        <div
                                            wire:key="tile-{{ $vehicle->id }}"
                                            class="vehicle-tile {{ $this->getVehicleStateClass($vehicle->id) }} {{ $isAiSuggested ? 'vehicle-tile--ai-suggested' : '' }}"
                                            wire:click="toggleVehicle({{ $vehicle->id }})"
                                        >
                                            <div class="vehicle-tile__content">
                                                <span class="vehicle-tile__brand">{{ $vehicle->manufacturer }}</span>
                                                <span class="vehicle-tile__model">{{ $vehicle->name }}</span>
                                            </div>

                                            {{-- Selection Indicator --}}
                                            @if($this->isBoth($vehicle->id))
                                                <div class="vehicle-tile__indicator vehicle-tile__indicator--both">
                                                    <span>O+Z</span>
                                                </div>
                                            @elseif($this->isOriginal($vehicle->id))
                                                <div class="vehicle-tile__indicator vehicle-tile__indicator--original">
                                                    <span>O</span>
                                                </div>
                                            @elseif($this->isZamiennik($vehicle->id))
                                                <div class="vehicle-tile__indicator vehicle-tile__indicator--zamiennik">
                                                    <span>Z</span>
                                                </div>
                                            @endif

                                            {{-- AI Suggestion Overlay --}}
                                            @if($isAiSuggested)
                                                <span class="vehicle-tile__ai-badge">AI</span>
                                                <span class="vehicle-tile__confidence">{{ round($aiScore * 100) }}%</span>
                                                <div class="ai-hover-overlay">
                                                    <div class="ai-hover-overlay__accept" wire:click.stop="toggleVehicle({{ $vehicle->id }})">
                                                        <span class="text-white text-lg">&#10003;</span>
                                                    </div>
                                                    <div class="ai-hover-overlay__dismiss" wire:click.stop="dismissAiSuggestion({{ $vehicle->id }})">
                                                        <span class="text-white text-lg">&#10005;</span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="compat-vehicles-empty">
                            <i class="fas fa-motorcycle"></i>
                            <p>Brak pojazdow dla wybranych filtrow</p>
                            @if($filterBrand || $shopContext)
                                <button wire:click="resetFilters" class="compat-btn-reset">
                                    <i class="fas fa-undo"></i>
                                    Resetuj filtry
                                </button>
                            @endif
                        </div>
                    @endforelse
                </div>

                {{-- Floating Action Bar --}}
                <div class="floating-action-bar floating-action-bar--visible"
                    @if($this->hasSyncJobsActive())
                        wire:poll.2s="refreshSyncStatus"
                    @endif
                >
                    <div class="floating-action-bar__left">
                        {{-- Selection Mode Toggle --}}
                        <div class="selection-mode-toggle">
                            <button
                                class="mode-btn {{ $selectionMode === 'original' ? 'mode-btn--active-original' : '' }}"
                                wire:click="setSelectionMode('original')"
                            >
                                <i class="{{ $selectionMode === 'original' ? 'fas' : 'far' }} fa-circle"></i>
                                Oryginal
                            </button>
                            <button
                                class="mode-btn {{ $selectionMode === 'zamiennik' ? 'mode-btn--active-zamiennik' : '' }}"
                                wire:click="setSelectionMode('zamiennik')"
                            >
                                <i class="{{ $selectionMode === 'zamiennik' ? 'fas' : 'far' }} fa-circle"></i>
                                Zamiennik
                            </button>
                        </div>

                        {{-- Pending Changes Counter --}}
                        @if($this->hasPendingChanges())
                            <span class="pending-changes-badge">
                                {{ $this->getPendingChangesCount() }} zmian
                            </span>
                        @endif

                        {{-- Sync Job Status Badge --}}
                        @if($this->hasSyncJobsActive())
                            @php $syncStatus = $this->getOverallSyncStatus(); @endphp
                            <span class="sync-status-badge sync-status-{{ $syncStatus }}">
                                @if($syncStatus === 'pending')
                                    <i class="fas fa-clock fa-spin"></i>
                                    Oczekuje...
                                @elseif($syncStatus === 'running')
                                    <i class="fas fa-sync fa-spin"></i>
                                    Synchronizacja...
                                @elseif($syncStatus === 'completed')
                                    <i class="fas fa-check"></i>
                                    Ukonczone
                                @elseif($syncStatus === 'failed')
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Blad
                                @endif
                            </span>
                        @endif
                    </div>

                    <div class="floating-action-bar__right">
                        <button
                            wire:click="cancelEdit"
                            class="floating-btn floating-btn--cancel"
                        >
                            <i class="fas fa-times"></i>
                            Anuluj
                        </button>
                        <button
                            wire:click="saveCompatibility"
                            class="floating-btn floating-btn--save"
                            @if($this->isSyncInProgress()) disabled @endif
                        >
                            <i class="fas fa-save"></i>
                            Zapisz
                        </button>
                        <button
                            wire:click="saveAndSync"
                            class="floating-btn floating-btn--sync"
                            @if($this->isSyncInProgress()) disabled @endif
                        >
                            @if($this->isSyncInProgress())
                                <i class="fas fa-spinner fa-spin"></i>
                                Synchronizacja...
                            @else
                                <i class="fas fa-cloud-upload-alt"></i>
                                Zapisz i wyslij
                            @endif
                        </button>
                    </div>
                </div>
            @else
                {{-- No Part Selected --}}
                <div class="compat-vehicles-placeholder">
                    <i class="fas fa-hand-pointer"></i>
                    <h3>Wybierz czesc z listy</h3>
                    <p>Kliknij na czesc po lewej stronie, aby edytowac jej dopasowania do pojazdow.</p>

                    <div class="compat-placeholder-legend">
                        <div class="legend-item">
                            <span class="legend-color legend-color--original"></span>
                            <span>Oryginal - czesc pasuje do pojazdu oryginalnie</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color legend-color--zamiennik"></span>
                            <span>Zamiennik - czesc moze zastapic oryginalna</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color legend-color--both"></span>
                            <span>Oba - czesc jest zarowno oryginalem jak i zamiennikiem</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color legend-color--suggestion"></span>
                            <span>Sugestia AI - propozycja systemu</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    {{-- Bulk Edit Modal (listens for @open-bulk-modal.window) --}}
    @livewire('admin.compatibility.bulk-edit-compatibility-modal')

    @elseif($activeTab === 'pending')
        <livewire:admin.compatibility.pending-compatibility-tab />
    @endif
</div>
