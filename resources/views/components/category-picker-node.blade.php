{{-- CategoryPickerNode Component - Recursive hierarchical tree node --}}
{{-- ETAP_07 FAZA 3D - ETAP 2 - Livewire 3.x DEEP FIX 2025-10-15 --}}
{{-- CRITICAL FIX: Remove ALL Livewire directives from nested Blade component --}}
{{-- CRITICAL FIX: Use ONLY Alpine.js for state management --}}
@props(['category', 'context' => 'default', 'selectedCategories' => []])

@php
    // Use level from category data (calculated by CategoryPicker backend)
    $level = $category['level'] ?? 0;
@endphp

<div class="category-picker-node"
     x-data="{
         expanded: {{ $category['has_children'] ? 'false' : 'true' }},
         categoryId: {{ $category['id'] }},
         hasChildren: {{ $category['has_children'] ? 'true' : 'false' }},
         // Check if this category is selected (from parent Alpine state)
         get isSelected() {
             return selectedCategories.includes(this.categoryId);
         }
     }">

    <!-- Node Row -->
    <div class="category-picker-node-row"
         :class="{ 'category-picker-node-expanded': expanded }">

        <!-- Indentation Spacer (NO inline styles) -->
        @if($level > 0)
            <div class="category-indent-spacer category-indent-spacer-{{ min($level, 5) }}"
                 data-level="{{ $level }}"
                 data-debug="Level:{{ $level }} Class:category-indent-spacer-{{ min($level, 5) }}"
                 title="Indent Level: {{ $level }}"></div>
        @endif

        <!-- Expand/Collapse Button -->
        <button @if($category['has_children'])
                    @click="expanded = !expanded"
                @endif
                class="category-picker-node-toggle"
                :class="{ 'invisible': !hasChildren }">
            <svg x-show="!expanded"
                 x-cloak
                 class="w-4 h-4 text-gray-400"
                 fill="currentColor"
                 viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
            <svg x-show="expanded"
                 x-cloak
                 class="w-4 h-4 text-gray-400"
                 fill="currentColor"
                 viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
        </button>

        <!-- Folder Icon -->
        <div class="category-picker-node-icon">
            @if($category['has_children'])
                <svg x-show="!expanded"
                     x-cloak
                     class="w-5 h-5 text-brand-400"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                </svg>
                <svg x-show="expanded"
                     x-cloak
                     class="w-5 h-5 text-brand-400"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" fill-opacity="0.5"></path>
                    <path d="M2 10h16v6a2 2 0 01-2 2H4a2 2 0 01-2-2v-6z"></path>
                </svg>
            @else
                <svg class="w-5 h-5 text-gray-500"
                     fill="currentColor"
                     viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                </svg>
            @endif
        </div>

        <!-- Checkbox -->
        <div class="category-picker-node-checkbox">
            <input type="checkbox"
                   id="category-{{ $context }}-{{ $category['id'] }}"
                   @click="$wire.toggleCategory({{ $category['id'] }})"
                   :checked="isSelected"
                   class="category-picker-checkbox">
        </div>

        <!-- Category Label -->
        <label for="category-{{ $context }}-{{ $category['id'] }}"
               class="category-picker-node-label">
            <span class="category-picker-node-name">{{ $category['name'] }}</span>
            @if($category['has_children'])
                <span class="category-picker-node-count">
                    ({{ count($category['children']) }})
                </span>
            @endif
        </label>
    </div>

    <!-- Children (Recursive) -->
    @if($category['has_children'])
        <div x-show="expanded"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-1"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-1"
             class="category-picker-node-children">
            @foreach($category['children'] as $child)
                @php
                    // CRITICAL FIX: Ensure child has level set (backend should provide this)
                    if (!isset($child['level'])) {
                        $child['level'] = $level + 1;
                        \Log::warning('CategoryPicker: Child missing level, calculated', [
                            'parent_id' => $category['id'],
                            'child_id' => $child['id'],
                            'parent_level' => $level,
                            'calculated_level' => $child['level'],
                        ]);
                    }
                @endphp
                <x-category-picker-node
                    :category="$child"
                    :context="$context"
                    :selected-categories="$selectedCategories"
                />
            @endforeach
        </div>
    @endif
</div>
