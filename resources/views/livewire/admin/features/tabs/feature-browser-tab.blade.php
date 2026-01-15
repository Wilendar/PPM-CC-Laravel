<div class="feature-browser">
    {{-- HEADER --}}
    <div class="feature-browser__header">
        <div class="flex items-center gap-3">
            <span class="text-2xl">📋</span>
            <div>
                <h3 class="text-h3">Przegladarka Cech</h3>
                <p class="text-sm text-gray-400">Wybierz grupe i cechy aby zobaczyc produkty</p>
            </div>
        </div>
        <div class="text-sm text-gray-400">
            {{ $this->groups->count() }} grup | {{ $this->groups->sum('features_count') }} cech
        </div>
    </div>

    {{-- 3-COLUMN LAYOUT --}}
    <div class="feature-browser__columns">
        {{-- LEFT COLUMN: Groups --}}
        <div class="feature-browser__column feature-browser__column--groups">
            <div class="feature-browser__column-header">
                <span class="font-medium">GRUPY CECH</span>
            </div>
            <div class="feature-browser__column-content">
                @foreach($this->groups as $group)
                    <button wire:key="group-{{ $group['id'] }}"
                            wire:click="selectGroup({{ $group['id'] }})"
                            class="feature-browser__group-item {{ $selectedGroupId === $group['id'] ? 'active' : '' }}">
                        <div class="flex items-center gap-2 flex-1">
                            @if($group['icon'])
                                <span class="text-sm {{ $group['colorClasses'] ?? '' }}">
                                    @switch($group['icon'])
                                        @case('engine') ⚙ @break
                                        @case('ruler') 📏 @break
                                        @case('wheel') ⚫ @break
                                        @case('brake') 🛞 @break
                                        @case('suspension') ↕ @break
                                        @case('electric') ⚡ @break
                                        @case('fuel') ⛽ @break
                                        @case('document') 📄 @break
                                        @case('car') 🚗 @break
                                        @default 📌
                                    @endswitch
                                </span>
                            @endif
                            <span class="truncate">{{ $group['name'] }}</span>
                        </div>
                        <span class="feature-browser__badge">{{ $group['features_count'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- MIDDLE COLUMN: Feature Types & Values --}}
        <div class="feature-browser__column feature-browser__column--features">
            @if($selectedGroupId)
                {{-- Feature Types Header --}}
                <div class="feature-browser__column-header">
                    <span class="font-medium">
                        @if($selectedFeatureTypeId && $this->selectedFeatureType)
                            {{ $this->selectedFeatureType['name'] }}
                            @if($this->selectedFeatureType['unit'])
                                <span class="text-gray-400">({{ $this->selectedFeatureType['unit'] }})</span>
                            @endif
                        @else
                            CECHY GRUPY
                        @endif
                    </span>
                    @if($selectedFeatureTypeId)
                        <button wire:click="$set('selectedFeatureTypeId', null)" class="text-xs text-gray-400 hover:text-white">
                            ← Wstecz
                        </button>
                    @endif
                </div>

                <div class="feature-browser__column-content">
                    @if(!$selectedFeatureTypeId)
                        {{-- Feature Types List --}}
                        @foreach($this->featureTypes as $featureType)
                            <button wire:key="type-{{ $featureType['id'] }}"
                                    wire:click="selectFeatureType({{ $featureType['id'] }})"
                                    class="feature-browser__feature-item">
                                <div class="flex items-center gap-2 flex-1">
                                    <span class="truncate">{{ $featureType['name'] }}</span>
                                    @if($featureType['unit'])
                                        <span class="text-xs text-gray-500">({{ $featureType['unit'] }})</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300">
                                        {{ $featureType['value_type'] }}
                                    </span>
                                    <span class="feature-browser__badge feature-browser__badge--small {{ $featureType['products_count'] > 0 ? 'feature-browser__badge--active' : 'feature-browser__badge--zero' }}">
                                        {{ $featureType['products_count'] }} prod.
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    @else
                        {{-- Feature Values List (with checkboxes) --}}
                        <div class="feature-browser__values-controls">
                            <button wire:click="selectAllValues" class="text-xs text-blue-400 hover:text-blue-300">
                                Zaznacz wszystkie
                            </button>
                            <button wire:click="deselectAllValues" class="text-xs text-gray-400 hover:text-gray-300">
                                Odznacz
                            </button>
                        </div>

                        {{-- Predefined Values --}}
                        @if($this->featureValues->isNotEmpty())
                            <div class="feature-browser__section-label">
                                <span class="text-xs text-gray-400">Predefiniowane wartosci:</span>
                            </div>
                            @foreach($this->featureValues as $value)
                                <label wire:key="value-{{ $value['id'] }}"
                                       class="feature-browser__value-item {{ in_array($value['id'], $selectedValueIds) ? 'active' : '' }}">
                                    <input type="checkbox"
                                           wire:click="toggleValue({{ $value['id'] }})"
                                           @checked(in_array($value['id'], $selectedValueIds))
                                           class="form-checkbox">
                                    <span class="flex-1 truncate">{{ $value['value'] }}</span>
                                    <span class="feature-browser__badge feature-browser__badge--small {{ $value['products_count'] > 0 ? 'feature-browser__badge--active' : 'feature-browser__badge--zero' }}">
                                        {{ $value['products_count'] }} prod.
                                    </span>
                                </label>
                            @endforeach
                        @endif

                        {{-- Custom Values Section --}}
                        @if($this->customValues->isNotEmpty())
                            <div class="feature-browser__section-label mt-3">
                                <span class="text-xs text-orange-400">Wartosci niestandardowe:</span>
                            </div>
                            @foreach($this->customValues as $customValue)
                                <label wire:key="custom-{{ md5($customValue['value']) }}"
                                       class="feature-browser__value-item feature-browser__value-item--custom {{ in_array($customValue['value'], $selectedCustomValues) ? 'active' : '' }}">
                                    <input type="checkbox"
                                           wire:click="toggleCustomValue('{{ addslashes($customValue['value']) }}')"
                                           @checked(in_array($customValue['value'], $selectedCustomValues))
                                           class="form-checkbox">
                                    <span class="flex-1 truncate">{{ $customValue['value'] }}</span>
                                    <span class="feature-browser__badge feature-browser__badge--small feature-browser__badge--custom {{ $customValue['products_count'] > 0 ? 'feature-browser__badge--active' : '' }}">
                                        {{ $customValue['products_count'] }} prod.
                                    </span>
                                </label>
                            @endforeach
                        @endif

                        {{-- Empty State --}}
                        @if($this->featureValues->isEmpty() && $this->customValues->isEmpty())
                            <div class="text-center text-gray-500 py-4">
                                Brak wartosci dla tej cechy
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Stats Footer --}}
                <div class="feature-browser__column-footer">
                    @if($selectedFeatureTypeId)
                        @php
                            $totalValues = $this->featureValues->count() + $this->customValues->count();
                            $totalSelected = count($selectedValueIds) + count($selectedCustomValues);
                        @endphp
                        {{ $totalValues }} wartosci |
                        {{ $totalSelected }} zaznaczonych
                    @else
                        {{ $this->featureTypes->count() }} cech
                    @endif
                </div>
            @else
                <div class="feature-browser__empty-state">
                    <span class="text-4xl mb-2">👈</span>
                    <p>Wybierz grupe z lewej kolumny</p>
                </div>
            @endif
        </div>

        {{-- RIGHT COLUMN: Products --}}
        <div class="feature-browser__column feature-browser__column--products">
            <div class="feature-browser__column-header">
                <span class="font-medium">PRODUKTY</span>
                <span class="text-sm text-gray-400">{{ $this->products->count() }} produktow</span>
            </div>

            <div class="feature-browser__column-content">
                @if($this->products->isNotEmpty())
                    {{-- Search/Filter --}}
                    <div class="p-2 border-b border-gray-700">
                        <input type="text"
                               placeholder="Szukaj SKU lub nazwy..."
                               class="form-input form-input-sm w-full"
                               disabled>
                    </div>

                    @foreach($this->products as $product)
                        <button wire:key="product-{{ $product['id'] }}"
                                wire:click="goToProduct({{ $product['id'] }})"
                                class="feature-browser__product-item">
                            <div class="flex flex-col flex-1 min-w-0">
                                <span class="font-mono text-sm text-orange-400 truncate">
                                    {{ $product['sku'] }}
                                </span>
                                <span class="text-xs text-gray-400 truncate">
                                    {{ $product['name'] }}
                                </span>
                            </div>
                            <span class="text-gray-500">→</span>
                        </button>
                    @endforeach
                @elseif(!empty($selectedValueIds) || !empty($selectedCustomValues))
                    <div class="feature-browser__empty-state">
                        <span class="text-4xl mb-2">📭</span>
                        <p>Brak produktow z ta wartoscia</p>
                    </div>
                @else
                    <div class="feature-browser__empty-state">
                        <span class="text-4xl mb-2">☑️</span>
                        <p>Zaznacz wartosci aby zobaczyc produkty</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
