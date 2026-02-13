{{-- Category Preview Tree Node - Recursive partial --}}
{{-- $node: array{id, name, level, status, children, prestashop_id, has_new_descendants} --}}
{{-- $level: int (depth for indentation) --}}
{{-- $expandedNodes: array of expanded node IDs --}}
{{-- $selectedCategoryIds: array of selected category IDs --}}
{{-- $skipCategories: bool --}}

@php
    $nodeId = $node['id'];
    $hasChildren = !empty($node['children']);
    $isNew = ($node['status'] ?? 'existing') === 'to_add';
    $hasNewDescendants = $node['has_new_descendants'] ?? false;
    $isProductCategory = $node['is_product_category'] ?? false;
    $isExpanded = in_array($nodeId, $expandedNodes) || ($isNew && $hasChildren);
    $isNumericId = is_int($nodeId);
    $prestashopId = $node['prestashop_id'] ?? null;
    $levelClamped = min($level, 5);
@endphp

<div class="category-tree-ppm-node category-tree-connector" data-level="{{ $level }}" wire:key="tree-node-{{ $nodeId }}">
    <div class="category-preview-row flex items-center gap-1 py-1 px-2 rounded transition-colors duration-150
                {{ $isNew ? 'bg-emerald-500/10 border border-emerald-500/30' : 'hover:bg-gray-700/50' }}"
         @if(!$isNew && $hasChildren)
             wire:click="toggleNode({{ $isNumericId ? $nodeId : "'{$nodeId}'" }})"
         @endif
         role="treeitem"
         aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">

        {{-- Indentation spacer - CSS classes instead of inline style --}}
        @if($level > 0)
            <span class="category-preview-indent" data-level="{{ $levelClamped }}"></span>
        @endif

        {{-- Expand/Collapse toggle --}}
        @if($hasChildren)
            <button type="button"
                    wire:click.stop="toggleNode({{ $isNumericId ? $nodeId : "'{$nodeId}'" }})"
                    class="category-preview-chevron {{ $isExpanded ? 'category-preview-chevron--expanded' : '' }}"
                    title="{{ $isExpanded ? 'Zwi' . 'n' : 'Rozwi' . 'n' }}">
                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        @else
            <span class="category-preview-leaf-dot">
                <span class="w-1.5 h-1.5 rounded-full {{ $isNew ? 'bg-emerald-400' : 'bg-gray-600' }}"></span>
            </span>
        @endif

        {{-- Checkbox for new categories --}}
        @if($isNew && $prestashopId)
            <input type="checkbox"
                   wire:click.stop="toggleCategory({{ (int) $prestashopId }})"
                   @checked(in_array((int) $prestashopId, $selectedCategoryIds))
                   @disabled($skipCategories)
                   class="checkbox-enterprise flex-shrink-0" />
        @endif

        {{-- Level-colored folder/file icon --}}
        @if($hasChildren)
            <span class="category-preview-folder-icon category-preview-folder-icon--level-{{ $levelClamped }}">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    @if($isExpanded)
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v1H7a3 3 0 00-2.83 2H2V6z"/>
                        <path d="M4 12v2a2 2 0 002 2h8.586A2 2 0 0017.414 15l1.414-5H6a1 1 0 00-.975.8L4 12z"/>
                    @else
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                    @endif
                </svg>
            </span>
        @else
            <span class="category-preview-file-icon {{ $isNew ? 'text-emerald-400' : 'text-gray-500' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </span>
        @endif

        {{-- Category name --}}
        <span class="text-sm truncate {{ $isNew ? 'text-emerald-300 font-medium' : 'text-gray-300' }}">
            {{ $node['name'] }}
        </span>

        {{-- Status badges --}}
        @if($isNew)
            <span class="ml-auto flex-shrink-0 category-preview-badge category-preview-badge--new">
                do dodania
            </span>
        @elseif($hasNewDescendants)
            <span class="ml-auto flex-shrink-0 category-preview-badge category-preview-badge--has-new">
                zawiera nowe
            </span>
        @endif

        {{-- Product assignment badge --}}
        @if($isProductCategory)
            <span class="{{ !$isNew && !$hasNewDescendants ? 'ml-auto' : '' }} flex-shrink-0 category-preview-badge category-preview-badge--product">
                produkt
            </span>
        @endif
    </div>

    {{-- Children (recursive) with expand/collapse animation --}}
    @if($hasChildren && $isExpanded)
        <div class="category-preview-children" wire:key="tree-children-{{ $nodeId }}">
            @foreach($node['children'] as $childNode)
                @include('livewire.components.partials.category-preview-tree-node', [
                    'node' => $childNode,
                    'level' => $level + 1,
                    'expandedNodes' => $expandedNodes,
                    'selectedCategoryIds' => $selectedCategoryIds,
                    'skipCategories' => $skipCategories,
                ])
            @endforeach
        </div>
    @endif
</div>
