<div class="space-y-6">
    {{-- Shop Selector --}}
    <div class="enterprise-card p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white">Mapowanie kategorii na typy produktow</h3>
                <p class="text-sm text-gray-400 mt-1">Przypisz kategorie PrestaShop do typow produktow PPM per sklep</p>
            </div>
            @if($selectedShopId)
                <button wire:click="openCreateModal" class="btn-enterprise-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Dodaj mapowanie
                </button>
            @endif
        </div>

        <div class="mt-4 max-w-sm">
            <label class="block text-sm font-medium text-gray-300 mb-2">Sklep PrestaShop</label>
            <select wire:model.live="selectedShopId" class="form-input-enterprise w-full">
                <option value="">-- Wybierz sklep --</option>
                @foreach($availableShops as $shop)
                    <option value="{{ $shop['id'] }}">{{ $shop['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Content area --}}
    @if(!$selectedShopId)
        {{-- Empty state: no shop selected --}}
        <div class="enterprise-card p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-300 mb-2">Wybierz sklep</h3>
            <p class="text-sm text-gray-500">Wybierz sklep PrestaShop aby zobaczyc i edytowac mapowania kategorii na typy produktow.</p>
        </div>
    @elseif(empty($categoryTree))
        {{-- Empty state: no mapped categories --}}
        <div class="enterprise-card p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-300 mb-2">Brak zmapowanych kategorii</h3>
            <p class="text-sm text-gray-500">Ten sklep nie ma jeszcze zmapowanych kategorii PPM. Najpierw dodaj mapowania kategorii w panelu sklepow.</p>
        </div>
    @else
        {{-- Mappings table --}}
        <div class="enterprise-card overflow-hidden">
            @if(empty($mappings))
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-300 mb-2">Brak mapowan</h3>
                    <p class="text-sm text-gray-500 mb-4">Kliknij "Dodaj mapowanie" aby przypisac kategorie do typow produktow.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700 bg-gray-800/50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kategoria</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Typ produktu</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Dzieci</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Priorytet</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Aktywna</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            @foreach($mappings as $mapping)
                                <tr wire:key="mapping-{{ $mapping['id'] }}" class="hover:bg-gray-800/30 transition-colors">
                                    {{-- Category --}}
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-white font-medium">{{ $mapping['category_name'] }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">PS #{{ $mapping['category_id'] }}</div>
                                    </td>

                                    {{-- Product Type Badge --}}
                                    <td class="px-6 py-4">
                                        <x-product-type-badge :name="$mapping['product_type_name']" :color="$mapping['product_type_color']" size="md" />
                                    </td>

                                    {{-- Include Children --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($mapping['include_children'])
                                            <svg class="w-5 h-5 text-green-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-gray-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        @endif
                                    </td>

                                    {{-- Priority --}}
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm text-gray-300 font-mono">{{ $mapping['priority'] }}</span>
                                    </td>

                                    {{-- Active Toggle --}}
                                    <td class="px-6 py-4 text-center">
                                        <button wire:click="toggleActive({{ $mapping['id'] }})"
                                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                                                       {{ $mapping['is_active'] ? 'bg-green-500' : 'bg-gray-600' }}"
                                                title="{{ $mapping['is_active'] ? 'Aktywna - kliknij aby wylaczyc' : 'Nieaktywna - kliknij aby wlaczyc' }}">
                                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 ease-in-out
                                                         {{ $mapping['is_active'] ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                        </button>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button wire:click="openEditModal({{ $mapping['id'] }})"
                                                    class="p-1.5 text-gray-400 hover:text-blue-400 transition-colors rounded"
                                                    title="Edytuj">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button wire:click="deleteMapping({{ $mapping['id'] }})"
                                                    wire:confirm="Na pewno chcesz usunac to mapowanie?"
                                                    class="p-1.5 text-gray-400 hover:text-red-400 transition-colors rounded"
                                                    title="Usun">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Summary --}}
                <div class="px-6 py-3 bg-gray-800/30 border-t border-gray-700 flex items-center justify-between">
                    <span class="text-sm text-gray-400">{{ count($mappings) }} {{ count($mappings) === 1 ? 'mapowanie' : 'mapowan' }}</span>
                    <span class="text-xs text-gray-500">Sortowane wg priorytetu (nizszy = wyzszy priorytet)</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($showMappingModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/60" wire:click="closeModal"></div>

            {{-- Modal content --}}
            <div class="relative bg-gray-800 rounded-xl border border-gray-700 shadow-2xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-white">
                        {{ $editingMappingId ? 'Edytuj mapowanie' : 'Nowe mapowanie' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-5">
                    {{-- Category tree picker --}}
                    <div x-data="{ search: @entangle('categorySearch') }">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kategoria</label>

                        {{-- Selected category display --}}
                        @if($selectedCategoryName)
                            <div class="flex items-center gap-2 mb-2 px-3 py-2 bg-orange-500/10 border border-orange-500/30 rounded-lg">
                                <svg class="w-4 h-4 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-sm text-orange-300 font-medium truncate">{{ $selectedCategoryName }}</span>
                            </div>
                        @endif

                        {{-- Search input --}}
                        <div class="relative mb-2">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   x-model="search"
                                   class="form-input-enterprise w-full pl-10"
                                   placeholder="Szukaj kategorii...">
                            <button x-show="search.length > 0"
                                    x-cloak
                                    @click="search = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Category tree --}}
                        <div class="max-h-64 overflow-y-auto rounded-lg border border-gray-600 bg-gray-900/50 p-1.5">
                            @if(!empty($categoryTree))
                                @foreach($categoryTree as $node)
                                    @include('livewire.admin.parameters.partials.category-type-tree-node', [
                                        'node' => $node,
                                        'depth' => 0,
                                        'selectedId' => $formCategoryId,
                                        'search' => $categorySearch,
                                    ])
                                @endforeach
                            @else
                                <div class="py-4 text-center text-sm text-gray-500">
                                    Brak kategorii do wyswietlenia
                                </div>
                            @endif
                        </div>

                        @error('formCategoryId')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Product type select --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Typ produktu</label>
                        <select wire:model="formProductTypeId" class="form-input-enterprise w-full">
                            <option value="">-- Wybierz typ --</option>
                            @foreach($availableTypes as $type)
                                <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                            @endforeach
                        </select>
                        @error('formProductTypeId')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Priority --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Priorytet</label>
                        <input type="number" wire:model="formPriority" min="0" max="999"
                               class="form-input-enterprise w-full" placeholder="50">
                        <p class="mt-1 text-xs text-gray-500">Nizszy numer = wyzszy priorytet. Domyslnie: 50</p>
                        @error('formPriority')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Include children checkbox --}}
                    <div class="flex items-start gap-3">
                        <input type="checkbox" wire:model.live="formIncludeChildren" id="formIncludeChildren"
                               class="checkbox-enterprise mt-1">
                        <div>
                            <label for="formIncludeChildren" class="text-sm font-medium text-gray-300 cursor-pointer">
                                Uwzglednij podkategorie
                            </label>
                            <p class="text-xs text-gray-500 mt-0.5">Mapowanie obejmie tez wszystkie podkategorie wybranej kategorii</p>
                        </div>
                    </div>

                    {{-- Preview count --}}
                    @if($previewCount !== null)
                        <div class="p-3 bg-gray-700/50 rounded-lg border border-gray-600">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm text-gray-300">
                                    To mapowanie dotyczy <span class="font-semibold text-white">{{ $previewCount }}</span> {{ $previewCount === 1 ? 'produktu' : 'produktow' }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Modal actions --}}
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button wire:click="closeModal" class="btn-enterprise-secondary">
                        Anuluj
                    </button>
                    <button wire:click="saveMapping" class="btn-enterprise-primary">
                        <span wire:loading.remove wire:target="saveMapping">
                            {{ $editingMappingId ? 'Zapisz zmiany' : 'Dodaj mapowanie' }}
                        </span>
                        <span wire:loading wire:target="saveMapping" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Zapisywanie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
