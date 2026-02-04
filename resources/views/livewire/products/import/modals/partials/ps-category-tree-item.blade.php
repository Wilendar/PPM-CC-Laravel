{{-- Category tree item (hierarchical mode, recursive) - FAZA 9.7b BUG#2 fix --}}
@php
    $catId = (int) ($category['id'] ?? 0);
    $catName = $category['name'] ?? 'Unknown';
    $catLevel = (int) ($category['level'] ?? 0);
    $children = $category['children'] ?? [];
    $hasChildren = !empty($children);
    $isSelected = in_array($catId, $selectedCategoryIds);
    $paddingLeft = $depth * 1.25; // rem
    // Default collapsed for depth > 0 (only root level expanded by default)
    $defaultExpanded = ($depth === 0) ? 'true' : 'false';
@endphp

<div class="ps-category-tree-node" style="padding-left: {{ $paddingLeft }}rem;"
     x-data="{ expanded: {{ $defaultExpanded }} }">
    {{-- Category item --}}
    <div class="flex items-center gap-2 px-3 py-1.5 rounded transition-colors
                {{ $isSelected ? 'bg-purple-900/30' : 'hover:bg-gray-700/30' }}">
        {{-- Expand/collapse icon for parents --}}
        @if($hasChildren)
            <button type="button"
                    x-on:click.stop="expanded = !expanded"
                    class="w-4 h-4 flex items-center justify-center text-gray-500 hover:text-gray-300 transition-colors">
                <svg class="w-3 h-3 ps-category-tree-toggle transition-transform duration-150"
                     :class="{ 'rotate-90': expanded }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        @else
            <span class="w-4"></span>
        @endif

        {{-- Checkbox --}}
        <label class="flex items-center gap-2 flex-1 cursor-pointer">
            <input type="checkbox"
                   wire:click="toggleCategory({{ $catId }})"
                   @checked($isSelected)
                   class="form-checkbox-dark w-3.5 h-3.5 rounded border-gray-600 text-purple-500 focus:ring-purple-500">

            {{-- Name --}}
            <span class="flex-1 text-sm {{ $isSelected ? 'text-white font-medium' : 'text-gray-300' }}">
                {{ $catName }}
            </span>
        </label>

        {{-- ID badge --}}
        <span class="text-[10px] text-gray-600 font-mono">#{{ $catId }}</span>
    </div>

    {{-- Children (recursive) - shown only when expanded --}}
    @if($hasChildren)
        <div class="ps-category-tree-children" x-show="expanded" x-collapse>
            @foreach($children as $child)
                @include('livewire.products.import.modals.partials.ps-category-tree-item', [
                    'category' => $child,
                    'depth' => $depth + 1
                ])
            @endforeach
        </div>
    @endif
</div>
