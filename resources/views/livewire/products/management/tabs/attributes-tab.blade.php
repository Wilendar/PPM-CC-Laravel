{{-- resources/views/livewire/products/management/tabs/attributes-tab.blade.php --}}
{{-- ETAP_07e FAZA 3 - Feature Editor integrated with ProductForm --}}
<div class="tab-content active space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-white flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            Cechy techniczne produktu
        </h3>

        <div class="flex items-center space-x-3">
            {{-- Feature Count Badge --}}
            @php
                $totalFeatures = count($productFeatures ?? []);
                $filledFeatures = collect($productFeatures ?? [])->filter(fn($f) => !empty($f['value']))->count();
            @endphp
            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-blue-900/30 text-blue-200 border border-blue-700/50">
                {{ $filledFeatures }}/{{ $totalFeatures }} wypelnionych
            </span>

            {{-- Active Shop Indicator --}}
            @if($activeShopId !== null && isset($availableShops))
                @php
                    $currentShop = collect($availableShops)->firstWhere('id', $activeShopId);
                @endphp
                <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-orange-900/30 text-orange-200 border border-orange-700/50">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Edytujesz: {{ $currentShop['name'] ?? 'Nieznany sklep' }}
                </span>
            @endif
        </div>
    </div>

    {{-- Quick Actions Bar --}}
    @if($isEditMode && isset($product) && $product->id)
        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded-lg border border-gray-700 mb-4">
            <div class="flex items-center space-x-3">
                {{-- Apply Template --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="btn-enterprise-secondary text-sm inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                        </svg>
                        Zastosuj szablon
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- Template Dropdown --}}
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute left-0 mt-2 w-64 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-50">
                        <div class="p-2">
                            <div class="text-xs text-gray-400 px-3 py-2 border-b border-gray-700">Wybierz szablon</div>
                            @forelse($this->featureTemplates as $template)
                                <button type="button"
                                        wire:click="applyFeatureTemplate({{ $template->id }})"
                                        @click="open = false"
                                        class="w-full text-left px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 rounded">
                                    <span class="font-medium">{{ $template->name }}</span>
                                    <span class="text-xs text-gray-500 ml-2">({{ $template->getFeaturesCount() }} cech)</span>
                                </button>
                            @empty
                                <div class="px-3 py-2 text-sm text-gray-500">Brak szablonow</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Add Feature Button --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="btn-enterprise-primary text-sm inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Dodaj ceche
                    </button>

                    {{-- Add Feature Dropdown --}}
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute left-0 mt-2 w-80 max-h-96 overflow-y-auto bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-50">
                        <div class="p-2">
                            <div class="text-xs text-gray-400 px-3 py-2 border-b border-gray-700">Wybierz ceche do dodania</div>
                            @foreach($this->featureGroups as $group)
                                @if($group->featureTypes->count() > 0)
                                    <div class="px-3 py-1 text-xs font-semibold text-gray-400 bg-gray-900/50 mt-1">
                                        {{ $group->getDisplayName() }}
                                    </div>
                                    @foreach($group->featureTypes as $featureType)
                                        @php
                                            $alreadyAdded = $this->hasFeature($featureType->id);
                                        @endphp
                                        <button type="button"
                                                wire:click="addProductFeature({{ $featureType->id }})"
                                                @click="open = false"
                                                @if($alreadyAdded) disabled @endif
                                                class="w-full text-left px-3 py-2 text-sm rounded {{ $alreadyAdded ? 'text-gray-600 cursor-not-allowed' : 'text-gray-300 hover:bg-gray-700' }}">
                                            {{ $featureType->name }}
                                            @if($featureType->unit)
                                                <span class="text-xs text-gray-500">({{ $featureType->unit }})</span>
                                            @endif
                                            @if($alreadyAdded)
                                                <span class="text-xs text-green-500 ml-2">✓ dodana</span>
                                            @endif
                                        </button>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Clear All --}}
            <button type="button"
                    wire:click="clearAllProductFeatures"
                    wire:confirm="Czy na pewno chcesz usunac wszystkie cechy?"
                    class="text-sm text-red-400 hover:text-red-300">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Wyczysc wszystkie
            </button>
        </div>
    @endif

    {{-- Feature Groups Accordion --}}
    <div class="space-y-4" x-data="{ openGroups: ['identyfikacja', 'silnik', 'wymiary'] }">
        @php
            $productFeaturesById = collect($productFeatures ?? [])->keyBy('feature_type_id');
        @endphp

        @forelse($this->featureGroups as $group)
            @php
                // Get features for this group that are assigned to this product
                $groupFeatures = $group->featureTypes->filter(function($ft) use ($productFeaturesById) {
                    return $productFeaturesById->has($ft->id);
                });
                $filledInGroup = $groupFeatures->filter(function($ft) use ($productFeaturesById) {
                    $pf = $productFeaturesById->get($ft->id);
                    return $pf && !empty($pf['value']);
                })->count();
            @endphp

            @if($groupFeatures->count() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    {{-- Group Header (Clickable) --}}
                    <button type="button"
                            @click="openGroups.includes('{{ $group->code }}') ? openGroups = openGroups.filter(g => g !== '{{ $group->code }}') : openGroups.push('{{ $group->code }}')"
                            class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center">
                            {{-- Group Icon --}}
                            <span class="w-8 h-8 flex items-center justify-center rounded-lg {{ $group->getColorClasses() }} mr-3">
                                @switch($group->icon)
                                    @case('engine')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        @break
                                    @case('ruler')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                        @break
                                    @case('wheel')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2" /><circle cx="12" cy="12" r="3" stroke-width="2" /></svg>
                                        @break
                                    @case('electric')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        @break
                                    @case('fuel')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" /></svg>
                                        @break
                                    @default
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                @endswitch
                            </span>

                            {{-- Group Name --}}
                            <span class="text-white font-medium">{{ $group->getDisplayName() }}</span>

                            {{-- Feature Count --}}
                            <span class="ml-3 text-xs text-gray-400">
                                ({{ $filledInGroup }}/{{ $groupFeatures->count() }})
                            </span>
                        </div>

                        {{-- Expand/Collapse Icon --}}
                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                             :class="{ 'rotate-180': openGroups.includes('{{ $group->code }}') }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- Group Content (Features) --}}
                    <div x-show="openGroups.includes('{{ $group->code }}')"
                         x-collapse
                         class="border-t border-gray-700">
                        <div class="p-4 space-y-3">
                            @foreach($groupFeatures as $featureType)
                                @php
                                    $productFeature = $productFeaturesById->get($featureType->id);
                                    $featureIndex = collect($productFeatures ?? [])->search(fn($f) => $f['feature_type_id'] == $featureType->id);
                                @endphp

                                <div class="flex items-center space-x-4 group" wire:key="feature-{{ $featureType->id }}">
                                    {{-- Label with Status Indicator --}}
                                    <label class="w-1/3 text-sm text-gray-300 flex items-center">
                                        {{ $featureType->name }}
                                        @if($featureType->isConditional())
                                            <span class="ml-1 text-xs px-1.5 py-0.5 rounded bg-purple-900/30 text-purple-300 border border-purple-700/50">
                                                {{ $featureType->getConditionalGroupLabel() }}
                                            </span>
                                        @endif
                                        {{-- Status Indicator (shop context only) --}}
                                        @if($activeShopId !== null)
                                            @php
                                                $featureStatusIndicator = $this->getFeatureStatusIndicator($featureType->id);
                                            @endphp
                                            @if($featureStatusIndicator['show'])
                                                <span class="ml-2 text-xs px-1.5 py-0.5 rounded {{ $featureStatusIndicator['class'] }}">
                                                    {{ $featureStatusIndicator['text'] }}
                                                </span>
                                            @endif
                                        @endif
                                    </label>

                                    {{-- Input (based on value_type) --}}
                                    <div class="flex-1">
                                        @switch($featureType->value_type)
                                            @case('number')
                                                <div class="relative">
                                                    <input type="text"
                                                           wire:model.live="productFeatures.{{ $featureIndex }}.value"
                                                           placeholder="{{ $featureType->input_placeholder ?? 'Wprowadz wartosc' }}"
                                                           class="{{ $this->getFeatureClasses($featureType->id) }} w-full pr-12">
                                                    <button type="button"
                                                            wire:click="$set('productFeatures.{{ $featureIndex }}.value', 'Nie dotyczy')"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 px-1.5 py-0.5 text-[10px] bg-gray-600/80 hover:bg-gray-500 text-gray-400 hover:text-gray-200 rounded transition-colors"
                                                            title="Ustaw 'Nie dotyczy'">
                                                        N/D
                                                    </button>
                                                </div>
                                                @break

                                            @case('bool')
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox"
                                                           wire:model.live="productFeatures.{{ $featureIndex }}.value"
                                                           class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                                                    <span class="ms-3 text-sm text-gray-300">
                                                        {{ ($productFeature['value'] ?? false) ? 'Tak' : 'Nie' }}
                                                    </span>
                                                </label>
                                                @break

                                            @case('select')
                                                <select wire:model.live="productFeatures.{{ $featureIndex }}.value"
                                                        class="{{ $this->getFeatureClasses($featureType->id) }} w-full">
                                                    <option value="">-- Wybierz --</option>
                                                    @forelse($featureType->featureValues as $fv)
                                                        <option value="{{ $fv->id }}">{{ $fv->display_value ?? $fv->value }}</option>
                                                    @empty
                                                        <option value="" disabled>Brak zdefiniowanych wartosci</option>
                                                    @endforelse
                                                </select>
                                                @break

                                            @default {{-- text --}}
                                                <div class="relative">
                                                    <input type="text"
                                                           wire:model.live="productFeatures.{{ $featureIndex }}.value"
                                                           placeholder="{{ $featureType->input_placeholder ?? 'Wprowadz wartosc' }}"
                                                           class="{{ $this->getFeatureClasses($featureType->id) }} w-full pr-10">
                                                    <button type="button"
                                                            wire:click="$set('productFeatures.{{ $featureIndex }}.value', 'Nie dotyczy')"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 px-1.5 py-0.5 text-[10px] bg-gray-600/80 hover:bg-gray-500 text-gray-400 hover:text-gray-200 rounded transition-colors"
                                                            title="Ustaw 'Nie dotyczy'">
                                                        N/D
                                                    </button>
                                                </div>
                                        @endswitch
                                    </div>

                                    {{-- Assign to Group Button (only for unassigned features) --}}
                                    @if($isEditMode && ($group->code === 'unassigned' || $group->code === 'imported_prestashop'))
                                        <button type="button"
                                                wire:click="openAssignGroupModal({{ $featureType->id }})"
                                                class="opacity-0 group-hover:opacity-100 transition-opacity text-blue-400 hover:text-blue-300 p-1"
                                                title="Przydziel do grupy">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Remove Button (Edit Mode) --}}
                                    @if($isEditMode)
                                        <button type="button"
                                                wire:click="removeProductFeature({{ $featureType->id }})"
                                                class="opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-300 p-1"
                                                title="Usun ceche">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @empty
            {{-- Empty State --}}
            <div class="text-center py-12 bg-gray-800 rounded-lg border border-gray-700">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <h3 class="text-lg font-medium text-white mb-2">Brak przypisanych cech</h3>
                <p class="text-sm text-gray-400 mb-4">
                    Ten produkt nie ma jeszcze zadnych cech technicznych.
                </p>
                @if($isEditMode)
                    <p class="text-sm text-gray-500">
                        Uzyj przycisku "Dodaj ceche" lub "Zastosuj szablon" powyzej.
                    </p>
                @endif
            </div>
        @endforelse

        {{-- No Features Assigned Yet --}}
        @if(empty($productFeatures) || count($productFeatures) === 0)
            <div class="text-center py-12 bg-gray-800 rounded-lg border border-gray-700">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <h3 class="text-lg font-medium text-white mb-2">Brak przypisanych cech</h3>
                <p class="text-sm text-gray-400 mb-4">
                    Ten produkt nie ma jeszcze zadnych cech technicznych.
                </p>
                @if($isEditMode)
                    <p class="text-sm text-gray-500">
                        Uzyj przycisku "Dodaj ceche" lub "Zastosuj szablon" powyzej.
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- PrestaShop Sync Info --}}
    @if($activeShopId && isset($product) && $product->id)
        <div class="mt-6 p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-blue-300">Synchronizacja z PrestaShop</h4>
                    <p class="text-xs text-blue-400/70 mt-1">
                        Cechy produktu zostana zsynchronizowane z PrestaShop podczas zapisywania produktu.
                        Upewnij sie, ze mapowanie cech jest skonfigurowane w panelu Administracja → Cechy.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Assign to Group Modal - Teleported to body for proper z-index --}}
    <div x-data="{
            open: false,
            newGroupName: '',
            activeTab: 'groups'
         }"
         x-on:open-assign-group-modal.window="open = true; activeTab = 'groups'; newGroupName = ''"
         x-on:close-assign-group-modal.window="open = false">
        <template x-teleport="body">
            <div x-show="open"
                 x-cloak
                 class="fixed inset-0 z-[9999] overflow-y-auto"
                 aria-labelledby="modal-title"
                 role="dialog"
                 aria-modal="true">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/70 transition-opacity" @click="open = false"></div>

                {{-- Modal Content --}}
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-lg bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
                 x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop>
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            Przydziel ceche do grupy
                        </h3>
                        <button @click="open = false" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    @if($this->assignGroupFeatureType)
                        <p class="mt-1 text-sm text-gray-400">
                            Cecha: <span class="text-orange-400 font-medium">{{ $this->assignGroupFeatureType->name }}</span>
                        </p>
                    @endif
                </div>

                {{-- Tab Navigation --}}
                <div class="px-6 pt-4">
                    <div class="flex space-x-1 bg-gray-900/50 rounded-lg p-1">
                        <button @click="activeTab = 'groups'"
                                :class="activeTab === 'groups' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'"
                                class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors">
                            Istniejace grupy
                        </button>
                        <button @click="activeTab = 'new'"
                                :class="activeTab === 'new' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'"
                                class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors">
                            Nowa grupa
                        </button>
                        <button @click="activeTab = 'templates'"
                                :class="activeTab === 'templates' ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white'"
                                class="flex-1 px-3 py-2 text-sm font-medium rounded-md transition-colors">
                            Szablony
                        </button>
                    </div>
                </div>

                {{-- Tab Content --}}
                <div class="px-6 py-4">
                    {{-- Existing Groups Tab --}}
                    <div x-show="activeTab === 'groups'" class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($this->featureGroups as $availableGroup)
                            @if($availableGroup->code !== 'unassigned' && $availableGroup->code !== 'imported_prestashop')
                                <button type="button"
                                        wire:click="assignFeatureToGroup({{ $availableGroup->id }})"
                                        class="w-full flex items-center p-3 rounded-lg bg-gray-700/50 hover:bg-gray-700 text-left transition-colors group">
                                    <span class="w-8 h-8 flex items-center justify-center rounded-lg {{ $availableGroup->getColorClasses() }} mr-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </span>
                                    <div class="flex-1">
                                        <div class="text-white font-medium">{{ $availableGroup->getDisplayName() }}</div>
                                        <div class="text-xs text-gray-400">{{ $availableGroup->featureTypes->count() }} cech</div>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-500 group-hover:text-green-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            @endif
                        @endforeach
                    </div>

                    {{-- New Group Tab --}}
                    <div x-show="activeTab === 'new'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Nazwa nowej grupy</label>
                            <input type="text"
                                   x-model="newGroupName"
                                   placeholder="np. Akcesoria, Czesci zamienne..."
                                   class="form-input-dark w-full">
                        </div>
                        <button type="button"
                                @click="$wire.createGroupAndAssign(newGroupName)"
                                :disabled="!newGroupName.trim()"
                                class="w-full btn-enterprise-primary disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Utworz grupe i przypisz
                        </button>
                    </div>

                    {{-- Templates Tab --}}
                    <div x-show="activeTab === 'templates'" class="space-y-2 max-h-64 overflow-y-auto">
                        <p class="text-xs text-gray-400 mb-3">Zarzadzaj przypisaniem cechy do szablonow</p>
                        @forelse($this->featureTemplates as $template)
                            @php
                                $isInTemplate = $this->assignGroupFeatureType
                                    ? collect($template->features ?? [])->contains('feature_type_id', $this->assignGroupFeatureType->id)
                                    : false;
                            @endphp
                            <div class="flex items-center p-3 rounded-lg {{ $isInTemplate ? 'bg-green-900/30 border border-green-700/50' : 'bg-gray-700/50' }} transition-colors group">
                                <span class="w-8 h-8 flex items-center justify-center rounded-lg {{ $isInTemplate ? 'bg-green-500/20 text-green-400' : 'bg-purple-500/20 text-purple-400' }} mr-3">
                                    @if($isInTemplate)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
                                        </svg>
                                    @endif
                                </span>
                                <div class="flex-1">
                                    <div class="text-white font-medium">{{ $template->name }}</div>
                                    <div class="text-xs {{ $isInTemplate ? 'text-green-400' : 'text-gray-400' }}">
                                        {{ $template->getFeaturesCount() }} cech
                                        @if($isInTemplate)
                                            <span class="ml-1">(zawiera te ceche)</span>
                                        @endif
                                    </div>
                                </div>
                                @if($isInTemplate)
                                    <button type="button"
                                            wire:click="removeFeatureFromTemplate({{ $template->id }})"
                                            class="px-3 py-1.5 text-xs bg-red-600/20 hover:bg-red-600/40 text-red-400 rounded-lg transition-colors"
                                            title="Usun z szablonu">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @else
                                    <button type="button"
                                            wire:click="addFeatureToTemplate({{ $template->id }})"
                                            class="px-3 py-1.5 text-xs bg-purple-600/20 hover:bg-purple-600/40 text-purple-400 rounded-lg transition-colors"
                                            title="Dodaj do szablonu">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-6 text-gray-500">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
                                </svg>
                                Brak szablonow
                            </div>
                        @endforelse
                    </div>
                </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                        <button type="button" @click="open = false" class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </template>
    </div>
</div>
