<div class="feature-browser">
    {{-- HEADER --}}
    <div class="feature-browser__header">
        <div class="flex items-center gap-3">
            <span class="text-2xl">&#128218;</span>
            <div>
                <h3 class="text-h3">Biblioteka Cech</h3>
                <p class="text-sm text-gray-400">Zarzadzaj grupami i cechami produktow</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openFeatureGroupModal" class="btn-enterprise-secondary btn-sm">
                + Grupa
            </button>
            <button wire:click="openFeatureTypeModal" class="btn-enterprise-primary btn-sm">
                + Cecha
            </button>
        </div>
    </div>

    {{-- 2-COLUMN LAYOUT --}}
    <div class="feature-library__columns">
        {{-- LEFT COLUMN: Groups --}}
        <div class="feature-browser__column feature-browser__column--groups">
            <div class="feature-browser__column-header">
                <span class="font-medium">GRUPY CECH</span>
                <span class="text-xs text-gray-400">{{ $this->groups->count() }}</span>
            </div>
            <div class="feature-browser__column-content">
                @foreach($this->groups as $group)
                    <div wire:key="group-{{ $group['id'] }}"
                         class="feature-library__group-item {{ $selectedGroupId === $group['id'] ? 'active' : '' }}">
                        {{-- Group Button --}}
                        <button wire:click="selectGroup({{ $group['id'] }})"
                                class="flex-1 flex items-center gap-2 text-left">
                            @if($group['icon'])
                                <span class="text-sm {{ $group['colorClasses'] ?? '' }}">
                                    @switch($group['icon'])
                                        @case('engine') &#9881; @break
                                        @case('ruler') &#128207; @break
                                        @case('wheel') &#9899; @break
                                        @case('brake') &#128376; @break
                                        @case('suspension') &#8597; @break
                                        @case('electric') &#9889; @break
                                        @case('fuel') &#9981; @break
                                        @case('document') &#128196; @break
                                        @case('car') &#128663; @break
                                        @case('gear') &#9881; @break
                                        @case('info') &#8505; @break
                                        @default &#128204;
                                    @endswitch
                                </span>
                            @endif
                            <span class="truncate flex-1">{{ $group['name'] }}</span>
                        </button>

                        {{-- Group Actions --}}
                        <div class="flex items-center gap-1">
                            @if($group['vehicle_filter'])
                                <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-500/20 text-yellow-400" title="Warunkowa: {{ $group['vehicle_filter'] }}">
                                    @if($group['vehicle_filter'] === 'elektryczne')
                                        ⚡
                                    @else
                                        ⛽
                                    @endif
                                </span>
                            @endif
                            <span class="feature-browser__badge {{ $group['used_features_count'] > 0 ? 'feature-browser__badge--active' : '' }}">
                                {{ $group['features_count'] }}
                            </span>
                            <button wire:click="editFeatureGroup({{ $group['id'] }})"
                                    class="p-1 text-blue-400 hover:text-blue-300 opacity-0 group-hover:opacity-100 transition-opacity"
                                    title="Edytuj grupe">
                                &#9998;
                            </button>
                            <button wire:click="deleteFeatureGroup({{ $group['id'] }})"
                                    wire:confirm="Usunac grupe {{ $group['name'] }}?"
                                    class="p-1 text-red-400 hover:text-red-300 opacity-0 group-hover:opacity-100 transition-opacity"
                                    title="Usun grupe">
                                &#128465;
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="feature-browser__column-footer">
                {{ $this->groups->sum('features_count') }} cech lacznie
            </div>
        </div>

        {{-- RIGHT COLUMN: Features of selected group --}}
        <div class="feature-browser__column feature-browser__column--features" style="flex: 2;">
            @if($selectedGroupId && $this->selectedGroup)
                {{-- Features Header --}}
                <div class="feature-browser__column-header">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">CECHY GRUPY:</span>
                        <span class="text-gray-300">{{ $this->selectedGroup['name'] }}</span>
                    </div>
                    <button wire:click="openFeatureTypeModal({{ $selectedGroupId }})"
                            class="text-xs text-green-400 hover:text-green-300">
                        + Dodaj ceche
                    </button>
                </div>

                {{-- Search --}}
                <div class="p-2 border-b border-gray-700">
                    <input type="text"
                           wire:model.live.debounce.300ms="searchQuery"
                           class="form-input form-input-sm w-full"
                           placeholder="Szukaj cechy...">
                </div>

                {{-- Features List --}}
                <div class="feature-browser__column-content">
                    @forelse($this->featureTypes as $feature)
                        <div wire:key="feature-{{ $feature['id'] }}"
                             class="feature-library__feature-item group">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="flex flex-col flex-1 min-w-0">
                                    <span class="font-medium truncate">{{ $feature['name'] }}</span>
                                    <span class="text-xs text-gray-500 truncate">{{ $feature['code'] }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- Unit badge --}}
                                @if($feature['unit'])
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-gray-700 text-gray-400">
                                        {{ $feature['unit'] }}
                                    </span>
                                @endif

                                {{-- Type badge --}}
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300">
                                    {{ $feature['value_type'] }}
                                </span>

                                {{-- Products count badge --}}
                                <span class="feature-browser__badge feature-browser__badge--small {{ $feature['products_count'] > 0 ? 'feature-browser__badge--active' : 'feature-browser__badge--zero' }}">
                                    {{ $feature['products_count'] }} prod.
                                </span>

                                {{-- Conditional badge --}}
                                @if($feature['conditional'])
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-500/20 text-yellow-400" title="Warunkowa: {{ $feature['conditional'] }}">
                                        @if($feature['conditional'] === 'elektryczne')
                                            ⚡
                                        @else
                                            ⛽
                                        @endif
                                    </span>
                                @endif

                                {{-- Actions --}}
                                <button wire:click="editFeatureType({{ $feature['id'] }})"
                                        class="p-1 text-blue-400 hover:text-blue-300 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Edytuj ceche">
                                    &#9998;
                                </button>
                                <button wire:click="deleteFeatureType({{ $feature['id'] }})"
                                        wire:confirm="Usunac ceche {{ $feature['name'] }}?"
                                        class="p-1 text-red-400 hover:text-red-300 opacity-0 group-hover:opacity-100 transition-opacity"
                                        title="Usun ceche">
                                    &#128465;
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="feature-browser__empty-state">
                            <span class="text-4xl mb-2">&#128196;</span>
                            <p>{{ $searchQuery ? 'Brak wynikow dla "' . $searchQuery . '"' : 'Brak cech w tej grupie' }}</p>
                            <button wire:click="openFeatureTypeModal({{ $selectedGroupId }})"
                                    class="mt-3 btn-enterprise-secondary btn-sm">
                                + Dodaj pierwsza ceche
                            </button>
                        </div>
                    @endforelse
                </div>

                {{-- Footer stats --}}
                <div class="feature-browser__column-footer">
                    {{ $this->featureTypes->count() }} cech |
                    {{ $this->featureTypes->where('products_count', '>', 0)->count() }} uzywanych
                </div>
            @else
                <div class="feature-browser__empty-state">
                    <span class="text-4xl mb-2">&#128072;</span>
                    <p>Wybierz grupe z lewej kolumny</p>
                </div>
            @endif
        </div>
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
