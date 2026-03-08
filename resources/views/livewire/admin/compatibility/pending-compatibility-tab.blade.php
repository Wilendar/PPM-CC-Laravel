{{--
    PendingCompatibilityTab - Products before publication
    Tab 2 of Compatibility Management panel

    Dark theme ONLY - no light-mode classes
    Two-column layout matching Tab 1 (Dopasowania)
    Reuses vehicle tile CSS classes from compatibility-tiles.css
    Uses shared filters-bar partial
--}}
<div>
    @vite(['resources/css/products/compatibility-tiles.css'])

    {{-- Header --}}
    <div class="compat-panel-header">
        <div class="compat-header-content">
            <div class="compat-header-title">
                <i class="fas fa-clock"></i>
                <div>
                    <h1>Produkty przed publikacja</h1>
                    <p>Dopasowania czesci zamiennych z importu - dane zapisywane do pending products</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Bar (shared partial) --}}
    @include('livewire.admin.compatibility.partials.filters-bar', [
        'searchModel' => 'searchPart',
        'brands' => $brands,
        'showShopContext' => false,
        'shops' => null,
        'showNoMatches' => false,
        'showAdvancedFilters' => true,
    ])

    {{-- Two-Column Layout --}}
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
        {{-- LEFT: Pending Parts List --}}
        <div class="compat-parts-column" x-ref="partsCol">
            <div class="compat-parts-header">
                <h3>
                    <i class="fas fa-clock"></i>
                    Czesci (Pending)
                </h3>
                <span class="compat-parts-count">{{ $pendingParts->total() }}</span>
            </div>

            <div class="compat-parts-list">
                @forelse($pendingParts as $part)
                    @php
                        $counts = $this->getCompatCounts($part);
                        $isActive = $expandedPendingId === $part->id;
                    @endphp

                    <div
                        wire:key="pending-{{ $part->id }}"
                        class="compat-part-item {{ $isActive ? 'compat-part-item--active' : '' }}"
                        wire:click="expandProduct({{ $part->id }})"
                    >
                        <div class="compat-part-info">
                            <span class="compat-part-sku">{{ $part->sku }}</span>
                            <span class="compat-part-name">{{ \Illuminate\Support\Str::limit($part->name, 40) }}</span>
                        </div>

                        <div class="compat-part-counts">
                            @if($counts['original'] > 0)
                                <span class="compat-count-badge-original" title="Oryginal">
                                    {{ $counts['original'] }}
                                </span>
                            @endif
                            @if($counts['zamiennik'] > 0)
                                <span class="compat-count-badge-zamiennik" title="Zamiennik">
                                    {{ $counts['zamiennik'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="compat-parts-empty">
                        <i class="fas fa-check-circle"></i>
                        <p>Brak czesci oczekujacych</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="compat-parts-pagination">
                {{ $pendingParts->links() }}
            </div>
        </div>

        {{-- Resize Handle --}}
        <div class="compat-resize-handle"
             :class="{ 'is-dragging': isDragging }"
             @mousedown="onMouseDown($event)"
        ></div>

        {{-- RIGHT: Vehicle Tiles --}}
        <div class="compat-vehicles-column">
            @if($expandedPendingId)
                @php
                    $expandedPart = \App\Models\PendingProduct::find($expandedPendingId);
                @endphp

                {{-- Editing Header --}}
                <div class="compat-vehicles-header">
                    <div class="compat-editing-info">
                        <h3>
                            <i class="fas fa-edit"></i>
                            Edycja:
                        </h3>
                        <span class="compat-editing-sku">{{ $expandedPart?->sku ?? 'N/A' }}</span>
                        <span class="compat-editing-name">{{ \Illuminate\Support\Str::limit($expandedPart?->name ?? '', 60) }}</span>
                    </div>
                    <div class="compat-selection-counts">
                        <span class="compat-count-badge-original" title="Oryginal">O: {{ $this->getOriginalCount() }}</span>
                        <span class="compat-count-badge-zamiennik" title="Zamiennik">Z: {{ $this->getZamiennikCount() }}</span>
                    </div>
                </div>

                {{-- Selection Mode Toggle --}}
                <div class="selection-mode-bar">
                    <div class="selection-mode-toggle">
                        <button
                            class="mode-btn {{ $selectionMode === 'original' ? 'mode-btn--active-original' : '' }}"
                            wire:click="setSelectionMode('original')"
                        >
                            <i class="{{ $selectionMode === 'original' ? 'fas' : 'far' }} fa-circle"></i>
                            Oryginal ({{ $this->getOriginalCount() }})
                        </button>
                        <button
                            class="mode-btn {{ $selectionMode === 'zamiennik' ? 'mode-btn--active-zamiennik' : '' }}"
                            wire:click="setSelectionMode('zamiennik')"
                        >
                            <i class="{{ $selectionMode === 'zamiennik' ? 'fas' : 'far' }} fa-circle"></i>
                            Zamiennik ({{ $this->getZamiennikCount() }})
                        </button>
                    </div>
                </div>

                {{-- Vehicle Tiles by Brand --}}
                <div class="compat-tiles-container">
                    @forelse($vehiclesGrouped as $brandName => $brandVehicles)
                        <div class="brand-section" wire:key="pbrand-{{ $brandName }}">
                            <div
                                class="brand-section__header"
                                wire:click="toggleBrandCollapse('{{ $brandName }}')"
                            >
                                <div class="brand-section__title">
                                    <i class="fas fa-chevron-{{ $this->isBrandCollapsed($brandName) ? 'right' : 'down' }}"></i>
                                    <span>{{ $brandName }}</span>
                                    <span class="brand-section__count">({{ $brandVehicles->count() }})</span>
                                </div>
                                <div class="brand-section__actions">
                                    <button
                                        wire:click.stop="selectAllInBrand('{{ $brandName }}')"
                                        class="brand-btn brand-btn--select"
                                        title="Zaznacz wszystkie"
                                    >
                                        <i class="fas fa-check-double"></i>
                                    </button>
                                    <button
                                        wire:click.stop="deselectAllInBrand('{{ $brandName }}')"
                                        class="brand-btn brand-btn--deselect"
                                        title="Odznacz wszystkie"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            @unless($this->isBrandCollapsed($brandName))
                                <div class="vehicle-tiles-grid">
                                    @foreach($brandVehicles as $vehicle)
                                        <div
                                            wire:key="ptile-{{ $vehicle->id }}"
                                            class="vehicle-tile {{ $this->getVehicleStateClass($vehicle->id) }}"
                                            wire:click="toggleVehicle({{ $vehicle->id }})"
                                        >
                                            <div class="vehicle-tile__content">
                                                <span class="vehicle-tile__brand">{{ $vehicle->manufacturer }}</span>
                                                <span class="vehicle-tile__model">{{ $vehicle->name }}</span>
                                            </div>

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
                                        </div>
                                    @endforeach
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="compat-vehicles-empty">
                            <i class="fas fa-motorcycle"></i>
                            <p>Brak pojazdow dla wybranych filtrow</p>
                        </div>
                    @endforelse
                </div>

                {{-- Floating Action Bar --}}
                <div class="floating-action-bar floating-action-bar--visible">
                    <div class="floating-action-bar__left">
                        <span class="compat-count-badge-original">O: {{ $this->getOriginalCount() }}</span>
                        <span class="compat-count-badge-zamiennik">Z: {{ $this->getZamiennikCount() }}</span>
                    </div>
                    <div class="floating-action-bar__actions">
                        <button
                            wire:click="collapseProduct"
                            class="floating-btn floating-btn--cancel"
                        >
                            <i class="fas fa-times"></i>
                            Anuluj
                        </button>
                        <button
                            wire:click="saveCompatibilities"
                            class="floating-btn floating-btn--save"
                        >
                            <i class="fas fa-save"></i>
                            Zapisz dopasowania
                        </button>
                    </div>
                </div>
            @else
                {{-- Placeholder --}}
                <div class="compat-vehicles-placeholder">
                    <i class="fas fa-hand-pointer"></i>
                    <h3>Wybierz czesc z listy</h3>
                    <p>Kliknij na czesc z listy po lewej stronie, aby edytowac jej dopasowania do pojazdow</p>
                </div>
            @endif
        </div>
    </div>
</div>
