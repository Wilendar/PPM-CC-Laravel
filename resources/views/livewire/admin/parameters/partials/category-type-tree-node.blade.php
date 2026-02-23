{{-- Recursive Category Tree Node for Type Mapping --}}
{{-- Parameters: $node (array), $depth (int), $selectedId (int|null), $search (string) --}}
@php
    $catId = $node['id'];
    $catName = $node['name'];
    $children = $node['children'] ?? [];
    $hasChildren = !empty($children);
    $isMapped = $node['is_mapped'] ?? false;
    $isSelected = $selectedId === $catId;
    $paddingLeft = $depth * 1.25;
    $defaultExpanded = ($depth < 2) ? 'true' : 'false';
@endphp

<div x-data="{ expanded: {{ $defaultExpanded }} }"
     x-show="!search || name.includes(search.toLowerCase())"
     x-init="name = '{{ addslashes(mb_strtolower($catName)) }}'"
     class="ctm-tree-node">

    {{-- Node row --}}
    <div class="flex items-center gap-1.5 px-2 py-1 rounded-md cursor-pointer transition-colors
                {{ $isSelected ? 'bg-orange-500/20 ring-1 ring-orange-500/40' : ($isMapped ? 'hover:bg-gray-700/50' : 'hover:bg-gray-700/30 opacity-60') }}"
         style="padding-left: {{ $paddingLeft }}rem;"
         @if($isMapped)
             wire:click="selectCategory({{ $catId }})"
         @endif
    >
        {{-- Expand/collapse toggle --}}
        @if($hasChildren)
            <button type="button"
                    @click.stop="expanded = !expanded"
                    class="w-5 h-5 flex items-center justify-center text-gray-500 hover:text-gray-300 flex-shrink-0">
                <svg class="w-3.5 h-3.5 transition-transform duration-150"
                     :class="{ 'rotate-90': expanded }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        @else
            <span class="w-5 flex-shrink-0"></span>
        @endif

        {{-- Category name --}}
        <span class="flex-1 text-sm truncate
                     {{ $isSelected ? 'text-orange-300 font-semibold' : ($isMapped ? 'text-gray-200' : 'text-gray-500 italic') }}">
            {{ $catName }}
        </span>

        {{-- Mapped indicator --}}
        @if($isMapped && !$isSelected)
            <span class="w-1.5 h-1.5 rounded-full bg-green-500/60 flex-shrink-0" title="Zmapowana z PrestaShop"></span>
        @endif

        {{-- Selected checkmark --}}
        @if($isSelected)
            <svg class="w-4 h-4 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        @endif
    </div>

    {{-- Children (recursive) --}}
    @if($hasChildren)
        <div x-show="expanded" x-collapse.duration.150ms>
            @foreach($children as $child)
                @include('livewire.admin.parameters.partials.category-type-tree-node', [
                    'node' => $child,
                    'depth' => $depth + 1,
                    'selectedId' => $selectedId,
                    'search' => $search,
                ])
            @endforeach
        </div>
    @endif
</div>
