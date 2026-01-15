{{-- VariantPanelContainer - 3-Panel Layout with Inline CRUD --}}
{{-- Variant Panel Redesign 2025-12 - Inline Mode (no modals) --}}

<div>
    {{-- Mini Header (simplified - no manager buttons) --}}
    <div class="bg-gray-800/30 border-b border-gray-700 px-4 py-3 mb-4 rounded-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-2xl">📊</span>
                <div>
                    <h2 class="text-lg font-semibold text-white">Przegladarka Wariantow</h2>
                    <p class="text-xs text-gray-400">
                        Wybierz grupe i wartosci aby zobaczyc produkty
                    </p>
                </div>
            </div>
            {{-- Stats badge --}}
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs rounded-lg bg-gray-700/50 text-gray-400">
                    {{ $this->attributeTypes->count() }} grup |
                    {{ $this->attributeTypes->sum('values_count') }} wartosci
                </span>
            </div>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->has('typeDelete') || $errors->has('valueDelete'))
        <div class="mb-4 p-3 rounded-lg bg-red-500/20 border border-red-500/40 text-red-400 text-sm">
            {{ $errors->first('typeDelete') ?: $errors->first('valueDelete') }}
        </div>
    @endif

    {{-- Flash Messages --}}
    @if(session()->has('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-500/20 border border-green-500/40 text-green-400 text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- 3-Panel Layout --}}
    <div class="flex rounded-lg border border-gray-700 overflow-hidden" style="height: 65vh; min-height: 500px;">

        {{-- LEFT PANEL: Attribute Types with Inline CRUD --}}
        <div class="w-64 flex-shrink-0 bg-gray-800/50 border-r border-gray-700 flex flex-col">
            <div class="p-3 border-b border-gray-700">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Grupy Atrybutow
                </h3>
            </div>

            {{-- Types List (scrollable) --}}
            <div class="flex-1 overflow-y-auto p-2 space-y-1">
                @foreach($this->attributeTypes as $type)
                    <div wire:key="type-row-{{ $type->id }}">
                        @if($editingTypeId === $type->id)
                            {{-- EDIT MODE: Inline Form --}}
                            <div class="p-2 rounded-lg bg-gray-700/50 border border-blue-500/40 space-y-2">
                                <input type="text"
                                       wire:model="typeForm.name"
                                       class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                              text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                       placeholder="Nazwa grupy">
                                @error('typeForm.name')
                                    <span class="text-xs text-red-400">{{ $message }}</span>
                                @enderror

                                <input type="text"
                                       wire:model="typeForm.code"
                                       wire:blur="generateTypeCode"
                                       class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                              text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono"
                                       placeholder="kod_grupy">
                                @error('typeForm.code')
                                    <span class="text-xs text-red-400">{{ $message }}</span>
                                @enderror

                                <select wire:model="typeForm.display_type"
                                        class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                               text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="dropdown">📋 Dropdown</option>
                                    <option value="radio">🔘 Radio</option>
                                    <option value="color">🎨 Kolor</option>
                                    <option value="button">🔲 Przyciski</option>
                                </select>

                                <div class="flex gap-1">
                                    <button wire:click="saveTypeEdit"
                                            class="flex-1 px-2 py-1 text-xs rounded bg-blue-500/20 text-blue-400
                                                   border border-blue-500/40 hover:bg-blue-500/30 transition-colors">
                                        ✓ Zapisz
                                    </button>
                                    <button wire:click="cancelTypeEdit"
                                            class="flex-1 px-2 py-1 text-xs rounded bg-gray-700 text-gray-400
                                                   border border-gray-600 hover:bg-gray-600 transition-colors">
                                        ✕ Anuluj
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- VIEW MODE: Type Button with Hover Actions --}}
                            <div class="group relative">
                                <button wire:click="selectType({{ $type->id }})"
                                        class="w-full text-left px-3 py-2 rounded-lg transition-all duration-150
                                               {{ $selectedTypeId === $type->id
                                                   ? 'bg-blue-500/20 border border-blue-500/40 text-blue-400'
                                                   : 'hover:bg-gray-700/50 text-gray-300 border border-transparent' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 min-w-0">
                                            @if($type->display_type === 'color')
                                                <span class="text-base flex-shrink-0">🎨</span>
                                            @elseif($type->display_type === 'radio')
                                                <span class="text-base flex-shrink-0">🔘</span>
                                            @elseif($type->display_type === 'button')
                                                <span class="text-base flex-shrink-0">🔲</span>
                                            @else
                                                <span class="text-base flex-shrink-0">📋</span>
                                            @endif
                                            <span class="font-medium text-sm truncate">{{ $type->name }}</span>
                                        </div>
                                        <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0
                                                     {{ $selectedTypeId === $type->id
                                                         ? 'bg-blue-500/30 text-blue-300'
                                                         : 'bg-gray-700 text-gray-400' }}">
                                            {{ $type->values_count }}
                                        </span>
                                    </div>
                                </button>

                                {{-- Hover Actions --}}
                                <div class="absolute right-1 top-1/2 -translate-y-1/2 hidden group-hover:flex items-center gap-0.5
                                            {{ $selectedTypeId === $type->id ? '' : '' }}">
                                    <button wire:click.stop="startTypeEdit({{ $type->id }})"
                                            class="p-1 rounded bg-gray-700/80 text-gray-400 hover:text-blue-400 hover:bg-gray-600 transition-colors"
                                            title="Edytuj">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                    <button wire:click.stop="deleteType({{ $type->id }})"
                                            wire:confirm="Usunac grupe '{{ $type->name }}'? Wszystkie wartosci zostana usuniete!"
                                            class="p-1 rounded bg-gray-700/80 text-gray-400 hover:text-red-400 hover:bg-gray-600 transition-colors"
                                            title="Usun">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if($this->attributeTypes->isEmpty() && !$showTypeCreateForm)
                    <div class="text-center py-6 text-gray-500">
                        <div class="text-2xl mb-2">📝</div>
                        <p class="text-xs">Brak grup atrybutow</p>
                    </div>
                @endif
            </div>

            {{-- CREATE TYPE FORM (at bottom) --}}
            <div class="p-2 border-t border-gray-700">
                @if($showTypeCreateForm)
                    <div class="p-2 rounded-lg bg-gray-700/30 border border-gray-600 space-y-2">
                        <div class="text-xs font-semibold text-gray-400 mb-1">Nowa grupa</div>

                        <input type="text"
                               wire:model="typeForm.name"
                               wire:blur="generateTypeCode"
                               class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                      text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                               placeholder="Nazwa grupy *">
                        @error('typeForm.name')
                            <span class="text-xs text-red-400">{{ $message }}</span>
                        @enderror

                        <input type="text"
                               wire:model="typeForm.code"
                               class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                      text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono"
                               placeholder="kod_grupy *">
                        @error('typeForm.code')
                            <span class="text-xs text-red-400">{{ $message }}</span>
                        @enderror

                        <select wire:model="typeForm.display_type"
                                class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                       text-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="dropdown">📋 Dropdown</option>
                            <option value="radio">🔘 Radio</option>
                            <option value="color">🎨 Kolor</option>
                            <option value="button">🔲 Przyciski</option>
                        </select>

                        @error('typeForm.general')
                            <div class="text-xs text-red-400">{{ $message }}</div>
                        @enderror

                        <div class="flex gap-1">
                            <button wire:click="createType"
                                    class="flex-1 px-2 py-1.5 text-xs rounded bg-green-500/20 text-green-400
                                           border border-green-500/40 hover:bg-green-500/30 transition-colors font-medium">
                                ✓ Utworz
                            </button>
                            <button wire:click="cancelTypeCreate"
                                    class="flex-1 px-2 py-1.5 text-xs rounded bg-gray-700 text-gray-400
                                           border border-gray-600 hover:bg-gray-600 transition-colors">
                                ✕ Anuluj
                            </button>
                        </div>
                    </div>
                @else
                    <button wire:click="showCreateTypeForm"
                            class="w-full px-3 py-2.5 text-sm font-medium rounded-lg
                                   bg-gradient-to-r from-gray-700 to-gray-800
                                   border border-gray-500 hover:border-blue-500
                                   text-gray-200 hover:text-white
                                   shadow-sm hover:shadow-md
                                   transition-all duration-200 ease-out
                                   flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>Dodaj grupe atrybutow</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- CENTER PANEL: Attribute Values with Inline CRUD --}}
        <div class="flex-1 min-w-0 border-r border-gray-700 flex flex-col overflow-hidden">

            @if($showValuePanel && $this->selectedType)
                {{-- Values Header --}}
                <div class="px-4 py-3 bg-gray-800/30 border-b border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-base font-semibold text-white flex items-center gap-2">
                                @if($this->selectedType->display_type === 'color')
                                    <span>🎨</span>
                                @elseif($this->selectedType->display_type === 'radio')
                                    <span>🔘</span>
                                @elseif($this->selectedType->display_type === 'button')
                                    <span>🔲</span>
                                @else
                                    <span>📋</span>
                                @endif
                                {{ $this->selectedType->name }}
                            </h3>
                            <p class="text-xs text-gray-400">
                                {{ $this->valuesWithCounts->count() }} wartosci
                                @if(count($selectedValueIds) > 0)
                                    | <span class="text-blue-400">{{ count($selectedValueIds) }} wybranych</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(count($selectedValueIds) > 0)
                                <button wire:click="clearValueSelection"
                                        class="text-xs text-gray-400 hover:text-white transition-colors">
                                    Wyczysc
                                </button>
                            @endif
                            <button wire:click="selectAllValues"
                                    class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                                Zaznacz wszystkie
                            </button>
                        </div>
                    </div>

                    {{-- Filter Mode Toggle --}}
                    @if(count($selectedValueIds) > 1)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-gray-400">Tryb filtrowania:</span>
                            <button wire:click="setFilterMode('any')"
                                    class="px-2 py-1 rounded transition-colors
                                           {{ $filterMode === 'any'
                                               ? 'bg-blue-500/20 text-blue-400 border border-blue-500/40'
                                               : 'text-gray-400 hover:text-white hover:bg-gray-700' }}">
                                LUB (dowolna)
                            </button>
                            <button wire:click="setFilterMode('all')"
                                    class="px-2 py-1 rounded transition-colors
                                           {{ $filterMode === 'all'
                                               ? 'bg-green-500/20 text-green-400 border border-green-500/40'
                                               : 'text-gray-400 hover:text-white hover:bg-gray-700' }}">
                                I (wszystkie)
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Values Grid/List (scrollable) --}}
                <div class="flex-1 overflow-y-auto p-3">
                    @if($this->selectedType->display_type === 'color')
                        {{-- Color Swatches Grid - Always view mode --}}
                        <div class="grid grid-cols-4 sm:grid-cols-5 lg:grid-cols-6 gap-2">
                            @foreach($this->valuesWithCounts as $value)
                                <div wire:key="value-row-{{ $value->id }}">
                                    {{-- VIEW MODE: Color Swatch --}}
                                    @php
                                        $isSelected = in_array($value->id, $selectedValueIds);
                                        $isEditing = $editingValueId === $value->id;
                                    @endphp
                                    <div class="relative">
                                        <button wire:click="toggleValue({{ $value->id }})"
                                                class="w-full p-2 rounded-lg transition-all duration-150
                                                       {{ $isEditing
                                                           ? 'bg-blue-600/30 border-2 border-blue-500 ring-2 ring-blue-500/50'
                                                           : ($isSelected
                                                               ? 'bg-blue-500/20 border-2 border-blue-500 ring-2 ring-blue-500/30'
                                                               : 'bg-gray-800 border-2 border-gray-700 hover:border-gray-600') }}">
                                            {{-- Color Swatch --}}
                                            <div class="w-full h-12 rounded-lg border-2
                                                        {{ $isEditing ? 'border-blue-400' : ($isSelected ? 'border-blue-400' : 'border-gray-600') }}"
                                                 style="background-color: {{ $value->color_hex ?? '#6b7280' }}">
                                            </div>

                                            <div class="text-center mt-1.5">
                                                <div class="text-sm font-medium text-gray-200 truncate">
                                                    {{ $value->label }}
                                                </div>
                                                @if($value->auto_prefix_enabled || $value->auto_suffix_enabled)
                                                    <div class="text-xs text-blue-400 font-mono">
                                                        @if($value->auto_prefix_enabled && $value->auto_prefix){{ $value->auto_prefix }}-@endif...@if($value->auto_suffix_enabled && $value->auto_suffix)-{{ $value->auto_suffix }}@endif
                                                    </div>
                                                @endif
                                                <div class="text-xs text-gray-500">
                                                    {{ $value->product_count }} prod.
                                                </div>
                                            </div>

                                            @if($isSelected)
                                                <div class="absolute top-1.5 right-1.5 w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                            @endif

                                            @if($isEditing)
                                                <div class="absolute top-1.5 left-1.5 px-1.5 py-0.5 bg-blue-500 rounded text-[10px] text-white font-medium">
                                                    Edycja
                                                </div>
                                            @endif
                                        </button>

                                        {{-- Permanent Actions Below Tile --}}
                                        @if(!$isEditing)
                                            <div class="flex justify-center gap-1 mt-1">
                                                <button wire:click.stop="startValueEdit({{ $value->id }})"
                                                        class="p-1 rounded bg-gray-700 text-gray-400 hover:text-blue-400 hover:bg-gray-600 transition-colors"
                                                        title="Edytuj">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                    </svg>
                                                </button>
                                                <button wire:click.stop="deleteValue({{ $value->id }})"
                                                        wire:confirm="Usunac wartosc '{{ $value->label }}'?"
                                                        class="p-1 rounded bg-gray-700 text-gray-400 hover:text-red-400 hover:bg-gray-600 transition-colors"
                                                        title="Usun">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- List View for non-color types --}}
                        <div class="space-y-1">
                            @foreach($this->valuesWithCounts as $value)
                                <div wire:key="value-row-{{ $value->id }}">
                                    @if($editingValueId === $value->id)
                                        {{-- EDIT MODE: List Value --}}
                                        <div class="p-3 rounded-lg bg-gray-700/50 border border-blue-500/40 space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <input type="text"
                                                           wire:model="valueForm.label"
                                                           class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                                                  text-gray-200 placeholder-gray-500 focus:border-blue-500"
                                                           placeholder="Etykieta *">
                                                    @error('valueForm.label')
                                                        <span class="text-xs text-red-400">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <input type="text"
                                                           wire:model="valueForm.code"
                                                           class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                                                  text-gray-200 placeholder-gray-500 focus:border-blue-500 font-mono"
                                                           placeholder="kod *">
                                                    @error('valueForm.code')
                                                        <span class="text-xs text-red-400">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            {{-- AutoSKU: Prefix/Suffix --}}
                                            <div class="grid grid-cols-2 gap-2 pt-1 border-t border-gray-600">
                                                <div class="flex items-center gap-2">
                                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                                        <input type="checkbox" wire:model.live="valueForm.auto_prefix_enabled"
                                                               class="w-4 h-4 rounded border-gray-600 bg-gray-900 text-blue-500 focus:ring-blue-500">
                                                        <span class="text-xs text-gray-400">Prefix:</span>
                                                    </label>
                                                    <input type="text" wire:model="valueForm.auto_prefix"
                                                           class="flex-1 px-2 py-1 text-sm bg-gray-900 border border-gray-600 rounded
                                                                  text-gray-200 font-mono uppercase {{ !$valueForm['auto_prefix_enabled'] ? 'opacity-50' : '' }}"
                                                           placeholder="XXX" maxlength="10" {{ !$valueForm['auto_prefix_enabled'] ? 'disabled' : '' }}>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                                        <input type="checkbox" wire:model.live="valueForm.auto_suffix_enabled"
                                                               class="w-4 h-4 rounded border-gray-600 bg-gray-900 text-blue-500 focus:ring-blue-500">
                                                        <span class="text-xs text-gray-400">Suffix:</span>
                                                    </label>
                                                    <input type="text" wire:model="valueForm.auto_suffix"
                                                           class="flex-1 px-2 py-1 text-sm bg-gray-900 border border-gray-600 rounded
                                                                  text-gray-200 font-mono uppercase {{ !$valueForm['auto_suffix_enabled'] ? 'opacity-50' : '' }}"
                                                           placeholder="XXX" maxlength="10" {{ !$valueForm['auto_suffix_enabled'] ? 'disabled' : '' }}>
                                                </div>
                                            </div>

                                            <div class="flex gap-1">
                                                <button wire:click="saveValueEdit"
                                                        class="flex-1 px-2 py-1.5 text-xs rounded bg-blue-500/20 text-blue-400
                                                               border border-blue-500/40 hover:bg-blue-500/30 transition-colors">
                                                    ✓ Zapisz
                                                </button>
                                                <button wire:click="cancelValueEdit"
                                                        class="flex-1 px-2 py-1.5 text-xs rounded bg-gray-700 text-gray-400
                                                               border border-gray-600 hover:bg-gray-600 transition-colors">
                                                    ✕ Anuluj
                                                </button>
                                            </div>
                                        </div>
                                    @else
                                        {{-- VIEW MODE: List Item --}}
                                        @php $isSelected = in_array($value->id, $selectedValueIds); @endphp
                                        <div class="group relative">
                                            <button wire:click="toggleValue({{ $value->id }})"
                                                    class="w-full text-left px-3 py-2 rounded-lg transition-all duration-150
                                                           flex items-center justify-between
                                                           {{ $isSelected
                                                               ? 'bg-blue-500/20 border border-blue-500/40'
                                                               : 'bg-gray-800/50 border border-gray-700 hover:border-gray-600 hover:bg-gray-800' }}">
                                                <div class="flex items-center gap-2">
                                                    {{-- Checkbox indicator --}}
                                                    <div class="w-4 h-4 rounded border-2 flex items-center justify-center transition-colors
                                                                {{ $isSelected
                                                                    ? 'bg-blue-500 border-blue-500'
                                                                    : 'border-gray-600 group-hover:border-gray-500' }}">
                                                        @if($isSelected)
                                                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <div class="font-medium text-sm {{ $isSelected ? 'text-blue-400' : 'text-gray-200' }}">
                                                            {{ $value->label }}
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            kod: {{ $value->code }}
                                                            @if($value->auto_prefix_enabled || $value->auto_suffix_enabled)
                                                                <span class="text-blue-400 font-mono ml-1">
                                                                    (SKU: @if($value->auto_prefix_enabled && $value->auto_prefix){{ $value->auto_prefix }}-@endif...@if($value->auto_suffix_enabled && $value->auto_suffix)-{{ $value->auto_suffix }}@endif)
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    {{-- Product count badge --}}
                                                    <span class="px-1.5 py-0.5 text-xs rounded
                                                                 {{ $value->product_count > 0
                                                                     ? 'bg-purple-500/20 text-purple-400 border border-purple-500/30'
                                                                     : 'bg-gray-700 text-gray-500' }}">
                                                        {{ $value->product_count }} prod.
                                                    </span>
                                                </div>
                                            </button>

                                            {{-- Hover Actions --}}
                                            <div class="absolute right-16 top-1/2 -translate-y-1/2 hidden group-hover:flex items-center gap-0.5">
                                                <button wire:click.stop="startValueEdit({{ $value->id }})"
                                                        class="p-1.5 rounded bg-gray-700/80 text-gray-400 hover:text-blue-400 hover:bg-gray-600 transition-colors"
                                                        title="Edytuj">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                    </svg>
                                                </button>
                                                <button wire:click.stop="deleteValue({{ $value->id }})"
                                                        wire:confirm="Usunac wartosc '{{ $value->label }}'?"
                                                        class="p-1.5 rounded bg-gray-700/80 text-gray-400 hover:text-red-400 hover:bg-gray-600 transition-colors"
                                                        title="Usun">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($this->valuesWithCounts->isEmpty() && !$showValueCreateForm)
                        <div class="text-center py-8">
                            <div class="text-4xl mb-3 opacity-50">📝</div>
                            <h4 class="text-sm font-semibold text-gray-300 mb-2">Brak wartosci</h4>
                            <p class="text-xs text-gray-400">Ta grupa nie ma jeszcze zadnych wartosci</p>
                        </div>
                    @endif
                </div>

                {{-- CREATE/EDIT VALUE FORM (at bottom) --}}
                <div class="p-3 border-t border-gray-700">
                    @if($showValueCreateForm || ($editingValueId && $this->selectedType->display_type === 'color'))
                        @php
                            $isEditMode = $editingValueId !== null;
                            $formTitle = $isEditMode ? 'Edycja wartosci' : 'Nowa wartosc';
                            $formBorderClass = $isEditMode ? 'border-blue-500/50' : 'border-gray-600';
                            $formBgClass = $isEditMode ? 'bg-blue-900/20' : 'bg-gray-700/30';
                        @endphp
                        <div class="p-3 rounded-lg {{ $formBgClass }} border {{ $formBorderClass }} space-y-2">
                            <div class="flex items-center justify-between mb-1">
                                <div class="text-xs font-semibold {{ $isEditMode ? 'text-blue-400' : 'text-gray-400' }}">
                                    {{ $formTitle }}
                                </div>
                                @if($isEditMode)
                                    <span class="px-2 py-0.5 text-xs rounded bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                        Edycja: {{ $valueForm['label'] ?? 'Brak' }}
                                    </span>
                                @endif
                            </div>

                            @if($this->selectedType->display_type === 'color')
                                {{-- Color type form - TWO COLUMN LAYOUT --}}
                                <div x-data="{
                                    hex: $wire.entangle('valueForm.color_hex').live,
                                    hue: 0,
                                    saturation: 100,
                                    brightness: 50,
                                    dragging: false,
                                    dragType: null,

                                    init() {
                                        if (this.hex) this.hexToHsb(this.hex);
                                        this.$watch('hex', (val) => { if (val && val.length === 7) this.hexToHsb(val); });
                                    },

                                    hexToHsb(hex) {
                                        let r = parseInt(hex.slice(1,3), 16) / 255;
                                        let g = parseInt(hex.slice(3,5), 16) / 255;
                                        let b = parseInt(hex.slice(5,7), 16) / 255;
                                        let max = Math.max(r, g, b), min = Math.min(r, g, b);
                                        let h, s, v = max;
                                        let d = max - min;
                                        s = max === 0 ? 0 : d / max;
                                        if (max === min) { h = 0; }
                                        else {
                                            switch (max) {
                                                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                                                case g: h = (b - r) / d + 2; break;
                                                case b: h = (r - g) / d + 4; break;
                                            }
                                            h /= 6;
                                        }
                                        this.hue = Math.round(h * 360);
                                        this.saturation = Math.round(s * 100);
                                        this.brightness = Math.round(v * 100);
                                    },

                                    hsbToHex() {
                                        let h = this.hue / 360, s = this.saturation / 100, v = this.brightness / 100;
                                        let r, g, b;
                                        let i = Math.floor(h * 6);
                                        let f = h * 6 - i;
                                        let p = v * (1 - s);
                                        let q = v * (1 - f * s);
                                        let t = v * (1 - (1 - f) * s);
                                        switch (i % 6) {
                                            case 0: r = v; g = t; b = p; break;
                                            case 1: r = q; g = v; b = p; break;
                                            case 2: r = p; g = v; b = t; break;
                                            case 3: r = p; g = q; b = v; break;
                                            case 4: r = t; g = p; b = v; break;
                                            case 5: r = v; g = p; b = q; break;
                                        }
                                        return '#' + [r, g, b].map(x => Math.round(x * 255).toString(16).padStart(2, '0')).join('');
                                    },

                                    updateFromGradient(e) {
                                        let rect = e.currentTarget.getBoundingClientRect();
                                        this.saturation = Math.max(0, Math.min(100, Math.round((e.clientX - rect.left) / rect.width * 100)));
                                        this.brightness = Math.max(0, Math.min(100, Math.round(100 - (e.clientY - rect.top) / rect.height * 100)));
                                        this.hex = this.hsbToHex();
                                    },

                                    updateFromHue(e) {
                                        let rect = e.currentTarget.getBoundingClientRect();
                                        this.hue = Math.max(0, Math.min(360, Math.round((e.clientX - rect.left) / rect.width * 360)));
                                        this.hex = this.hsbToHex();
                                    },

                                    get hueColor() {
                                        let h = this.hue / 360;
                                        let r, g, b;
                                        let i = Math.floor(h * 6);
                                        let f = h * 6 - i;
                                        switch (i % 6) {
                                            case 0: r = 1; g = f; b = 0; break;
                                            case 1: r = 1-f; g = 1; b = 0; break;
                                            case 2: r = 0; g = 1; b = f; break;
                                            case 3: r = 0; g = 1-f; b = 1; break;
                                            case 4: r = f; g = 0; b = 1; break;
                                            case 5: r = 1; g = 0; b = 1-f; break;
                                        }
                                        return `rgb(${Math.round(r*255)},${Math.round(g*255)},${Math.round(b*255)})`;
                                    }
                                }" class="grid grid-cols-2 gap-4">
                                    {{-- LEFT COLUMN: Color Picker --}}
                                    <div class="space-y-2">
                                        {{-- Saturation/Brightness Gradient - FULL SIZE --}}
                                        <div class="relative h-40 rounded-lg cursor-crosshair overflow-hidden border border-gray-600"
                                             :style="'background: linear-gradient(to top, #000, transparent), linear-gradient(to right, #fff, ' + hueColor + ')'"
                                             @mousedown="dragging = true; dragType = 'gradient'; updateFromGradient($event)"
                                             @mousemove="if (dragging && dragType === 'gradient') updateFromGradient($event)"
                                             @mouseup="dragging = false"
                                             @mouseleave="dragging = false">
                                            <div class="absolute w-4 h-4 border-2 border-white rounded-full shadow-lg transform -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                                                 :style="'left: ' + saturation + '%; top: ' + (100 - brightness) + '%; background: ' + hex">
                                            </div>
                                        </div>

                                        {{-- Hue Slider --}}
                                        <div class="relative h-3 rounded-full cursor-pointer overflow-hidden border border-gray-600"
                                             style="background: linear-gradient(to right, #f00 0%, #ff0 17%, #0f0 33%, #0ff 50%, #00f 67%, #f0f 83%, #f00 100%)"
                                             @mousedown="dragging = true; dragType = 'hue'; updateFromHue($event)"
                                             @mousemove="if (dragging && dragType === 'hue') updateFromHue($event)"
                                             @mouseup="dragging = false"
                                             @mouseleave="dragging = false">
                                            <div class="absolute top-1/2 w-2.5 h-5 bg-white border-2 border-gray-800 rounded shadow-lg transform -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                                                 :style="'left: ' + (hue / 360 * 100) + '%'">
                                            </div>
                                        </div>

                                        {{-- Color Preview + Hex --}}
                                        <div class="flex items-center gap-2">
                                            <div class="w-10 h-10 rounded-lg border-2 border-gray-600 shadow-inner flex-shrink-0"
                                                 :style="'background-color: ' + hex"></div>
                                            <input type="text"
                                                   x-model="hex"
                                                   @input="hex = $event.target.value.toUpperCase()"
                                                   class="flex-1 px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                                          text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1
                                                          focus:ring-blue-500 font-mono uppercase"
                                                   placeholder="#000000"
                                                   maxlength="7">
                                        </div>
                                    </div>

                                    {{-- RIGHT COLUMN: Input Fields --}}
                                    <div class="space-y-2">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">Nazwa *</label>
                                            <input type="text"
                                                   wire:model="valueForm.label"
                                                   wire:blur="generateValueCode"
                                                   class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                                          text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                   placeholder="np. Czerwony">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-0.5">Kod *</label>
                                            <input type="text"
                                                   wire:model="valueForm.code"
                                                   class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                                          text-gray-200 placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono"
                                                   placeholder="np. czerwony">
                                        </div>
                                        {{-- AutoSKU Prefix/Suffix with Enabled Checkboxes --}}
                                        <div class="space-y-2 pt-1 border-t border-gray-600/50">
                                            <div class="text-xs text-gray-500">AutoSKU</div>
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="valueForm.auto_prefix_enabled"
                                                           class="w-4 h-4 rounded border-gray-600 bg-gray-900 text-blue-500 focus:ring-blue-500">
                                                    <span class="text-xs text-gray-400">Prefix:</span>
                                                </label>
                                                <input type="text" wire:model="valueForm.auto_prefix"
                                                       class="flex-1 px-2 py-1 text-sm bg-gray-900 border border-gray-600 rounded
                                                              text-gray-200 font-mono uppercase {{ !$valueForm['auto_prefix_enabled'] ? 'opacity-50' : '' }}"
                                                       placeholder="XXX" maxlength="10" {{ !$valueForm['auto_prefix_enabled'] ? 'disabled' : '' }}>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="valueForm.auto_suffix_enabled"
                                                           class="w-4 h-4 rounded border-gray-600 bg-gray-900 text-blue-500 focus:ring-blue-500">
                                                    <span class="text-xs text-gray-400">Suffix:</span>
                                                </label>
                                                <input type="text" wire:model="valueForm.auto_suffix"
                                                       class="flex-1 px-2 py-1 text-sm bg-gray-900 border border-gray-600 rounded
                                                              text-gray-200 font-mono uppercase {{ !$valueForm['auto_suffix_enabled'] ? 'opacity-50' : '' }}"
                                                       placeholder="XXX" maxlength="10" {{ !$valueForm['auto_suffix_enabled'] ? 'disabled' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                {{-- Non-color type form --}}
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <input type="text"
                                               wire:model="valueForm.label"
                                               wire:blur="generateValueCode"
                                               class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                                      text-gray-200 placeholder-gray-500 focus:border-blue-500"
                                               placeholder="Etykieta *">
                                    </div>
                                    <div>
                                        <input type="text"
                                               wire:model="valueForm.code"
                                               class="w-full px-2 py-1.5 text-sm bg-gray-900 border border-gray-600 rounded
                                                      text-gray-200 placeholder-gray-500 focus:border-blue-500 font-mono"
                                               placeholder="kod *">
                                    </div>
                                </div>
                            @endif

                            @error('valueForm.label')
                                <span class="text-xs text-red-400">{{ $message }}</span>
                            @enderror
                            @error('valueForm.code')
                                <span class="text-xs text-red-400">{{ $message }}</span>
                            @enderror
                            @error('valueForm.color_hex')
                                <span class="text-xs text-red-400">{{ $message }}</span>
                            @enderror
                            @error('valueForm.general')
                                <div class="text-xs text-red-400">{{ $message }}</div>
                            @enderror

                            <div class="flex gap-1">
                                @if($isEditMode)
                                    <button wire:click="saveValueEdit"
                                            class="flex-1 px-2 py-1.5 text-xs rounded bg-blue-500/20 text-blue-400
                                                   border border-blue-500/40 hover:bg-blue-500/30 transition-colors font-medium">
                                        ✓ Zapisz zmiany
                                    </button>
                                    <button wire:click="cancelValueEdit"
                                            class="flex-1 px-2 py-1.5 text-xs rounded bg-gray-700 text-gray-400
                                                   border border-gray-600 hover:bg-gray-600 transition-colors">
                                        ✕ Anuluj
                                    </button>
                                @else
                                    <button wire:click="createValue"
                                            class="flex-1 px-2 py-1.5 text-xs rounded bg-green-500/20 text-green-400
                                                   border border-green-500/40 hover:bg-green-500/30 transition-colors font-medium">
                                        ✓ Utworz
                                    </button>
                                    <button wire:click="cancelValueCreate"
                                            class="flex-1 px-2 py-1.5 text-xs rounded bg-gray-700 text-gray-400
                                                   border border-gray-600 hover:bg-gray-600 transition-colors">
                                        ✕ Anuluj
                                    </button>
                                @endif
                            </div>
                        </div>
                    @else
                        <button wire:click="showCreateValueForm"
                                class="w-full px-3 py-2.5 text-sm font-medium rounded-lg
                                       bg-gradient-to-r from-gray-700 to-gray-800
                                       border border-gray-500 hover:border-green-500
                                       text-gray-200 hover:text-white
                                       shadow-sm hover:shadow-md
                                       transition-all duration-200 ease-out
                                       flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span>Dodaj nowa wartosc</span>
                        </button>
                    @endif
                </div>
            @else
                {{-- Empty state --}}
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div class="text-5xl mb-3 opacity-30">👈</div>
                        <h3 class="text-base font-semibold text-gray-400 mb-1">Wybierz grupe</h3>
                        <p class="text-xs text-gray-500">Kliknij grupe atrybutow po lewej stronie</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- RIGHT PANEL: Products --}}
        <div class="w-80 flex-shrink-0 flex flex-col overflow-hidden bg-gray-800/30
                    {{ !$showProductPanel ? 'hidden xl:flex' : '' }}">

            @if($showProductPanel && count($selectedValueIds) > 0)
                {{-- Products Header --}}
                <div class="px-3 py-3 border-b border-gray-700">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-base font-semibold text-white">Produkty</h3>
                            <p class="text-xs text-gray-400">
                                <span class="text-green-400 font-medium">{{ $this->productCount }}</span> produktow
                                @if($filterMode === 'all')
                                    <span class="text-gray-500">(wszystkie wartosci)</span>
                                @else
                                    <span class="text-gray-500">(dowolna wartosc)</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="productSearch"
                               class="w-full px-3 py-1.5 text-sm bg-gray-900 border border-gray-700 rounded-lg
                                      text-gray-200 placeholder-gray-500
                                      focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                               placeholder="Szukaj SKU lub nazwy...">
                        <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none">
                            <span class="text-gray-500">🔍</span>
                        </div>
                    </div>
                </div>

                {{-- Products List --}}
                <div class="flex-1 overflow-y-auto">
                    @if($this->products && $this->products->count() > 0)
                        <div class="space-y-1 p-2">
                            @foreach($this->products as $product)
                                @php
                                    // Collect all unique color values from all variants
                                    $colorAttrs = collect();
                                    if ($product->variants) {
                                        foreach ($product->variants as $variant) {
                                            foreach ($variant->attributes as $attr) {
                                                if ($attr->attributeValue && $attr->attributeValue->color_hex) {
                                                    $colorAttrs->put($attr->value_id, $attr->attributeValue);
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <a href="{{ route('admin.products.edit', $product->id) }}"
                                   wire:key="product-{{ $product->id }}"
                                   class="block relative p-2.5 rounded-lg bg-gray-800/50 border border-gray-700/50
                                          hover:bg-gray-700/50 hover:border-gray-600 transition-all group">

                                    {{-- Top: SKU + Variant Count --}}
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="px-1.5 py-0.5 text-xs font-mono rounded
                                                     bg-gray-900 text-gray-300 border border-gray-600">
                                            {{ $product->sku }}
                                        </span>
                                        @if($product->variants && $product->variants->count() > 0)
                                            <span class="px-1.5 py-0.5 text-xs rounded-full
                                                         bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                                {{ $product->variants->count() }} war.
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Product Name --}}
                                    <div class="text-sm font-medium text-gray-200 truncate mb-2 group-hover:text-white">
                                        {{ $product->name }}
                                    </div>

                                    {{-- Color Swatches Row - ALL colors --}}
                                    @if($colorAttrs->count() > 0)
                                        <div class="flex items-center gap-1 flex-wrap">
                                            @foreach($colorAttrs->take(8) as $valueId => $attrValue)
                                                @php $isSelected = in_array($valueId, $selectedValueIds); @endphp
                                                <div class="relative group/swatch" title="{{ $attrValue->label }}">
                                                    <span class="block w-5 h-5 rounded-md border-2 transition-all
                                                                 {{ $isSelected
                                                                     ? 'border-blue-400 ring-2 ring-blue-400/50 scale-110'
                                                                     : 'border-gray-600 hover:border-gray-500' }}"
                                                          style="background-color: {{ $attrValue->color_hex }}">
                                                    </span>
                                                    @if($isSelected)
                                                        <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-blue-500 rounded-full border border-gray-800"></span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($colorAttrs->count() > 8)
                                                <span class="text-xs text-gray-500 ml-1">+{{ $colorAttrs->count() - 8 }}</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Arrow indicator --}}
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        @if($this->products->hasPages())
                            <div class="px-3 py-2 border-t border-gray-700">
                                {{ $this->products->links('livewire.partials.simple-pagination') }}
                            </div>
                        @endif
                    @else
                        <div class="flex-1 flex items-center justify-center p-6">
                            <div class="text-center">
                                <div class="text-3xl mb-2 opacity-50">🔍</div>
                                <p class="text-xs text-gray-400">Brak produktow z wybranymi wartosciami</p>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Empty state --}}
                <div class="flex-1 flex items-center justify-center p-6">
                    <div class="text-center">
                        <div class="text-4xl mb-3 opacity-30">📦</div>
                        <h3 class="text-sm font-semibold text-gray-400 mb-1">Wybierz wartosci</h3>
                        <p class="text-xs text-gray-500">
                            Kliknij wartosci atrybutow aby zobaczyc produkty
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
