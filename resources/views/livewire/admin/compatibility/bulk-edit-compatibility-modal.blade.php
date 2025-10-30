{{--
    BulkEditCompatibilityModal View - Excel-inspired UI

    FEATURES:
    - Bidirectional mode selector (Part→Vehicle / Vehicle→Part)
    - Search with multi-select checkboxes
    - Family grouping with "Select all [Family]" helpers
    - Preview table with duplicate/conflict detection
    - Transaction-safe apply button

    LIVEWIRE 3.x COMPLIANCE:
    - wire:key MANDATORY for all dynamic lists
    - Alpine.js x-data for modal state
    - $wire for property binding
    - wire:model.live for reactive search

    UX DESIGN:
    - Excel horizontal drag equivalent: 1 part × 26 vehicles
    - Excel vertical drag equivalent: 50 parts × 1 vehicle
    - Safety: Preview before apply
    - Performance: Debounced search, cached computed properties
--}}

<div
    x-data="{ open: @entangle('open') }"
    @open-bulk-modal.window="
        $wire.openModal($event.detail.direction, $event.detail.selectedIds);
        open = true;
    "
    class="bulk-edit-modal"
>
    @if($open)
    <div
        class="modal-overlay fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
        @click.self="$wire.close(); open = false;"
    >
        <div class="modal-container enterprise-card bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto p-6">

            {{-- SECTION 1: Header + Direction --}}
            <div class="modal-header flex justify-between items-center mb-6 border-b pb-4">
                <h2 class="text-2xl font-bold text-gray-800">Edycja masowa dopasowań</h2>
                <button
                    wire:click="close"
                    class="btn-close text-gray-400 hover:text-gray-600 text-3xl font-bold"
                    aria-label="Zamknij"
                >
                    ×
                </button>
            </div>

            {{-- Direction Selector --}}
            <div class="direction-selector mb-6 bg-gray-50 p-4 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kierunek operacji:</label>
                <div class="flex gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="radio"
                            wire:model.live="direction"
                            value="part_to_vehicle"
                            class="mr-2"
                        >
                        <span class="text-sm">
                            Część → Pojazd
                            <span class="text-gray-500">({{ count($selectedPartIds) }} części)</span>
                        </span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input
                            type="radio"
                            wire:model.live="direction"
                            value="vehicle_to_part"
                            class="mr-2"
                        >
                        <span class="text-sm">
                            Pojazd → Część
                            <span class="text-gray-500">({{ count($selectedVehicleIds) }} pojazdów)</span>
                        </span>
                    </label>
                </div>
            </div>

            {{-- SECTION 2: Selected Items Summary --}}
            <div class="selected-items-summary mb-6">
                @if($direction === 'part_to_vehicle')
                    <h3 class="text-lg font-semibold mb-3">Wybrane części ({{ count($selectedPartIds) }}):</h3>
                    <div class="selected-badges flex flex-wrap gap-2">
                        @foreach($this->selectedParts as $part)
                            <span
                                wire:key="selected-part-{{ $part->id }}"
                                class="badge badge-part bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"
                            >
                                {{ $part->sku }} - {{ \Illuminate\Support\Str::limit($part->name, 30) }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <h3 class="text-lg font-semibold mb-3">Wybrane pojazdy ({{ count($selectedVehicleIds) }}):</h3>
                    <div class="selected-badges flex flex-wrap gap-2">
                        @foreach($this->selectedVehicles as $vehicle)
                            <span
                                wire:key="selected-vehicle-{{ $vehicle->id }}"
                                class="badge badge-vehicle bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm"
                            >
                                {{ $vehicle->brand }} {{ $vehicle->model }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- SECTION 3: Search Target Items --}}
            <div class="search-section mb-6">
                <h3 class="text-lg font-semibold mb-3">
                    @if($direction === 'part_to_vehicle')
                        Wyszukaj pojazdy (SKU lub nazwa):
                    @else
                        Wyszukaj części (SKU lub nazwa):
                    @endif
                </h3>

                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="Wpisz SKU lub nazwę..."
                    class="search-input w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >

                {{-- Search Results --}}
                @if($searchResults && $searchResults->count() > 0)
                    <div class="search-results mt-4 max-h-80 overflow-y-auto border border-gray-200 rounded-lg p-4">

                        @if($direction === 'part_to_vehicle')
                            {{-- Group by vehicle family --}}
                            @foreach($this->vehicleFamilies as $family => $vehicles)
                                <div wire:key="family-{{ Str::slug($family) }}" class="family-group mb-4">
                                    <div class="family-header flex justify-between items-center bg-gray-100 px-3 py-2 rounded">
                                        <span class="font-semibold text-gray-700">
                                            {{ $family }}
                                            <span class="text-gray-500 text-sm">({{ count($vehicles) }} pojazdów)</span>
                                        </span>
                                        <button
                                            wire:click="selectAllFamily('{{ $family }}')"
                                            class="btn-family-helper text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            Zaznacz wszystkie {{ $family }}*
                                        </button>
                                    </div>

                                    <div class="family-items pl-4 mt-2 space-y-2">
                                        @foreach($vehicles as $vehicle)
                                            <label
                                                wire:key="vehicle-{{ $vehicle->id }}"
                                                class="search-result-item flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer"
                                            >
                                                <input
                                                    type="checkbox"
                                                    wire:click="toggleTarget({{ $vehicle->id }})"
                                                    @checked(in_array($vehicle->id, $selectedTargetIds))
                                                    class="mr-3"
                                                >
                                                <span class="flex-1">{{ $vehicle->brand }} {{ $vehicle->model }}</span>
                                                <span class="sku-hint text-gray-500 text-sm">({{ $vehicle->sku }})</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            {{-- Simple list for parts --}}
                            <div class="space-y-2">
                                @foreach($searchResults as $part)
                                    <label
                                        wire:key="part-{{ $part->id }}"
                                        class="search-result-item flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:click="toggleTarget({{ $part->id }})"
                                            @checked(in_array($part->id, $selectedTargetIds))
                                            class="mr-3"
                                        >
                                        <span class="flex-1">{{ $part->sku }} - {{ \Illuminate\Support\Str::limit($part->name, 50) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @elseif(strlen($searchQuery) >= 2)
                    <p class="text-gray-500 text-sm mt-2">Brak wyników dla "{{ $searchQuery }}"</p>
                @endif
            </div>

            {{-- SECTION 4: Compatibility Type --}}
            <div class="compatibility-type-selector mb-6 bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-3">Typ dopasowania:</h3>

                <div class="flex gap-6">
                    <label class="type-radio flex items-center cursor-pointer">
                        <input
                            type="radio"
                            wire:model.live="compatibilityType"
                            value="original"
                            class="mr-2"
                        >
                        <span class="type-badge badge-original bg-green-100 text-green-800 px-3 py-1 rounded-full">
                            Oryginał
                        </span>
                        <span class="type-description text-gray-600 text-sm ml-2">OEM parts (original fit)</span>
                    </label>

                    <label class="type-radio flex items-center cursor-pointer">
                        <input
                            type="radio"
                            wire:model.live="compatibilityType"
                            value="replacement"
                            class="mr-2"
                        >
                        <span class="type-badge badge-replacement bg-orange-100 text-orange-800 px-3 py-1 rounded-full">
                            Zamiennik
                        </span>
                        <span class="type-description text-gray-600 text-sm ml-2">Aftermarket equivalent</span>
                    </label>
                </div>
            </div>

            {{-- SECTION 5: Preview Table --}}
            @if($showPreview && !empty($previewData))
                <div class="preview-section mb-6 border border-gray-300 rounded-lg p-4">
                    <h3 class="text-lg font-semibold mb-3">
                        Podgląd zmian ({{ count($previewData['new'] ?? []) }} nowych):
                    </h3>

                    <div class="overflow-x-auto max-h-60 overflow-y-auto">
                        <table class="preview-table w-full text-sm">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left">Część</th>
                                    <th class="px-3 py-2 text-left">Pojazd</th>
                                    <th class="px-3 py-2 text-left">Typ</th>
                                    <th class="px-3 py-2 text-left">Akcja</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- New entries (green) --}}
                                @foreach($previewData['new'] ?? [] as $index => $item)
                                    <tr wire:key="preview-new-{{ $index }}" class="preview-row-new bg-green-50 border-b">
                                        <td class="px-3 py-2">{{ $item['part_sku'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2">{{ $item['vehicle_name'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="badge badge-{{ $compatibilityType }} px-2 py-1 rounded text-xs">
                                                {{ ucfirst($compatibilityType) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-green-600 font-semibold">➕ ADD</td>
                                    </tr>
                                @endforeach

                                {{-- Duplicates (yellow warning) --}}
                                @foreach($previewData['duplicates'] ?? [] as $index => $dup)
                                    <tr wire:key="preview-dup-{{ $index }}" class="preview-row-duplicate bg-yellow-50 border-b">
                                        <td class="px-3 py-2">{{ $dup['part_sku'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2">{{ $dup['vehicle_name'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2">{{ $dup['attribute'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2 text-yellow-600 font-semibold">⚠️ SKIP (exists)</td>
                                    </tr>
                                @endforeach

                                {{-- Conflicts (red warning) --}}
                                @foreach($previewData['conflicts'] ?? [] as $index => $conf)
                                    <tr wire:key="preview-conflict-{{ $index }}" class="preview-row-conflict bg-red-50 border-b">
                                        <td class="px-3 py-2">{{ $conf['part_sku'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2">{{ $conf['vehicle_name'] ?? 'N/A' }}</td>
                                        <td class="px-3 py-2">
                                            <span class="badge badge-conflict bg-red-200 text-red-800 px-2 py-1 rounded text-xs">
                                                Exists as {{ $conf['existing_attribute'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-red-600 font-semibold">⚠️ CONFLICT</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Error/Success Messages --}}
            @if($errorMessage)
                <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded mb-4">
                    {{ $errorMessage }}
                </div>
            @endif

            @if($successMessage)
                <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded mb-4">
                    {{ $successMessage }}
                </div>
            @endif

            {{-- SECTION 6: Footer Actions --}}
            <div class="modal-footer flex justify-end gap-3 pt-4 border-t">
                <button
                    wire:click="close"
                    class="btn-cancel px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
                >
                    Anuluj
                </button>

                <button
                    wire:click="generatePreview"
                    class="btn-preview px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    @disabled(empty($selectedTargetIds))
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>
                        Podgląd
                        @if(!empty($selectedPartIds) && !empty($selectedTargetIds))
                            ({{ count($selectedPartIds) * count($selectedTargetIds) }} zmian)
                        @endif
                    </span>
                    <span wire:loading>Generowanie...</span>
                </button>

                <button
                    wire:click="apply"
                    class="btn-apply px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    @disabled(!$showPreview || $isProcessing)
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="apply">Zastosuj</span>
                    <span wire:loading wire:target="apply">Przetwarzanie...</span>
                </button>
            </div>

        </div>
    </div>
    @endif
</div>
