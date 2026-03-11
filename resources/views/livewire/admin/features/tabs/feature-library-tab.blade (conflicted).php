@php $iconMap = \App\Models\FeatureGroup::getIconMap(); @endphp
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

    {{-- BULK ACTIONS BAR --}}
    @if(count($selectedFeatureTypeIds) > 0)
        <div class="flex items-center justify-between bg-gray-800 border border-gray-600 rounded-lg p-3 mb-4"
             x-data="{ showMoveDropdown: false }">
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-300">
                    Zaznaczono: <strong class="text-white">{{ count($selectedFeatureTypeIds) }}</strong> cech
                </span>
                <label class="flex items-center gap-2 text-xs text-gray-400 hover:text-white cursor-pointer">
                    <input type="checkbox"
                           wire:model.live="selectAllFeatures"
                           class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                    {{ $selectAllFeatures ? 'Odznacz wszystko' : 'Zaznacz wszystkie widoczne' }}
                </label>
            </div>
            <div class="flex items-center gap-2">
                {{-- Move to group --}}
                <div class="relative">
                    <button @click="showMoveDropdown = !showMoveDropdown"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors">
                        Przenies do grupy
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="showMoveDropdown" @click.away="showMoveDropdown = false"
                         x-transition
                         class="absolute right-0 mt-1 w-48 bg-gray-800 border border-gray-700 rounded-lg shadow-lg z-50">
                        @foreach($this->groups as $group)
                            <button wire:click="bulkMoveToGroup({{ $group['id'] }})"
                                    @click="showMoveDropdown = false"
                                    class="w-full text-left px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 first:rounded-t-lg last:rounded-b-lg">
                                {{ $group['name'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
                {{-- Bulk delete --}}
                <button wire:click="bulkDeleteFeatureTypes"
                        wire:confirm="Usunac {{ count($selectedFeatureTypeIds) }} zaznaczonych cech? Produkty zostana odlaczone."
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-400 bg-red-900/30 hover:bg-red-900/50 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Usun zaznaczone
                </button>
            </div>
        </div>
    @endif

    {{-- EXPANDABLE TREE with Drag & Drop --}}
    <div class="feature-tree"
         x-data="{
            draggedFeatureId: null,
            draggedFromGroupId: null,
            dropTargetGroupId: null,
            insertBeforeId: null,
            insertIndicatorEl: null,
            expandTimer: null,

            startDrag(event, featureId, groupId) {
                this.draggedFeatureId = featureId;
                this.draggedFromGroupId = groupId;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(featureId));
                document.body.classList.add('dragging-feature');
            },

            endDrag() {
                this.draggedFeatureId = null;
                this.draggedFromGroupId = null;
                this.dropTargetGroupId = null;
                this.insertBeforeId = null;
                this.clearInsertLine();
                if (this.expandTimer) { clearTimeout(this.expandTimer); this.expandTimer = null; }
                document.body.classList.remove('dragging-feature');
            },

            highlightGroup(event, groupId, isExpanded) {
                if (!this.draggedFeatureId) return;
                event.preventDefault();
                this.dropTargetGroupId = groupId;
                if (!isExpanded && !this.expandTimer) {
                    this.expandTimer = setTimeout(() => {
                        $wire.toggleGroup(groupId);
                        this.expandTimer = null;
                    }, 500);
                }
            },

            unhighlightGroup(groupId) {
                if (this.dropTargetGroupId === groupId) {
                    this.dropTargetGroupId = null;
                }
                this.clearInsertLine();
                this.insertBeforeId = null;
                if (this.expandTimer) { clearTimeout(this.expandTimer); this.expandTimer = null; }
            },

            onFeatureDragOver(event, featureId, groupId) {
                if (!this.draggedFeatureId || this.draggedFeatureId === featureId) return;
                event.preventDefault();
                event.stopPropagation();
                this.dropTargetGroupId = groupId;

                const rect = event.currentTarget.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;
                const isAbove = event.clientY < midY;

                this.clearInsertLine();

                if (isAbove) {
                    this.insertBeforeId = featureId;
                    event.currentTarget.classList.add('feature-tree__feature-item--insert-above');
                } else {
                    const next = event.currentTarget.nextElementSibling;
                    if (next && next.hasAttribute('draggable')) {
                        this.insertBeforeId = parseInt(next.getAttribute('data-feature-id'));
                    } else {
                        this.insertBeforeId = null;
                    }
                    event.currentTarget.classList.add('feature-tree__feature-item--insert-below');
                }
                this.insertIndicatorEl = event.currentTarget;
            },

            clearInsertLine() {
                document.querySelectorAll('.feature-tree__feature-item--insert-above, .feature-tree__feature-item--insert-below').forEach(el => {
                    el.classList.remove('feature-tree__feature-item--insert-above', 'feature-tree__feature-item--insert-below');
                });
                this.insertIndicatorEl = null;
            },

            dropOnFeature(event, featureId, groupId) {
                event.preventDefault();
                event.stopPropagation();
                if (!this.draggedFeatureId || this.draggedFeatureId === featureId) { this.endDrag(); return; }
                const movedId = this.draggedFeatureId;
                const fromGroup = this.draggedFromGroupId;
                const beforeId = this.insertBeforeId;
                this.endDrag();
                $wire.moveFeatureToGroup(movedId, groupId, beforeId);
            },

            dropOnGroup(event, targetGroupId) {
                event.preventDefault();
                if (!this.draggedFeatureId) return;
                const movedId = this.draggedFeatureId;
                const fromGroup = this.draggedFromGroupId;
                this.endDrag();
                $wire.moveFeatureToGroup(movedId, targetGroupId, null);
            }
         }"
         @dragover.prevent
    >
        @foreach($this->groups as $group)
            @php $isExpanded = $this->isGroupExpanded($group['id']); @endphp
            <div wire:key="group-{{ $group['id'] }}" class="feature-tree__group">
                {{-- Group Header (clickable + drop target) --}}
                <div class="feature-tree__group-header"
                     wire:click="toggleGroup({{ $group['id'] }})"
                     @dragover.prevent="highlightGroup($event, {{ $group['id'] }}, {{ $isExpanded ? 'true' : 'false' }})"
                     @dragleave="unhighlightGroup({{ $group['id'] }})"
                     @drop.prevent="dropOnGroup($event, {{ $group['id'] }})"
                     :class="{ 'feature-tree__group-header--drop-target': dropTargetGroupId === {{ $group['id'] }} }"
                >
                    <span class="feature-tree__expand-icon {{ $isExpanded ? 'expanded' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                    <span class="feature-tree__group-icon">
                        {!! $iconMap[$group['icon']]['entity'] ?? '&#128193;' !!}
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

                {{-- Features List (collapsible + drop target) --}}
                @if($isExpanded)
                    <div class="feature-tree__features"
                         @dragover.prevent="highlightGroup($event, {{ $group['id'] }}, true)"
                         @drop.prevent="dropOnGroup($event, {{ $group['id'] }})"
                         :class="{ 'feature-tree__features--drop-target': dropTargetGroupId === {{ $group['id'] }} }"
                    >
                        @forelse($group['features'] as $feature)
                            <div wire:key="feature-{{ $feature['id'] }}"
                                 class="feature-tree__feature-item {{ in_array((string)$feature['id'], $selectedFeatureTypeIds) ? 'feature-tree__feature-item--selected' : '' }}"
                                 draggable="true"
                                 data-feature-id="{{ $feature['id'] }}"
                                 @dragstart="startDrag($event, {{ $feature['id'] }}, {{ $group['id'] }})"
                                 @dragend="endDrag()"
                                 @dragover.prevent="onFeatureDragOver($event, {{ $feature['id'] }}, {{ $group['id'] }})"
                                 @dragleave="clearInsertLine()"
                                 @drop.prevent="dropOnFeature($event, {{ $feature['id'] }}, {{ $group['id'] }})"
                                 :class="{ 'feature-tree__feature-item--dragging': draggedFeatureId === {{ $feature['id'] }} }"
                            >
                                <input type="checkbox"
                                       wire:model.live="selectedFeatureTypeIds"
                                       wire:key="select-feat-{{ $feature['id'] }}"
                                       value="{{ $feature['id'] }}"
                                       class="rounded border-gray-600 bg-gray-700 text-[#e0ac7e] focus:ring-[#e0ac7e] flex-shrink-0"
                                       title="Zaznacz ceche"
                                       @click.stop>
                                <span class="feature-drag-handle">&#9776;</span>
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
                            <div class="feature-tree__empty"
                                 @dragover.prevent="highlightGroup($event, {{ $group['id'] }}, true)"
                                 @drop.prevent="dropOnGroup($event, {{ $group['id'] }})">
                                Brak cech - przeciagnij tutaj
                            </div>
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
                        @php
                            $iconMapJson = json_encode(\App\Models\FeatureGroup::getIconMap());
                            $categoriesJson = json_encode(\App\Models\FeatureGroup::getIconCategories());
                        @endphp
                        <div x-data="{
                            open: false,
                            selected: $wire.entangle('featureGroupIcon'),
                            activeCategory: 'glowne',
                            icons: {{ $iconMapJson }},
                            categories: {{ $categoriesJson }},
                            get selectedEntity() { return this.icons[this.selected]?.entity ?? '&#128193;'; },
                            get selectedLabel() { return this.icons[this.selected]?.label ?? null; },
                            getFilteredIcons() {
                                return Object.fromEntries(
                                    Object.entries(this.icons).filter(([k, v]) => v.category === this.activeCategory)
                                );
                            },
                            selectIcon(key) {
                                this.selected = key;
                                this.open = false;
                            }
                        }" class="relative" wire:key="icon-picker-{{ $editingFeatureGroupId ?? 'new' }}">
                            {{-- Trigger button --}}
                            <button type="button" @click="open = !open"
                                    class="form-input-enterprise w-full flex items-center gap-2 text-left cursor-pointer">
                                <span class="text-xl" x-html="selectedEntity"></span>
                                <span class="text-sm text-gray-300" x-text="selectedLabel || '-- Wybierz ikone --'"></span>
                                <svg class="w-4 h-4 ml-auto text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Dropdown grid --}}
                            <div x-show="open" @click.outside="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="icon-picker-dropdown">

                                {{-- Category tabs --}}
                                <div class="icon-picker-categories">
                                    <template x-for="(catLabel, catKey) in categories" :key="catKey">
                                        <button type="button" @click="activeCategory = catKey"
                                                :class="{ 'icon-picker-cat--active': activeCategory === catKey }"
                                                class="icon-picker-cat"
                                                x-text="catLabel"></button>
                                    </template>
                                </div>

                                {{-- Icons grid --}}
                                <div class="icon-picker-grid">
                                    <template x-for="(iconData, iconKey) in getFilteredIcons()" :key="iconKey">
                                        <button type="button" @click="selectIcon(iconKey)"
                                                :class="{ 'icon-picker-item--selected': selected === iconKey }"
                                                class="icon-picker-item"
                                                :title="iconData.label">
                                            <span class="text-xl" x-html="iconData.entity"></span>
                                            <span class="icon-picker-item-label" x-text="iconData.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Kolor</label>
                        <select wire:model="featureGroupColor" wire:key="color-select-{{ $editingFeatureGroupId ?? 'new' }}" class="form-input">
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
