{{--
    Comparison Tree Node Component
    ETAP_07f: Import Modal Redesign - ProductForm Style

    Displays a category comparison node with status indicator in ProductForm tree style:
    - 'both' (green): Category exists in both PPM and PrestaShop (synced)
    - 'prestashop_only' (orange): Only in PrestaShop (to add)
    - 'ppm_only' (red): Only in PPM (to remove)

    @props
    - node: Category comparison node data
    - level: Current depth level (0-4)
--}}
@props(['node', 'level' => 0])

@php
    $status = $node['status'] ?? 'both';
    $hasChildren = !empty($node['children']);

    // Status-based styling (ProductForm-inspired)
    $statusTextColors = [
        'both' => 'text-green-400',
        'prestashop_only' => 'text-orange-400',
        'ppm_only' => 'text-red-400',
    ];

    $statusBgColors = [
        'both' => 'bg-green-500/10 border-green-500/30',
        'prestashop_only' => 'bg-orange-500/10 border-orange-500/30',
        'ppm_only' => 'bg-red-500/10 border-red-500/30',
    ];

    $statusLabels = [
        'both' => 'Zsynchronizowana',
        'prestashop_only' => 'Do dodania',
        'ppm_only' => 'Tylko w PPM',
    ];

    $statusBadgeClasses = [
        'both' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'prestashop_only' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'ppm_only' => 'bg-red-500/20 text-red-400 border-red-500/30',
    ];
@endphp

{{-- ProductForm-style tree item with Alpine.js state management --}}
<div x-data="{ collapsed: {{ $level < 2 ? 'false' : 'true' }} }"
     wire:key="comparison-{{ $node['prestashop_id'] ?? $node['id'] ?? uniqid() }}"
     class="comparison-tree-node">

    {{-- Category Row - ProductForm style layout --}}
    <div class="category-tree-row flex items-center space-x-2 py-1.5 px-2 rounded-lg transition-all duration-150 hover:bg-gray-700/20 {{ $statusBgColors[$status] ?? '' }} border"
         style="padding-left: {{ $level * 1.5 }}rem;">

        {{-- Collapse/Expand chevron (only if has children) - ProductForm style --}}
        @if($hasChildren)
            <button type="button"
                    @click="collapsed = !collapsed"
                    class="text-gray-500 hover:text-gray-300 transition-transform duration-200 p-0.5"
                    :class="collapsed ? 'rotate-0' : 'rotate-90'">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </button>
        @else
            {{-- Spacer for alignment when no children --}}
            <span class="w-5"></span>
        @endif

        {{-- Status Icon based on sync state --}}
        <span class="flex-shrink-0">
            @if($status === 'both')
                {{-- Checkmark for synced --}}
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            @elseif($status === 'prestashop_only')
                {{-- Plus for to add --}}
                <svg class="w-4 h-4 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                </svg>
            @else
                {{-- X for ppm_only --}}
                <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            @endif
        </span>

        {{-- Hierarchy prefix (ProductForm style └─) --}}
        @if($level > 0)
            <span class="category-tree-icon text-gray-500 text-sm">└─</span>
        @endif

        {{-- Category Name --}}
        <span class="flex-1 text-sm font-medium {{ $statusTextColors[$status] ?? 'text-gray-300' }}">
            {{ $node['name'] }}
        </span>

        {{-- Status Badge --}}
        <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $statusBadgeClasses[$status] ?? 'bg-gray-500/20 text-gray-400' }}">
            {{ $statusLabels[$status] ?? 'Nieznany' }}
        </span>

        {{-- Product Count (if available) --}}
        @if(isset($node['product_count_ppm']) && $node['product_count_ppm'] > 0)
            <span class="px-2 py-0.5 rounded text-xs bg-gray-700/50 text-gray-400 border border-gray-600/30">
                {{ $node['product_count_ppm'] }} prod.
            </span>
        @endif

        {{-- IDs for debugging (smaller, muted) --}}
        <span class="text-xs text-gray-600 tabular-nums">
            @if($node['id'])
                <span class="mr-1">PPM:{{ $node['id'] }}</span>
            @endif
            @if($node['prestashop_id'])
                <span>PS:{{ $node['prestashop_id'] }}</span>
            @endif
        </span>
    </div>

    {{-- Children (recursive) with ProductForm-style animation --}}
    @if($hasChildren)
        <div x-show="!collapsed"
             x-transition.opacity.duration.100ms
             class="mt-0.5">
            @foreach($node['children'] as $child)
                <x-comparison-tree-node
                    :node="$child"
                    :level="$level + 1"
                />
            @endforeach
        </div>
    @endif
</div>
