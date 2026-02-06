<div class="feature-browser">
    {{-- HEADER --}}
    <div class="feature-browser__header">
        <div class="flex items-center gap-4">
            <div class="feature-browser__header-icon">
                <span class="text-2xl">🔍</span>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-white">Przeglądarka Cech</h3>
                <p class="text-sm text-gray-400">Eksploruj cechy i znajdź produkty</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-800/50 border border-gray-700">
                <span class="text-xs text-gray-400">Grupy:</span>
                <span class="text-sm font-semibold text-white">{{ $this->groups->count() }}</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-800/50 border border-gray-700">
                <span class="text-xs text-gray-400">Cechy:</span>
                <span class="text-sm font-semibold text-white">{{ $this->groups->sum('features_count') }}</span>
            </div>
        </div>
    </div>

    {{-- 3-COLUMN LAYOUT --}}
    <div class="feature-browser__columns">
        {{-- LEFT COLUMN: Groups --}}
        <div class="feature-browser__column feature-browser__column--groups">
            <div class="feature-browser__column-header">
                <span class="flex items-center gap-2">
                    <span class="header-icon">📁</span>
                    GRUPY CECH
                </span>
            </div>
            <div class="feature-browser__column-content">
                @foreach($this->groups as $group)
                    <button wire:key="group-{{ $group['id'] }}"
                            wire:click="selectGroup({{ $group['id'] }})"
                            class="feature-browser__group-item {{ $selectedGroupId === $group['id'] ? 'active' : '' }}">
                        <div class="feature-browser__group-icon">
                            @switch($group['icon'] ?? '')
                                @case('engine') ⚙️ @break
                                @case('ruler') 📏 @break
                                @case('wheel') ⚫ @break
                                @case('brake') 🛞 @break
                                @case('suspension') ↕️ @break
                                @case('electric') ⚡ @break
                                @case('fuel') ⛽ @break
                                @case('document') 📄 @break
                                @case('car') 🚗 @break
                                @default 📌
                            @endswitch
                        </div>
                        <span class="flex-1 truncate font-medium">{{ $group['name'] }}</span>
                        <span class="feature-browser__badge {{ $group['features_count'] > 10 ? 'feature-browser__badge--highlight' : '' }}">
                            {{ $group['features_count'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- MIDDLE COLUMN: Feature Types & Values --}}
        <div class="feature-browser__column feature-browser__column--features">
            @if($selectedGroupId)
                {{-- Feature Types Header --}}
                <div class="feature-browser__column-header">
                    <span class="flex items-center gap-2">
                        <span class="header-icon">{{ $selectedFeatureTypeId ? '📋' : '🏷️' }}</span>
                        @if($selectedFeatureTypeId && $this->selectedFeatureType)
                            {{ $this->selectedFeatureType['name'] }}
                            @if($this->selectedFeatureType['unit'])
                                <span class="text-gray-400 font-normal">({{ $this->selectedFeatureType['unit'] }})</span>
                            @endif
                        @else
                            CECHY GRUPY
                        @endif
                    </span>
                    @if($selectedFeatureTypeId)
                        <button wire:click="$set('selectedFeatureTypeId', null)"
                                class="flex items-center gap-1 px-2 py-1 rounded text-xs text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors">
                            <span>←</span> Wstecz
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
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="truncate font-medium">{{ $featureType['name'] }}</span>
                                    @if($featureType['unit'])
                                        <span class="text-xs text-gray-500 flex-shrink-0">({{ $featureType['unit'] }})</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @php
                                        $typeClass = match($featureType['value_type']) {
                                            'text' => 'feature-browser__type-badge--text',
                                            'number' => 'feature-browser__type-badge--number',
                                            'select' => 'feature-browser__type-badge--select',
                                            'boolean' => 'feature-browser__type-badge--boolean',
                                            default => ''
                                        };
                                    @endphp
                                    <span class="feature-browser__type-badge {{ $typeClass }}">
                                        {{ $featureType['value_type'] }}
                                    </span>
                                    <span class="feature-browser__badge feature-browser__badge--small {{ $featureType['products_count'] > 0 ? 'feature-browser__badge--active' : 'feature-browser__badge--zero' }}">
                                        {{ $featureType['products_count'] }}
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
                    <div class="feature-browser__empty-state-icon">👈</div>
                    <p class="feature-browser__empty-state-text">Wybierz grupę cech</p>
                    <p class="feature-browser__empty-state-hint">Kliknij grupę w lewej kolumnie</p>
                </div>
            @endif
        </div>

        {{-- RIGHT COLUMN: Products --}}
        <div class="feature-browser__column feature-browser__column--products">
            <div class="feature-browser__column-header">
                <span class="flex items-center gap-2">
                    <span class="header-icon">📦</span>
                    PRODUKTY
                </span>
                <span class="feature-browser__badge {{ $this->products->count() > 0 ? 'feature-browser__badge--active' : '' }}">
                    {{ $this->products->count() }}
                </span>
            </div>

            <div class="feature-browser__column-content">
                @if($this->products->isNotEmpty())
                    {{-- Search/Filter --}}
                    <div class="p-2 border-b border-gray-700/50 mb-2">
                        <input type="text"
                               placeholder="🔍 Szukaj SKU lub nazwy..."
                               class="form-input-enterprise w-full text-sm"
                               disabled>
                    </div>

                    @foreach($this->products as $product)
                        <button wire:key="product-{{ $product['id'] }}"
                                wire:click="goToProduct({{ $product['id'] }})"
                                class="feature-browser__product-item">
                            <div class="feature-browser__product-avatar">
                                📦
                            </div>
                            <div class="flex flex-col flex-1 min-w-0">
                                <span class="feature-browser__product-sku truncate">
                                    {{ $product['sku'] }}
                                </span>
                                <span class="feature-browser__product-name truncate">
                                    {{ $product['name'] }}
                                </span>
                            </div>
                            <div class="feature-browser__product-arrow">→</div>
                        </button>
                    @endforeach
                @elseif(!empty($selectedValueIds) || !empty($selectedCustomValues))
                    <div class="feature-browser__empty-state">
                        <div class="feature-browser__empty-state-icon">📭</div>
                        <p class="feature-browser__empty-state-text">Brak produktów</p>
                        <p class="feature-browser__empty-state-hint">Nie znaleziono produktów z wybraną wartością</p>
                    </div>
                @else
                    <div class="feature-browser__empty-state">
                        <div class="feature-browser__empty-state-icon">☑️</div>
                        <p class="feature-browser__empty-state-text">Wybierz wartości</p>
                        <p class="feature-browser__empty-state-hint">Zaznacz wartości cechy aby zobaczyć produkty</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
