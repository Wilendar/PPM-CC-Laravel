{{-- Collapsible attribute type panel for VariantModal --}}
@php
    $isSelected = isset($selectedAttributeTypes[$type->id]) && $selectedAttributeTypes[$type->id];
    $selectedCount = count(array_filter($selectedValues[$type->id] ?? []));
@endphp

<div class="import-attr-panel" wire:key="attr-panel-{{ $type->id }}">
    {{-- Panel Header --}}
    <div class="import-attr-panel-header {{ $isSelected ? 'import-attr-panel-header-active' : '' }}"
         x-on:click="expanded[{{ $type->id }}] = !expanded[{{ $type->id }}]">
        {{-- Checkbox --}}
        <input type="checkbox"
               wire:click.stop="toggleAttributeType({{ $type->id }})"
               @checked($isSelected)
               class="w-4 h-4 rounded bg-gray-700 border-gray-500 text-green-500 focus:ring-green-500/30 cursor-pointer">

        {{-- Chevron --}}
        <svg class="w-4 h-4 flex-shrink-0 import-attr-panel-chevron"
             :class="expanded[{{ $type->id }}] && 'import-attr-panel-chevron-open'"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>

        {{-- Name + Code --}}
        <span class="text-sm font-medium text-gray-200">{{ $type->name }}</span>
        <span class="text-xs text-gray-500">({{ $type->code }})</span>

        {{-- Color indicator --}}
        @if($type->display_type === 'color')
            <span class="text-xs text-purple-400 ml-1">Kolor</span>
        @endif

        {{-- Selected count badge --}}
        @if($isSelected && $selectedCount > 0)
            <span class="ml-auto text-xs text-gray-400">{{ $selectedCount }} wybranych</span>
        @endif
    </div>

    {{-- Panel Body: Values + Search --}}
    <div class="import-attr-panel-body"
         x-show="expanded[{{ $type->id }}] && {{ $isSelected ? 'true' : 'false' }}"
         x-collapse>

        {{-- Search input --}}
        <input type="text"
               x-model="valueSearch[{{ $type->id }}]"
               placeholder="Szukaj wartosci..."
               class="import-value-search">

        {{-- Value buttons --}}
        <div class="flex flex-wrap gap-1.5 mt-2">
            @foreach($this->getValuesForType($type->id) as $value)
                @php
                    $isValueSelected = isset($selectedValues[$type->id][$value->id]) && $selectedValues[$type->id][$value->id];
                    $usage = $this->getValueUsageCount($type->id, $value->id);
                    $valueLabelLower = strtolower($value->label);
                @endphp

                <template x-if="!valueSearch[{{ $type->id }}] || '{{ addslashes($valueLabelLower) }}'.includes((valueSearch[{{ $type->id }}] || '').toLowerCase())">
                    <button type="button"
                            wire:click="toggleValue({{ $type->id }}, {{ $value->id }})"
                            class="px-2 py-1 rounded text-xs font-medium transition-all
                                   {{ $isValueSelected
                                       ? 'bg-green-600/30 text-green-300 ring-1 ring-green-500/50'
                                       : 'bg-gray-700/50 text-gray-400 hover:bg-gray-600/50' }}"
                            title="{{ $value->auto_suffix ? 'Suffix: ' . $value->auto_suffix : '' }}{{ $value->auto_prefix ? ' Prefix: ' . $value->auto_prefix : '' }}">
                        @if($value->color_hex)
                            <span class="inline-block w-3 h-3 rounded-full mr-1 align-middle"
                                  style="background-color: {{ $value->color_hex }}"></span>
                        @endif
                        {{ $value->label }}
                        @if($value->auto_suffix)
                            <span class="text-gray-500 text-[0.625rem]">({{ $value->auto_suffix }})</span>
                        @endif
                        @if($usage > 0)
                            <span class="import-value-usage">({{ $usage }})</span>
                        @endif
                    </button>
                </template>
            @endforeach
        </div>
    </div>
</div>
