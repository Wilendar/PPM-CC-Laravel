<div class="compatibility-selector-component">
    {{-- Header --}}
    <div class="selector-header">
        <h3>Vehicle Compatibility</h3>
        <button wire:click="toggleEditMode"
                class="btn-toggle-mode"
                type="button"
                aria-label="{{ $editMode ? 'Switch to view mode' : 'Switch to edit mode' }}">
            {{ $editMode ? 'View Mode' : 'Edit Mode' }}
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @error('general')
        <div class="alert alert-danger">
            {{ $message }}
        </div>
    @enderror

    {{-- Search Panel (Edit Mode Only) --}}
    @if($editMode)
        <div class="search-panel">
            <h4>Search Vehicles</h4>

            <div class="search-filters">
                <input type="text"
                       wire:model.live.debounce.300ms="searchFilters.brand"
                       placeholder="Brand (e.g., Honda)"
                       class="search-input"
                       aria-label="Search by vehicle brand">

                <input type="text"
                       wire:model.live.debounce.300ms="searchFilters.model"
                       placeholder="Model (e.g., CBR 600)"
                       class="search-input"
                       aria-label="Search by vehicle model">

                <input type="number"
                       wire:model.live.debounce.300ms="searchFilters.year"
                       placeholder="Year (e.g., 2013)"
                       class="search-input year-input"
                       min="1900"
                       max="{{ now()->year + 1 }}"
                       aria-label="Search by vehicle year">
            </div>

            {{-- Search Results --}}
            @if($searchResults->isNotEmpty())
                <div class="search-results">
                    <div class="results-header">
                        <span>Found {{ $searchResults->count() }} vehicle{{ $searchResults->count() !== 1 ? 's' : '' }}</span>
                    </div>

                    <div class="vehicle-list" role="list">
                        @foreach($searchResults as $vehicle)
                            <div class="vehicle-row"
                                 wire:key="search-vehicle-{{ $product->sku }}-{{ $vehicle->id }}"
                                 role="listitem">
                                <div class="vehicle-info">
                                    <span class="vehicle-brand">{{ $vehicle->brand }}</span>
                                    <span class="vehicle-model">{{ $vehicle->model }} {{ $vehicle->variant }}</span>
                                    <span class="vehicle-years">({{ $vehicle->year_from }}-{{ $vehicle->year_to ?? 'present' }})</span>
                                    @if($vehicle->engine_capacity)
                                        <span class="vehicle-cc">{{ $vehicle->engine_capacity }}cc</span>
                                    @endif
                                </div>
                                <button wire:click="$set('selectedVehicleId', {{ $vehicle->id }})"
                                        class="btn-select-vehicle {{ $selectedVehicleId === $vehicle->id ? 'selected' : '' }}"
                                        type="button"
                                        aria-pressed="{{ $selectedVehicleId === $vehicle->id ? 'true' : 'false' }}">
                                    {{ $selectedVehicleId === $vehicle->id ? 'Selected' : 'Select' }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif(strlen($searchFilters['brand']) >= 2 || strlen($searchFilters['model']) >= 2)
                <div class="no-results" role="status">
                    No vehicles found. Try different search criteria.
                </div>
            @endif

            {{-- Add Compatibility Panel --}}
            @if($selectedVehicleId)
                <div class="add-compatibility-panel">
                    <select wire:model="selectedAttributeId"
                            class="attribute-select"
                            aria-label="Select compatibility type">
                        <option value="">Select compatibility type...</option>
                        @foreach($this->compatibilityAttributes as $attr)
                            <option value="{{ $attr->id }}">{{ $attr->name }}</option>
                        @endforeach
                    </select>

                    <button wire:click="addCompatibility"
                            class="btn-add-compatibility"
                            type="button"
                            wire:loading.attr="disabled"
                            wire:target="addCompatibility">
                        <span wire:loading.remove wire:target="addCompatibility">Add Compatibility</span>
                        <span wire:loading wire:target="addCompatibility">Adding...</span>
                    </button>
                </div>

                @error('selectedVehicleId')
                    <div class="alert alert-danger mt-2">
                        {{ $message }}
                    </div>
                @enderror
            @endif
        </div>
    @endif

    {{-- Compatibility List --}}
    <div class="compatibility-list">
        <div class="list-header">
            <h4>Compatible Vehicles ({{ $compatibilities->count() }})</h4>
        </div>

        @forelse($compatibilities as $compat)
            <div class="compatibility-row"
                 wire:key="compat-{{ $product->sku }}-{{ $compat->id }}"
                 role="article"
                 aria-label="Compatibility entry for {{ $compat->vehicleModel->getFullName() }}">

                {{-- Vehicle Details --}}
                <div class="vehicle-details">
                    <div class="vehicle-name">
                        {{ $compat->vehicleModel->getFullName() }}
                    </div>
                    <div class="vehicle-meta">
                        SKU: {{ $compat->vehicle_sku ?? $compat->vehicleModel->sku }}
                    </div>
                </div>

                {{-- Compatibility Details --}}
                <div class="compatibility-details">
                    {{-- Compatibility Attribute --}}
                    @if($editMode)
                        <select wire:change="updateAttribute({{ $compat->id }}, $event.target.value)"
                                class="attribute-select-inline"
                                aria-label="Compatibility attribute for {{ $compat->vehicleModel->getFullName() }}">
                            <option value="">No attribute</option>
                            @foreach($this->compatibilityAttributes as $attr)
                                <option value="{{ $attr->id }}" {{ $compat->compatibility_attribute_id === $attr->id ? 'selected' : '' }}>
                                    {{ $attr->name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        @if($compat->compatibilityAttribute)
                            <span class="attribute-badge attribute-badge-{{ strtolower(str_replace(' ', '-', $compat->compatibilityAttribute->name)) }}"
                                  role="status">
                                {{ $compat->compatibilityAttribute->name }}
                            </span>
                        @endif
                    @endif

                    {{-- Source Information --}}
                    <div class="source-info">
                        <span class="source-name">{{ $compat->compatibilitySource->name }}</span>
                        <span class="trust-level trust-{{ $compat->compatibilitySource->trust_level }}"
                              role="status"
                              aria-label="Trust level: {{ ucfirst($compat->compatibilitySource->trust_level) }}">
                            {{ ucfirst($compat->compatibilitySource->trust_level) }}
                        </span>
                    </div>

                    {{-- Verification Badge --}}
                    @if($compat->verified)
                        <div class="verified-badge" role="status">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Verified by {{ $compat->verifiedBy->name ?? 'Admin' }}
                        </div>
                    @elseif(auth()->user()->isAdmin() && $editMode)
                        <button wire:click="verifyCompatibility({{ $compat->id }})"
                                class="btn-verify"
                                type="button"
                                wire:loading.attr="disabled"
                                wire:target="verifyCompatibility({{ $compat->id }})"
                                aria-label="Verify compatibility for {{ $compat->vehicleModel->getFullName() }}">
                            <span wire:loading.remove wire:target="verifyCompatibility({{ $compat->id }})">Verify</span>
                            <span wire:loading wire:target="verifyCompatibility({{ $compat->id }})">Verifying...</span>
                        </button>
                    @endif
                </div>

                {{-- Remove Button (Edit Mode Only) --}}
                @if($editMode)
                    <button wire:click="removeCompatibility({{ $compat->id }})"
                            class="btn-remove-compat"
                            type="button"
                            wire:loading.attr="disabled"
                            wire:target="removeCompatibility({{ $compat->id }})"
                            aria-label="Remove compatibility for {{ $compat->vehicleModel->getFullName() }}">
                        <span wire:loading.remove wire:target="removeCompatibility({{ $compat->id }})">&times;</span>
                        <span wire:loading wire:target="removeCompatibility({{ $compat->id }})">...</span>
                    </button>
                @endif
            </div>
        @empty
            <div class="empty-state" role="status">
                <p>No vehicle compatibility defined yet.</p>
                @if($editMode)
                    <p class="hint">Use the search panel above to add compatible vehicles.</p>
                @else
                    <p class="hint">Switch to Edit Mode to add compatible vehicles.</p>
                @endif
            </div>
        @endforelse
    </div>
</div>
