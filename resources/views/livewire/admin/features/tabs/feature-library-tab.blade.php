<div class="feature-browser">
    {{-- HEADER --}}
    <div class="feature-browser__header">
        <div class="flex items-center gap-3">
            <span class="text-2xl">&#128218;</span>
            <div>
                <h3 class="text-h3">Biblioteka Cech</h3>
                <p class="text-sm text-gray-400">
                    {{ $this->groups->count() }} grup | {{ $this->groups->sum('features_count') }} cech
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="expandAll" class="btn-enterprise-ghost btn-sm">Rozwin wszystko</button>
            <button wire:click="collapseAll" class="btn-enterprise-ghost btn-sm">Zwin wszystko</button>
            <button wire:click="openFeatureGroupModal" class="btn-enterprise-secondary btn-sm">+ Grupa</button>
            <button wire:click="openFeatureTypeModal" class="btn-enterprise-primary btn-sm">+ Cecha</button>
        </div>
    </div>

    {{-- EXPANDABLE TREE --}}
    <div class="feature-tree">
        @foreach($this->groups as $group)
            <div wire:key="group-{{ $group['id'] }}" class="feature-tree__group">
                {{-- Group Header (clickable) --}}
                <div class="feature-tree__group-header" wire:click="toggleGroup({{ $group['id'] }})">
                    <span class="feature-tree__expand-icon {{ $this->isGroupExpanded($group['id']) ? 'expanded' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                    <span class="feature-tree__group-icon">
                        @switch($group['icon'])
                            @case('engine') &#9881; @break
                            @case('electric') &#9889; @break
                            @case('fuel') &#9981; @break
                            @case('ruler') &#128207; @break
                            @case('wheel') &#9899; @break
                            @case('brake') &#128376; @break
                            @case('suspension') &#8597; @break
                            @case('document') &#128196; @break
                            @case('car') &#128663; @break
                            @case('gear') &#9881; @break
                            @case('info') &#8505; @break
                            @default &#128193;
                        @endswitch
                    </span>
                    <span class="feature-tree__group-name">{{ $group['name'] }}</span>
                    @if($group['vehicle_filter'])
                        <span class="feature-tree__badge feature-tree__badge--warning">
                            {{ $group['vehicle_filter'] === 'elektryczne' ? '⚡' : '⛽' }}
                        </span>
                    @endif
                    <span class="feature-tree__badge">{{ $group['features_count'] }}</span>

                    {{-- Group Actions (stop propagation) --}}
                    <div class="feature-tree__actions" wire:click.stop>
                        <button wire:click="editFeatureGroup({{ $group['id'] }})" class="feature-tree__action-btn" title="Edytuj">&#9998;</button>
                        <button wire:click="deleteFeatureGroup({{ $group['id'] }})" wire:confirm="Usunac grupe {{ $group['name'] }}?" class="feature-tree__action-btn feature-tree__action-btn--danger" title="Usun">&#128465;</button>
                    </div>
                </div>

                {{-- Features List (collapsible) --}}
                @if($this->isGroupExpanded($group['id']))
                    <div class="feature-tree__features">
                        @forelse($group['features'] as $feature)
                            <div wire:key="feature-{{ $feature['id'] }}" class="feature-tree__feature-item">
                                <span class="feature-tree__feature-name">{{ $feature['name'] }}</span>
                                <span class="feature-tree__feature-code text-gray-500">{{ $feature['code'] }}</span>
                                @if($feature['unit'])
                                    <span class="feature-tree__badge feature-tree__badge--small">{{ $feature['unit'] }}</span>
                                @endif
                                <span class="feature-tree__badge feature-tree__badge--small">{{ $feature['value_type'] }}</span>
                                <span class="feature-tree__badge feature-tree__badge--small {{ $feature['products_count'] > 0 ? 'feature-tree__badge--active' : '' }}">
                                    {{ $feature['products_count'] }} prod.
                                </span>
                                <div class="feature-tree__actions">
                                    <button wire:click="editFeatureType({{ $feature['id'] }})" class="feature-tree__action-btn" title="Edytuj">&#9998;</button>
                                    <button wire:click="deleteFeatureType({{ $feature['id'] }})" wire:confirm="Usunac ceche {{ $feature['name'] }}?" class="feature-tree__action-btn feature-tree__action-btn--danger" title="Usun">&#128465;</button>
                                </div>
                            </div>
                        @empty
                            <div class="feature-tree__empty">Brak cech w grupie</div>
                        @endforelse
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- FEATURE TYPE MODAL --}}
    @if($showFeatureTypeModal)
    <div class="modal-overlay show" wire:click.self="closeFeatureTypeModal">
        <div class="modal-content max-w-xl">
            <div class="modal-header">
                <h3 class="text-h3">
                    {{ $editingFeatureTypeId ? 'Edytuj' : 'Nowa' }} Cecha
                </h3>
                <button wire:click="closeFeatureTypeModal" class="modal-close">&#10005;</button>
            </div>

            <div class="space-y-4 p-4">
                {{-- Name --}}
                <div>
                    <label class="form-label">Nazwa cechy *</label>
                    <input type="text"
                           wire:model="featureTypeName"
                           class="form-input"
                           placeholder="np. Moc silnika">
                    @error('featureTypeName')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Code --}}
                <div>
                    <label class="form-label">Kod (unikatowy) *</label>
                    <input type="text"
                           wire:model="featureTypeCode"
                           class="form-input"
                           placeholder="np. engine_power">
                    @error('featureTypeCode')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- 2-column layout for type and unit --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Typ wartosci *</label>
                        <select wire:model="featureTypeValueType" class="form-input">
                            <option value="text">Tekst</option>
                            <option value="number">Liczba</option>
                            <option value="bool">Tak/Nie</option>
                            <option value="select">Lista wyboru</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Jednostka</label>
                        <input type="text"
                               wire:model="featureTypeUnit"
                               class="form-input"
                               placeholder="np. W, kg, cm">
                    </div>
                </div>

                {{-- Group --}}
                <div>
                    <label class="form-label">Grupa</label>
                    <select wire:model="featureTypeGroupId" class="form-input">
                        <option value="">-- Bez grupy --</option>
                        @foreach($this->allGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->getDisplayName() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Placeholder --}}
                <div>
                    <label class="form-label">Placeholder (podpowiedz)</label>
                    <input type="text"
                           wire:model="featureTypePlaceholder"
                           class="form-input"
                           placeholder="np. Wprowadz wartosc...">
                </div>

                {{-- Conditional --}}
                <div>
                    <label class="form-label">Warunkowa (typ pojazdu)</label>
                    <select wire:model="featureTypeConditional" class="form-input">
                        <option value="">-- Dla wszystkich --</option>
                        <option value="elektryczne">Tylko elektryczne</option>
                        <option value="spalinowe">Tylko spalinowe</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button wire:click="saveFeatureType"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="saveFeatureType">&#128190; Zapisz</span>
                    <span wire:loading wire:target="saveFeatureType">Zapisywanie...</span>
                </button>
                <button wire:click="closeFeatureTypeModal" class="btn-enterprise-secondary">
                    Anuluj
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- FEATURE GROUP MODAL --}}
    @if($showFeatureGroupModal)
    <div class="modal-overlay show" wire:click.self="closeFeatureGroupModal">
        <div class="modal-content max-w-xl">
            <div class="modal-header">
                <h3 class="text-h3">
                    {{ $editingFeatureGroupId ? 'Edytuj' : 'Nowa' }} Grupa
                </h3>
                <button wire:click="closeFeatureGroupModal" class="modal-close">&#10005;</button>
            </div>

            <div class="space-y-4 p-4">
                {{-- Name --}}
                <div>
                    <label class="form-label">Nazwa grupy *</label>
                    <input type="text"
                           wire:model="featureGroupName"
                           class="form-input"
                           placeholder="np. Silnik">
                    @error('featureGroupName')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Code --}}
                <div>
                    <label class="form-label">Kod (unikatowy) *</label>
                    <input type="text"
                           wire:model="featureGroupCode"
                           class="form-input"
                           placeholder="np. engine">
                    @error('featureGroupCode')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- 2-column: Icon + Color --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Ikona</label>
                        <select wire:model="featureGroupIcon" class="form-input">
                            <option value="">-- Brak --</option>
                            <option value="engine">&#9881; Silnik</option>
                            <option value="ruler">&#128207; Linijka</option>
                            <option value="wheel">&#9899; Kolo</option>
                            <option value="brake">&#128376; Hamulec</option>
                            <option value="suspension">&#8597; Zawieszenie</option>
                            <option value="electric">&#9889; Elektryczny</option>
                            <option value="fuel">&#9981; Paliwo</option>
                            <option value="document">&#128196; Dokument</option>
                            <option value="car">&#128663; Samochod</option>
                            <option value="gear">&#9881; Zebatka</option>
                            <option value="info">&#8505; Info</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Kolor</label>
                        <select wire:model="featureGroupColor" class="form-input">
                            <option value="">-- Domyslny --</option>
                            <option value="orange">Pomaranczowy</option>
                            <option value="blue">Niebieski</option>
                            <option value="green">Zielony</option>
                            <option value="yellow">Zolty</option>
                            <option value="red">Czerwony</option>
                            <option value="purple">Fioletowy</option>
                            <option value="cyan">Turkusowy</option>
                            <option value="gray">Szary</option>
                        </select>
                    </div>
                </div>

                {{-- Sort Order --}}
                <div>
                    <label class="form-label">Kolejnosc *</label>
                    <input type="number"
                           wire:model="featureGroupSortOrder"
                           class="form-input"
                           min="0"
                           placeholder="0">
                    @error('featureGroupSortOrder')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Vehicle Type Filter --}}
                <div>
                    <label class="form-label">Filtr typu pojazdu</label>
                    <select wire:model="featureGroupVehicleFilter" class="form-input">
                        <option value="">-- Dla wszystkich --</option>
                        <option value="elektryczne">Tylko elektryczne</option>
                        <option value="spalinowe">Tylko spalinowe</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button wire:click="saveFeatureGroup"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="saveFeatureGroup">&#128190; Zapisz</span>
                    <span wire:loading wire:target="saveFeatureGroup">Zapisywanie...</span>
                </button>
                <button wire:click="closeFeatureGroupModal" class="btn-enterprise-secondary">
                    Anuluj
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
