{{-- Recursive Category Tree Item --}}
{{-- Parameters: $category, $level, $context (activeShopId ?? 'default'), $expandedCategoryIds --}}

@php
    $hasChildren = $category->children && $category->children->count() > 0;
    // FIX #2 2025-11-21: Track category selection for reactive button visibility
    $isSelected = in_array($category->id, $this->getPrestaShopCategoryIdsForContext($activeShopId));
    $isPrimary = $this->getPrimaryPrestaShopCategoryIdForContext($activeShopId) == $category->id;
    // FIX #14 2025-11-21: Performance - expand ONLY if in expandedCategoryIds list
    $shouldExpand = in_array($category->id, $expandedCategoryIds ?? []);
@endphp

<div x-data="{
    collapsed: {{ $shouldExpand ? 'false' : 'true' }},
    isSelected: {{ $isSelected ? 'true' : 'false' }},
    isPrimary: {{ $isPrimary ? 'true' : 'false' }}
}"
x-on:primary-category-changed.window="
    isPrimary = ($event.detail.categoryId === {{ $category->id }})
"
wire:key="category-tree-{{ $context }}-{{ $category->id }}">
    <div class="flex items-center space-x-2 py-1"
         wire:key="category-row-{{ $context }}-{{ $category->id }}"
         style="padding-left: {{ $level * 1.5 }}rem;">

        {{-- Collapse/Expand chevron (only if has children) --}}
        @if($hasChildren)
            <button
                type="button"
                @click="collapsed = !collapsed"
                class="text-gray-500 hover:text-gray-300 transition-transform duration-200"
                :class="collapsed ? 'rotate-0' : 'rotate-90'"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        @else
            {{-- Spacer for alignment when no children --}}
            <span class="w-4"></span>
        @endif

        {{-- FIX #4 2025-11-21: Disable checkbox during save/pending --}}
        {{-- FIX #8 2025-11-21: REMOVED wire:loading.attr="disabled" (conflicts with wire:poll.5s) --}}
        {{-- FIX #13 2025-11-21: Use Alpine.js :disabled binding with REACTIVE PROPERTY (not method!) --}}
        <input
            type="checkbox"
            id="category_{{ $context }}_{{ $category->id }}"
            x-model="isSelected"
            @change="$wire.toggleCategory({{ $category->id }})"
            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="$wire.categoryEditingDisabled"
        >

        {{-- FIX #13 2025-11-21: Use Alpine.js :class binding with REACTIVE PROPERTY --}}
        <label for="category_{{ $context }}_{{ $category->id }}"
               class="flex-1 category-tree-label"
               :class="$wire.categoryEditingDisabled ? 'opacity-50 cursor-not-allowed' : ''">
            <span class="category-tree-icon mr-1">{{ $level > 0 ? '└─' : '' }}</span>
            {{ $category->name }}
        </label>

        {{-- FIX #2 2025-11-21: Use Alpine.js x-show for reactive button visibility --}}
        {{-- FIX #4 2025-11-21: Disable button during save/pending --}}
        {{-- FIX #8 2025-11-21: REMOVED wire:loading.attr="disabled" (conflicts with wire:poll.5s) --}}
        {{-- FIX #13 2025-11-21: Use Alpine.js :disabled binding with REACTIVE PROPERTY --}}
        {{-- FIX 2025-11-24 (v4): Alpine.js reactive isPrimary synced via Livewire dispatch event --}}
        {{-- Resolves conflict: Fix #1 (PHP expression needs re-render) vs Fix #3 (static wire:key prevents re-render) --}}
        <button
            x-show="isSelected"
            @click="$wire.setPrimaryCategory({{ $category->id }})"
            type="button"
            :class="isPrimary ? 'category-primary-btn' : 'category-set-primary-btn'"
            class="px-2 py-1 text-xs rounded disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="$wire.categoryEditingDisabled"
            x-text="isPrimary ? 'Główna' : 'Ustaw główną'"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 transform scale-95"
        ></button>
    </div>

    {{-- Recursively render children with collapse/expand animation --}}
    @if($hasChildren)
        <div x-show="!collapsed"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            @foreach($category->children->sortBy('sort_order') as $child)
                @include('livewire.products.management.partials.category-tree-item', [
                    'category' => $child,
                    'level' => $level + 1,
                    'context' => $context,
                    'expandedCategoryIds' => $expandedCategoryIds
                ])
            @endforeach
        </div>
    @endif
</div>
