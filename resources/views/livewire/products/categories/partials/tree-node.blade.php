{{-- Tree Node Partial - Recursive Category Tree Display --}}
@php
    $category = $node['category'];
    $hasChildren = !empty($node['children']);
    $isExpanded = $node['expanded'];
    $isSelected = $node['selected'];
    $indentLevel = $level * 20; // 20px per level
@endphp

<div class="tree-node"
     data-category-id="{{ $category->id }}"
     data-level="{{ $level }}"
     style="margin-left: {{ $indentLevel }}px">

    {{-- Node Container --}}
    <div class="flex items-center py-2 px-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md group transition-colors
                {{ $isSelected ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
         draggable="true"
         x-data="treeNodeDragDrop({{ $category->id }}, {{ $level }})"
         x-on:dragstart="handleDragStart($event)"
         x-on:dragover.prevent="handleDragOver($event)"
         x-on:dragenter.prevent="handleDragEnter($event)"
         x-on:dragleave="handleDragLeave($event)"
         x-on:drop.prevent="handleDrop($event)">

        {{-- Expand/Collapse Toggle --}}
        <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center">
            @if($hasChildren)
                <button wire:click="toggleNode({{ $category->id }})"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    @if($isExpanded)
                        <i class="fas fa-chevron-down text-xs"></i>
                    @else
                        <i class="fas fa-chevron-right text-xs"></i>
                    @endif
                </button>
            @else
                <div class="w-4 h-4 flex items-center justify-center">
                    <div class="w-1 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                </div>
            @endif
        </div>

        {{-- Drag Handle --}}
        <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center cursor-move opacity-0 group-hover:opacity-100 transition-opacity">
            <i class="fas fa-grip-vertical text-gray-400 text-xs"></i>
        </div>

        {{-- Selection Checkbox --}}
        <div class="flex-shrink-0 mr-3">
            <input type="checkbox"
                   wire:model="selectedCategories"
                   value="{{ $category->id }}"
                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
        </div>

        {{-- Category Icon --}}
        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center mr-3">
            @if($category->icon)
                <i class="{{ $category->icon }} text-gray-600 dark:text-gray-400"></i>
            @else
                <i class="fas fa-folder text-gray-400"></i>
            @endif
        </div>

        {{-- Category Information --}}
        <div class="flex-grow min-w-0">
            <div class="flex items-center space-x-3">
                {{-- Category Name --}}
                <div class="flex-grow min-w-0">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ $category->name }}
                    </h4>
                    @if($category->description)
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ Str::limit($category->description, 80) }}
                        </p>
                    @endif
                </div>

                {{-- Product Count Badge --}}
                <div class="flex-shrink-0">
                    <div class="flex items-center space-x-2">
                        @if(($category->products_count ?? 0) > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $category->products_count }} produktów
                            </span>
                        @endif

                        @if(($category->primary_products_count ?? 0) > 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ $category->primary_products_count }} głównych
                            </span>
                        @endif

                        @if($hasChildren)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                {{ count($node['children']) }} podkategorii
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="flex-shrink-0">
                    @if($category->is_active)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <i class="fas fa-check mr-1"></i>
                            Aktywna
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <i class="fas fa-times mr-1"></i>
                            Nieaktywna
                        </span>
                    @endif
                </div>

                {{-- Actions Dropdown --}}
                <div class="flex-shrink-0">
                    @include('livewire.products.categories.partials.category-actions', ['category' => $category])
                </div>
            </div>
        </div>
    </div>

    {{-- Children Nodes (Recursive) --}}
    @if($hasChildren && $isExpanded)
        <div class="children-container"
             x-show="true"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2">

            @foreach($node['children'] as $childNode)
                @include('livewire.products.categories.partials.tree-node', [
                    'node' => $childNode,
                    'level' => $level + 1
                ])
            @endforeach
        </div>
    @endif
</div>

{{-- Alpine.js Tree Node Drag & Drop Component --}}
<script>
function treeNodeDragDrop(categoryId, level) {
    return {
        categoryId: categoryId,
        level: level,
        isDragging: false,
        isDropTarget: false,

        handleDragStart(event) {
            this.isDragging = true;
            event.dataTransfer.setData('text/plain', this.categoryId);
            event.dataTransfer.setData('application/x-category-level', this.level);

            // Visual feedback
            event.target.classList.add('opacity-50');

            // Store reference for other components
            window.draggedCategoryId = this.categoryId;
            window.draggedCategoryLevel = this.level;

            console.log('Drag started:', { categoryId: this.categoryId, level: this.level });
        },

        handleDragOver(event) {
            event.preventDefault();

            // Only allow drop if not dragging onto self or descendant
            const draggedId = parseInt(event.dataTransfer.getData('text/plain'));
            if (draggedId === this.categoryId) {
                return false;
            }

            event.dataTransfer.dropEffect = 'move';
        },

        handleDragEnter(event) {
            event.preventDefault();

            const draggedId = parseInt(event.dataTransfer.getData('text/plain'));
            if (draggedId !== this.categoryId) {
                this.isDropTarget = true;
                event.target.closest('.tree-node').classList.add('bg-blue-100', 'dark:bg-blue-900/30');
            }
        },

        handleDragLeave(event) {
            // Only remove highlight if actually leaving the element
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.isDropTarget = false;
                event.target.closest('.tree-node').classList.remove('bg-blue-100', 'dark:bg-blue-900/30');
            }
        },

        handleDrop(event) {
            event.preventDefault();

            const draggedId = parseInt(event.dataTransfer.getData('text/plain'));
            const draggedLevel = parseInt(event.dataTransfer.getData('application/x-category-level'));

            // Clean up visual states
            this.isDropTarget = false;
            event.target.closest('.tree-node').classList.remove('bg-blue-100', 'dark:bg-blue-900/30');

            // Don't drop onto self
            if (draggedId === this.categoryId) {
                return;
            }

            // Calculate new parent and sort order
            const newParentId = this.categoryId;
            const newSortOrder = this.calculateNewSortOrder();

            console.log('Drop:', {
                draggedId,
                newParentId,
                newSortOrder,
                targetLevel: this.level
            });

            // Call Livewire method to update category
            @this.call('reorderCategory', draggedId, newParentId, newSortOrder);

            // Clean up global state
            delete window.draggedCategoryId;
            delete window.draggedCategoryLevel;
        },

        handleDragEnd(event) {
            // Clean up drag state
            this.isDragging = false;
            event.target.classList.remove('opacity-50');

            // Remove any remaining drop target highlights
            document.querySelectorAll('.tree-node').forEach(node => {
                node.classList.remove('bg-blue-100', 'dark:bg-blue-900/30');
            });
        },

        calculateNewSortOrder() {
            // Find siblings and calculate appropriate sort order
            const parentElement = this.$el.closest('.children-container');
            if (!parentElement) return 0;

            const siblings = parentElement.querySelectorAll(':scope > .tree-node');
            return siblings.length; // Simple increment-based ordering
        }
    }
}
</script>