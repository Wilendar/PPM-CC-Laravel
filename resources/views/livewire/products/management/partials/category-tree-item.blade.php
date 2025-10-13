{{-- Recursive Category Tree Item --}}
{{-- Parameters: $category, $level, $context (activeShopId ?? 'default') --}}

@php
    $hasChildren = $category->children && $category->children->count() > 0;
@endphp

<div x-data="{ collapsed: false }" wire:key="category-tree-{{ $context }}-{{ $category->id }}">
    <div class="flex items-center space-x-2 py-1"
         wire:key="category-row-{{ $context }}-{{ $category->id }}"
         style="padding-left: {{ $level * 1.5 }}rem;">

        {{-- Collapse/Expand chevron (only if has children) --}}
        @if($hasChildren)
            <button
                type="button"
                @click="collapsed = !collapsed"
                class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-transform duration-200"
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

        <input
            wire:click="toggleCategory({{ $category->id }})"
            type="checkbox"
            id="category_{{ $context }}_{{ $category->id }}"
            {{ in_array($category->id, $this->getCategoriesForContext($activeShopId)) ? 'checked' : '' }}
            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >

        <label for="category_{{ $context }}_{{ $category->id }}" class="flex-1 text-sm text-gray-700 dark:text-gray-300">
            <span class="text-gray-400 dark:text-gray-500 mr-1">{{ $level > 0 ? '└─' : '' }}</span>
            {{ $category->name }}
        </label>

        @if(in_array($category->id, $this->getCategoriesForContext($activeShopId)))
            <button
                wire:click="setPrimaryCategory({{ $category->id }})"
                type="button"
                class="px-2 py-1 text-xs rounded {{ $this->getPrimaryCategoryForContext($activeShopId) == $category->id ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
            >
                {{ $this->getPrimaryCategoryForContext($activeShopId) == $category->id ? 'Główna' : 'Ustaw główną' }}
            </button>
        @endif
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
                    'context' => $context
                ])
            @endforeach
        </div>
    @endif
</div>
