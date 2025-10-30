{{-- Category Tree Item Component (Recursive) - COMPACT REDESIGN --}}
{{-- ETAP_07 FAZA 3D: Category Preview System --}}
@props(['category', 'level' => 0])

@php
    // Ensure category data exists
    $categoryId = $category['prestashop_id'] ?? 0;
    $ppmId = $category['ppm_id'] ?? null; // PPM ID for manual categories
    $isManual = $category['is_manual'] ?? false; // Flag for manual (non-PrestaShop) categories
    $categoryName = $category['name'] ?? 'Unknown';
    $levelDepth = $category['level_depth'] ?? 0;
    $isActive = $category['active'] ?? $category['is_active'] ?? true;
    $idParent = $category['id_parent'] ?? 0;
    $children = $category['children'] ?? [];
    $existsInPpm = $category['exists_in_ppm'] ?? false;

    // Unique ID for scroll target (use ppm_id for manual, prestashop_id for imports)
    $uniqueId = $isManual && $ppmId ? "category-ppm-{$ppmId}" : "category-ps-{$categoryId}";

    // Visual indentation based on level (24px per level)
    $paddingLeft = $level * 24;

    // Icon based on exists_in_ppm flag
    if ($existsInPpm) {
        $icon = '✅'; // Already exists
        $iconClass = 'text-green-400';
        $textClass = 'text-gray-500';
        $disabled = true;
    } else {
        $icon = match($levelDepth) {
            0 => '📁', // Root level
            1 => '📂', // Second level
            default => '📄' // Leaf categories
        };
        $iconClass = 'text-brand-400';
        $textClass = 'text-white';
        $disabled = false;
    }
@endphp

<div class="category-tree-item-compact" id="{{ $uniqueId }}" data-category-id="{{ $categoryId }}" data-ppm-id="{{ $ppmId ?? '' }}" data-is-manual="{{ $isManual ? '1' : '0' }}">
    <div class="flex items-center gap-2 py-1.5 px-2 rounded-lg hover:bg-gray-700/30 transition-colors duration-150 {{ $disabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }} group">
        <!-- Visual Hierarchy Indicators (horizontal bars like in Categories panel) -->
        @if($level > 0)
            <div class="flex items-center flex-shrink-0">
                @for($i = 0; $i < $level; $i++)
                    <span class="text-gray-600 text-sm">—</span>
                @endfor
            </div>
        @endif

        <!-- Checkbox -->
        <label class="flex items-center gap-2 flex-1 cursor-pointer">
        <!-- Checkbox - CRITICAL FIX: wire:model doesn't work in nested components, use Alpine.js + Livewire -->
        {{-- Use ppm_id for manual categories, prestashop_id for imports --}}
        @php
            $checkboxId = $isManual && $ppmId ? $ppmId : $categoryId;
        @endphp
        <input type="checkbox"
               x-data
               @click="$wire.toggleCategory({{ $checkboxId }})"
               {{ $disabled ? 'disabled' : '' }}
               @checked(in_array($checkboxId, $this->selectedCategoryIds))
               class="w-4 h-4 rounded border-gray-600 text-brand-600 focus:ring-brand-500 focus:ring-offset-gray-900 flex-shrink-0 {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
               style="accent-color: #e0ac7e;">

        <!-- Icon -->
        <span class="flex-shrink-0 text-base {{ $iconClass }}">
            {{ $icon }}
        </span>

        <!-- Category Info - COMPACT -->
        <div class="flex-1 min-w-0 flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium {{ $textClass }} truncate">
                {{ $categoryName }}
            </span>

            <!-- Badges - COMPACT -->
            <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-700/50 text-gray-400 whitespace-nowrap">
                L{{ $levelDepth }}
            </span>

            @if($existsInPpm)
                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-green-900/30 text-green-400 whitespace-nowrap">
                    Istnieje
                </span>
            @else
                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-blue-900/30 text-blue-400 whitespace-nowrap">
                    Nowa
                </span>
            @endif

            @if(!$isActive)
                <span class="px-1.5 py-0.5 text-xs font-semibold rounded bg-gray-700 text-gray-500 whitespace-nowrap">
                    Nieaktywna
                </span>
            @endif

            <!-- PrestaShop ID - COMPACT -->
            <span class="text-xs text-gray-500">
                PS:{{ $categoryId }}
            </span>
        </div>
        </label>
    </div>

    {{-- Recursive Children Rendering --}}
    @if(!empty($children))
        <div class="category-children">
            @foreach($children as $child)
                <x-category-tree-item
                    :category="$child"
                    :level="$level + 1"
                    wire:key="cat-child-{{ $child['prestashop_id'] ?? 'unknown' }}-{{ $categoryId }}"
                />
            @endforeach
        </div>
    @endif
</div>
