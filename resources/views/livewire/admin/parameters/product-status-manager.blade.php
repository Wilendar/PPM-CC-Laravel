<div>
    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        {{-- Search --}}
        <div class="relative flex-1 max-w-xs">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Szukaj statusu..."
                   class="form-input-dark w-full pl-10">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        {{-- Add Button --}}
        <button wire:click="openCreateModal" class="btn-enterprise-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Dodaj status
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-white">{{ $this->stats['total'] }}</div>
            <div class="text-sm text-gray-400">Wszystkie</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-green-400">{{ $this->stats['active_equivalent'] }}</div>
            <div class="text-sm text-gray-400">Aktywne</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-gray-500">{{ $this->stats['inactive_equivalent'] }}</div>
            <div class="text-sm text-gray-400">Nieaktywne</div>
        </div>
        <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-blue-400">{{ $this->stats['in_use'] }}</div>
            <div class="text-sm text-gray-400">Uzywane</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-gray-800/50 rounded-lg border border-gray-700 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kolor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nazwa</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Slug</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Aktywny</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Domyslny</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Produkty</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Integracje</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($this->statuses as $status)
                    <tr wire:key="status-{{ $status->id }}" class="hover:bg-gray-700/50 transition-colors">
                        {{-- Color dot --}}
                        <td class="px-4 py-3">
                            <span class="inline-block w-5 h-5 rounded-full border-2 border-gray-600"
                                  style="background-color: {{ $status->color }}"></span>
                        </td>

                        {{-- Name + Icon + Auto badge --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($status->icon)
                                    <span class="text-gray-400 text-sm">{{ $status->icon }}</span>
                                @endif
                                <span class="text-sm font-medium text-white">{{ $status->name }}</span>
                                @if($status->transition_on_stock_depleted && $status->transition_to_status_id)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-900/40 text-amber-400" title="Auto-transition po wyczerpaniu zapasow">
                                        Auto
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Slug --}}
                        <td class="px-4 py-3">
                            <code class="text-xs text-gray-400 bg-gray-800 px-2 py-1 rounded">{{ $status->slug }}</code>
                        </td>

                        {{-- Active equivalent badge --}}
                        <td class="px-4 py-3 text-center">
                            @if($status->is_active_equivalent)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-400">
                                    Tak
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-700 text-gray-400">
                                    Nie
                                </span>
                            @endif
                        </td>

                        {{-- Default badge --}}
                        <td class="px-4 py-3 text-center">
                            @if($status->is_default)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-900/50 text-orange-400">
                                    Domyslny
                                </span>
                            @else
                                <span class="text-xs text-gray-500">-</span>
                            @endif
                        </td>

                        {{-- Products count --}}
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm text-gray-300">{{ $status->products_count }}</span>
                        </td>

                        {{-- Integration mappings --}}
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                @foreach($status->integrationMappings as $mapping)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium
                                        {{ $mapping->maps_to_active ? 'bg-green-900/40 text-green-400' : 'bg-red-900/40 text-red-400' }}"
                                        title="{{ \App\Models\ProductStatusIntegrationMapping::TYPES[$mapping->integration_type] ?? $mapping->integration_type }}: {{ $mapping->maps_to_active ? 'Aktywny' : 'Nieaktywny' }}">
                                        @switch($mapping->integration_type)
                                            @case('prestashop')
                                                PS
                                                @break
                                            @case('baselinker')
                                                BL
                                                @break
                                            @case('subiekt_gt')
                                                SG
                                                @break
                                            @default
                                                {{ strtoupper(substr($mapping->integration_type, 0, 2)) }}
                                        @endswitch
                                    </span>
                                @endforeach
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openEditModal({{ $status->id }})"
                                        class="p-1.5 text-gray-400 hover:text-white rounded hover:bg-gray-700"
                                        title="Edytuj">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $status->id }})"
                                        class="p-1.5 text-gray-400 hover:text-red-400 rounded hover:bg-gray-700"
                                        title="Usun">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-10 w-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                            </svg>
                            Brak statusow. Kliknij "Dodaj status" aby utworzyc pierwszy.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="modal-overlay show" wire:click.self="closeModal">
            <div class="modal sm:max-w-xl w-full mx-4" @click.stop>
                <form wire:submit="save">
                    {{-- Modal Header --}}
                    <div class="modal-header">
                        <h3 class="text-lg font-medium text-white">
                            {{ $editingId ? 'Edytuj status' : 'Dodaj nowy status' }}
                        </h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa statusu *</label>
                            <input type="text"
                                   wire:model="formData.name"
                                   class="form-input-dark w-full"
                                   placeholder="np. Aktywny, Wycofany, Sezonowy...">
                            @error('formData.name')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Color + Icon row --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Color --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Kolor *</label>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           wire:model="formData.color"
                                           class="w-10 h-10 rounded border border-gray-600 bg-gray-700 cursor-pointer p-0.5">
                                    <input type="text"
                                           wire:model="formData.color"
                                           class="form-input-dark flex-1"
                                           placeholder="#6b7280"
                                           maxlength="7">
                                </div>
                                @error('formData.color')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Icon --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Ikona (emoji/tekst)</label>
                                <input type="text"
                                       wire:model="formData.icon"
                                       class="form-input-dark w-full"
                                       placeholder="np. &#x2705; &#x26A0;&#xFE0F; &#x274C;">
                                @error('formData.icon')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Sort order --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Kolejnosc sortowania</label>
                            <input type="number"
                                   wire:model="formData.sort_order"
                                   class="form-input-dark w-full"
                                   min="0"
                                   placeholder="0">
                        </div>

                        {{-- Checkboxes --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- is_active_equivalent --}}
                            <label class="flex items-center gap-3 p-3 bg-gray-700/30 rounded-lg border border-gray-600 cursor-pointer">
                                <input type="checkbox"
                                       wire:model="formData.is_active_equivalent"
                                       class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <div>
                                    <div class="text-sm font-medium text-white">Aktywny ekwiwalent</div>
                                    <div class="text-xs text-gray-400">Produkty z tym statusem traktowane jako aktywne</div>
                                </div>
                            </label>

                            {{-- is_default --}}
                            <label class="flex items-center gap-3 p-3 bg-gray-700/30 rounded-lg border border-gray-600 cursor-pointer">
                                <input type="checkbox"
                                       wire:model="formData.is_default"
                                       class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <div>
                                    <div class="text-sm font-medium text-white">Status domyslny</div>
                                    <div class="text-xs text-gray-400">Przypisywany nowym produktom automatycznie</div>
                                </div>
                            </label>
                        </div>

                        {{-- Integration Mappings Section --}}
                        <div class="bg-gray-700/30 rounded-lg p-4 border border-gray-600">
                            <h4 class="text-sm font-medium text-gray-300 mb-3">Mapowanie integracji</h4>
                            <p class="text-xs text-gray-500 mb-4">Okresl, czy produkty z tym statusem maja byc widoczne (aktywne) w poszczegolnych integracjach.</p>

                            <div class="space-y-3">
                                {{-- PrestaShop --}}
                                <label class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-900/50 text-blue-400">PS</span>
                                        <span class="text-sm text-gray-300">PrestaShop</span>
                                    </div>
                                    <input type="checkbox"
                                           wire:model="integrationMappings.prestashop"
                                           class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                </label>

                                {{-- BaseLinker --}}
                                <label class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-900/50 text-purple-400">BL</span>
                                        <span class="text-sm text-gray-300">BaseLinker</span>
                                    </div>
                                    <input type="checkbox"
                                           wire:model="integrationMappings.baselinker"
                                           class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                </label>

                                {{-- Subiekt GT --}}
                                <label class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-900/50 text-amber-400">SG</span>
                                        <span class="text-sm text-gray-300">Subiekt GT</span>
                                    </div>
                                    <input type="checkbox"
                                           wire:model="integrationMappings.subiekt_gt"
                                           class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                </label>
                            </div>
                        </div>

                        {{-- Auto-Transition Section --}}
                        <div class="bg-gray-700/30 rounded-lg p-4 border border-gray-600" x-data="{ autoTransition: @entangle('formData.transition_on_stock_depleted') }">
                            <h4 class="text-sm font-medium text-gray-300 mb-3">Auto-transition po wyczerpaniu zapasow</h4>
                            <p class="text-xs text-gray-500 mb-4">Status automatycznie zmieni sie na inny, gdy zapasy na monitorowanym magazynie spadna do 0.</p>

                            {{-- Enable checkbox --}}
                            <label class="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg cursor-pointer mb-3">
                                <input type="checkbox"
                                       wire:model.live="formData.transition_on_stock_depleted"
                                       class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <div>
                                    <div class="text-sm font-medium text-white">Aktywny do wyczerpania zapasow</div>
                                    <div class="text-xs text-gray-400">Automatyczna zmiana statusu gdy stock = 0</div>
                                </div>
                            </label>

                            {{-- Conditional fields --}}
                            <div x-show="autoTransition" x-transition class="space-y-3">
                                {{-- Target status --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Po wyczerpaniu zmien na *</label>
                                    <select wire:model="formData.transition_to_status_id"
                                            class="form-input-dark w-full">
                                        <option value="">-- Wybierz status docelowy --</option>
                                        @foreach($this->availableTransitionStatuses as $ts)
                                            <option value="{{ $ts['id'] }}">{{ $ts['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('formData.transition_to_status_id')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Warehouse --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Monitorowany magazyn</label>
                                    <select wire:model="formData.depletion_warehouse_id"
                                            class="form-input-dark w-full">
                                        <option value="">Domyslny magazyn</option>
                                        @foreach($this->warehouses as $wh)
                                            <option value="{{ $wh['id'] }}">
                                                {{ $wh['name'] }}
                                                @if($wh['is_default']) (domyslny) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Pozostaw puste aby uzyc magazynu domyslnego.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button type="submit" class="btn-enterprise-primary">
                            {{ $editingId ? 'Zapisz zmiany' : 'Utworz status' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        <div class="modal-overlay show" wire:click.self="closeDeleteModal">
            <div class="modal sm:max-w-md w-full mx-4" @click.stop>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-white">Usun status</h3>
                            <p class="text-sm text-gray-400">Tej operacji nie mozna cofnac</p>
                        </div>
                    </div>

                    <div class="bg-gray-700/30 rounded-lg p-4 border border-gray-600 mb-4">
                        <p class="text-sm text-gray-300">
                            Czy na pewno chcesz usunac status <strong class="text-white">"{{ $deleteName }}"</strong>?
                        </p>
                        @if($deleteProductsCount > 0)
                            <p class="text-sm text-amber-400 mt-2">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                                </svg>
                                {{ $deleteProductsCount }} {{ $deleteProductsCount === 1 ? 'produkt zostanie przeniesiony' : 'produktow zostanie przeniesionych' }} do statusu domyslnego.
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <button wire:click="closeDeleteModal" class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button wire:click="delete" class="btn-enterprise-danger">
                            Usun status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
