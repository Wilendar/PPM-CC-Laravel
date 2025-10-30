{{-- Enhanced Tree Node - Modern Card-Based Design --}}
@php
    $category = $node['category'];
    $hasChildren = !empty($node['children']);
    $isExpanded = $node['expanded'];
    $isSelected = $node['selected'];
    $indentLevel = $level * 24; // 24px per level for better spacing
    $nodeDepthColor = [
        0 => 'from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10',
        1 => 'from-green-50 to-emerald-50 dark:from-green-900/10 dark:to-emerald-900/10',
        2 => 'from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10',
        3 => 'from-orange-50 to-amber-50 dark:from-orange-900/10 dark:to-amber-900/10',
        4 => 'from-red-50 to-rose-50 dark:from-red-900/10 dark:to-rose-900/10'
    ][$level % 5];
@endphp

<div class="tree-node category-container group relative"
     data-category-id="{{ $category->id }}"
     data-level="{{ $level }}"
     style="margin-left: {{ $indentLevel }}px"
     x-data="enhancedTreeNode({{ $category->id }}, {{ $level }})"
     x-init="initializeNode()">

    {{-- Enhanced Node Card --}}
    <div class="relative mb-3 bg-gray-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:scale-[1.02]
                border-2 border-gray-100 dark:border-gray-700 hover:border-blue-200 dark:hover:border-blue-600
                {{ $isSelected ? 'ring-4 ring-blue-500/20 border-blue-500 dark:border-blue-400' : '' }}"
         draggable="true"
         @dragstart="handleDragStart($event)"
         @dragover.prevent="handleDragOver($event)"
         @dragenter.prevent="handleDragEnter($event)"
         @dragleave="handleDragLeave($event)"
         @drop.prevent="handleDrop($event)">

        {{-- Level Indicator Line --}}
        @if($level > 0)
            <div class="absolute -left-6 top-8 w-6 h-px bg-gradient-to-r {{ $nodeDepthColor }}"></div>
            <div class="absolute -left-6 top-0 bottom-0 w-px bg-gradient-to-b {{ $nodeDepthColor }}"></div>
        @endif

        {{-- Node Content --}}
        <div class="flex items-center p-6 space-x-4">
            {{-- Expand/Collapse Button --}}
            <div class="flex-shrink-0">
                @if($hasChildren)
                    <button wire:click="toggleNode({{ $category->id }})"
                            class="group/expand w-10 h-10 flex items-center justify-center rounded-xl
                                   bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30
                                   hover:from-blue-200 hover:to-indigo-200 dark:hover:from-blue-800/50 dark:hover:to-indigo-800/50
                                   border-2 border-blue-200 dark:border-blue-700 hover:border-blue-300 dark:hover:border-blue-600
                                   transition-all duration-300 transform hover:scale-110 shadow-md hover:shadow-lg">
                        @if($isExpanded)
                            <i class="fas fa-chevron-down text-blue-600 dark:text-blue-400 text-sm group-hover/expand:text-blue-700 dark:group-hover/expand:text-blue-300 transition-colors duration-300"></i>
                        @else
                            <i class="fas fa-chevron-right text-blue-600 dark:text-blue-400 text-sm group-hover/expand:text-blue-700 dark:group-hover/expand:text-blue-300 transition-colors duration-300"></i>
                        @endif
                    </button>
                @else
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600">
                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                    </div>
                @endif
            </div>

            {{-- Drag Handle --}}
            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <div class="w-8 h-8 flex items-center justify-center cursor-move rounded-lg
                           bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600
                           hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500
                           border border-gray-600 hover:border-gray-400 dark:hover:border-gray-500
                           transition-all duration-300 transform hover:scale-110">
                    <i class="fas fa-grip-vertical text-gray-500 dark:text-gray-400 text-xs"></i>
                </div>
            </div>

            {{-- Selection Checkbox --}}
            <div class="flex-shrink-0">
                <input type="checkbox"
                       wire:model="selectedCategories"
                       value="{{ $category->id }}"
                       class="w-6 h-6 rounded-xl border-2 border-gray-600
                              text-blue-600 focus:ring-4 focus:ring-blue-500/30 transition-all duration-300
                              transform hover:scale-110">
            </div>

            {{-- Category Icon --}}
            <div class="flex-shrink-0">
                <div class="relative group/icon">
                    <div class="w-16 h-16 bg-gradient-to-br {{ $nodeDepthColor }} rounded-2xl
                               flex items-center justify-center shadow-lg group-hover:shadow-xl
                               border-2 border-white dark:border-gray-600 group-hover/icon:scale-110
                               transition-all duration-300 transform">
                        @if($category->icon)
                            <i class="{{ $category->icon }} text-2xl text-gray-300"></i>
                        @else
                            <i class="fas fa-folder text-2xl text-gray-500 dark:text-gray-400"></i>
                        @endif
                    </div>
                    {{-- Level Badge --}}
                    @if($level > 0)
                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full
                                   flex items-center justify-center text-xs font-bold text-white shadow-md">
                            {{ $level + 1 }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Category Information --}}
            <div class="flex-grow min-w-0 space-y-2">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xl font-bold text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">
                            {{ $category->name }}
                        </h4>
                        @if($category->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">
                                {{ Str::limit($category->description, 100) }}
                            </p>
                        @endif
                    </div>

                    {{-- Enhanced Status & Metrics --}}
                    <div class="flex items-center space-x-3 ml-4">
                        {{-- Product Count --}}
                        @if(($category->products_count ?? 0) > 0)
                            <div class="flex items-center space-x-2 bg-blue-100 dark:bg-blue-900/30 px-4 py-2 rounded-xl border border-blue-200 dark:border-blue-700">
                                <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-bold text-blue-800 dark:text-blue-300">
                                    {{ $category->products_count }} {{ $category->products_count === 1 ? 'produkt' : 'produktów' }}
                                </span>
                            </div>
                        @endif

                        {{-- Primary Products Count --}}
                        @if(($category->primary_products_count ?? 0) > 0)
                            <div class="flex items-center space-x-2 bg-green-100 dark:bg-green-900/30 px-4 py-2 rounded-xl border border-green-200 dark:border-green-700">
                                <i class="fas fa-star text-xs text-green-600 dark:text-green-400"></i>
                                <span class="text-sm font-bold text-green-800 dark:text-green-300">
                                    {{ $category->primary_products_count }} głównych
                                </span>
                            </div>
                        @endif

                        {{-- Children Count --}}
                        @if($hasChildren)
                            <div class="flex items-center space-x-2 bg-purple-100 dark:bg-purple-900/30 px-4 py-2 rounded-xl border border-purple-200 dark:border-purple-700">
                                <i class="fas fa-sitemap text-xs text-purple-600 dark:text-purple-400"></i>
                                <span class="text-sm font-bold text-purple-800 dark:text-purple-300">
                                    {{ count($node['children']) }} {{ count($node['children']) === 1 ? 'podkategoria' : 'podkategorii' }}
                                </span>
                            </div>
                        @endif

                        {{-- Status Badge --}}
                        @if($category->is_active)
                            <div class="flex items-center space-x-2 bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30
                                       px-4 py-2 rounded-xl border-2 border-green-200 dark:border-green-700 shadow-md">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm font-bold text-green-800 dark:text-green-300">Aktywna</span>
                            </div>
                        @else
                            <div class="flex items-center space-x-2 bg-gradient-to-r from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30
                                       px-4 py-2 rounded-xl border-2 border-red-200 dark:border-red-700 shadow-md">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-sm font-bold text-red-800 dark:text-red-300">Nieaktywna</span>
                            </div>
                        @endif

                        {{-- Actions Dropdown --}}
                        @include('livewire.products.categories.partials.enhanced-category-actions', ['category' => $category])
                    </div>
                </div>
            </div>
        </div>

        {{-- Drag Drop Indicator --}}
        <div x-show="isDropTarget"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute inset-0 bg-blue-500/20 border-2 border-blue-500 border-dashed rounded-2xl
                    flex items-center justify-center backdrop-blur-sm"
             style="display: none;">
            <div class="bg-blue-600 text-white px-6 py-3 rounded-xl shadow-lg flex items-center space-x-2">
                <i class="fas fa-download text-lg"></i>
                <span class="font-semibold">Upuść tutaj</span>
            </div>
        </div>
    </div>

    {{-- Enhanced Children Nodes --}}
    @if($hasChildren && $isExpanded)
        <div class="children-container ml-6 space-y-2"
             x-show="true"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 transform -translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 transform -translate-y-4 scale-95">

            {{-- Children Connection Line --}}
            <div class="absolute left-0 top-0 bottom-8 w-px bg-gradient-to-b {{ $nodeDepthColor }} opacity-50 ml-5"></div>

            @foreach($node['children'] as $index => $childNode)
                @include('livewire.products.categories.partials.enhanced-tree-node', [
                    'node' => $childNode,
                    'level' => $level + 1
                ])
            @endforeach
        </div>
    @endif
</div>

{{-- Enhanced Alpine.js Component --}}
<script>
function enhancedTreeNode(categoryId, level) {
    return {
        categoryId: categoryId,
        level: level,
        isDragging: false,
        isDropTarget: false,
        isHovered: false,

        initializeNode() {
            // Add stagger animation delay
            this.$el.style.animationDelay = `${level * 100 + categoryId % 10 * 50}ms`;
            this.$el.classList.add('animate-fade-in-up');
        },

        handleDragStart(event) {
            this.isDragging = true;
            event.dataTransfer.setData('text/plain', this.categoryId);
            event.dataTransfer.setData('application/x-category-level', this.level);

            // Enhanced visual feedback
            event.target.style.opacity = '0.6';
            event.target.style.transform = 'scale(0.95)';

            // Store reference globally
            window.draggedCategoryId = this.categoryId;
            window.draggedCategoryLevel = this.level;

            // Add drag cursor to body
            document.body.style.cursor = 'grabbing';

            console.log('Enhanced drag started:', { categoryId: this.categoryId, level: this.level });
        },

        handleDragOver(event) {
            event.preventDefault();

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

                // Add visual feedback
                event.target.closest('.tree-node').style.transform = 'scale(1.05)';
                event.target.closest('.tree-node').style.boxShadow = '0 25px 50px -12px rgba(59, 130, 246, 0.5)';
            }
        },

        handleDragLeave(event) {
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.isDropTarget = false;

                // Remove visual feedback
                event.target.closest('.tree-node').style.transform = '';
                event.target.closest('.tree-node').style.boxShadow = '';
            }
        },

        handleDrop(event) {
            event.preventDefault();

            const draggedId = parseInt(event.dataTransfer.getData('text/plain'));
            const draggedLevel = parseInt(event.dataTransfer.getData('application/x-category-level'));

            // Clean up visual states
            this.isDropTarget = false;
            this.isDragging = false;

            const nodeElement = event.target.closest('.tree-node');
            nodeElement.style.transform = '';
            nodeElement.style.boxShadow = '';
            nodeElement.style.opacity = '';

            // Reset body cursor
            document.body.style.cursor = '';

            // Don't drop onto self
            if (draggedId === this.categoryId) {
                return;
            }

            console.log('Enhanced drop:', {
                draggedId,
                targetId: this.categoryId,
                draggedLevel,
                targetLevel: this.level
            });

            // Show success feedback
            this.showDropFeedback();

            // Call Livewire method
            @this.call('reorderCategory', draggedId, this.categoryId, 0);

            // Cleanup
            delete window.draggedCategoryId;
            delete window.draggedCategoryLevel;
        },

        handleDragEnd(event) {
            this.isDragging = false;

            // Reset visual states
            event.target.style.opacity = '';
            event.target.style.transform = '';
            document.body.style.cursor = '';

            // Remove any remaining drop indicators
            document.querySelectorAll('.tree-node').forEach(node => {
                node.style.transform = '';
                node.style.boxShadow = '';
            });
        },

        showDropFeedback() {
            // Add success animation
            const nodeElement = this.$el.querySelector('.tree-node > div');
            nodeElement.classList.add('animate-pulse');

            setTimeout(() => {
                nodeElement.classList.remove('animate-pulse');
            }, 1000);
        }
    }
}
</script>

{{-- Node-specific styles --}}
<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.tree-node:hover .children-container::before {
    opacity: 0.8;
}
</style>