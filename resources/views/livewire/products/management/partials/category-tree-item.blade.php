{{-- Recursive Category Tree Item --}}
{{-- Parameters: $category, $level, $context (activeShopId ?? 'default'), $expandedCategoryIds --}}
{{-- ETAP_07b FAZA 4.2: Added search filtering and toggle-all support --}}
{{-- ETAP_07b FAZA 4.2.3: Inline category creation via Livewire (PERFORMANCE FIX) --}}
{{-- PERFORMANCE FIX 2025-11-26: Removed Alpine functions - uses Livewire single source of truth --}}

@php
    $hasChildren = $category->children && $category->children->count() > 0;
    // FIX #2 2025-11-21: Track category selection for reactive button visibility
    $isSelected = in_array($category->id, $this->getPrestaShopCategoryIdsForContext($activeShopId));
    $isPrimary = $this->getPrimaryPrestaShopCategoryIdForContext($activeShopId) == $category->id;
    // FIX #14 2025-11-21: Performance - expand ONLY if in expandedCategoryIds list
    $shouldExpand = in_array($category->id, $expandedCategoryIds ?? []);
    // FAZA 4.2: Prepare category name for search matching (lowercase)
    $categoryNameLower = mb_strtolower($category->name);
    // PERFORMANCE FIX 2025-11-27: Removed $showInlineFormHere - form now controlled by Alpine.js (no Livewire re-render!)
    // 2025-11-26: Check if category is pending (negative ID or is_pending flag)
    $isPendingCategory = $category->id < 0 || (isset($category->is_pending) && $category->is_pending);
    // 2025-11-26: Check if category is marked for deletion (deferred delete)
    $isMarkedForDeletion = $category->id > 0 && $this->isCategoryMarkedForDeletion($category->id, (string)$context);
    // 2025-11-26: Check if PARENT is marked for deletion (to hide undo button on children)
    // FIX 2025-11-27: Pass context to use correct category tree (PPM vs PrestaShop)
    $parentId = $this->findParentCategoryId($category->id, (string)$context);
    $parentIsMarkedForDeletion = $parentId && $this->isCategoryMarkedForDeletion($parentId, (string)$context);
@endphp

{{-- PERFORMANCE FIX: Minimal Alpine x-data - NO FUNCTIONS, only reactive state --}}
{{-- 2025-11-26: Added newCreated state for scroll/highlight UX --}}
{{-- 2025-11-26: Added markedForDeletion state for deferred deletion --}}
{{-- PERFORMANCE FIX 2025-11-27: Added showInlineForm for pure Alpine toggle (no Livewire re-render!) --}}
{{-- PERFORMANCE FIX 2025-11-27: Added inlineName for pure Alpine input (no wire:model.live lag!) --}}
<div x-data="{
    collapsed: {{ $shouldExpand ? 'false' : 'true' }},
    isSelected: {{ $isSelected ? 'true' : 'false' }},
    isPrimary: {{ $isPrimary ? 'true' : 'false' }},
    hidden: false,
    highlighted: false,
    newCreated: false,
    markedForDeletion: {{ $isMarkedForDeletion ? 'true' : 'false' }},
    parentMarkedForDeletion: {{ $parentIsMarkedForDeletion ? 'true' : 'false' }},
    showInlineForm: false,
    inlineName: '',
    categoryName: '{{ addslashes($categoryNameLower) }}',
    context: '{{ $context }}',
    level: {{ $level }},
    categoryId: {{ $category->id }}
}"
{{-- PERFORMANCE FIX 2025-11-27: Consolidated 7 window listeners into 1 (86% reduction) --}}
x-on:category-event.window="
    const d = $event.detail;
    const ctx = String(d.context);
    const myCtx = String(context);

    switch(d.type) {
        case 'primary-changed':
            // FIX 2025-11-28: Check context to ensure only ONE primary badge per context
            if (ctx === myCtx || !d.context) {
                isPrimary = (d.categoryId === {{ $category->id }});
            }
            break;

        case 'created-scroll':
            if (d.categoryId === categoryId && ctx === myCtx) {
                newCreated = true;
                isSelected = true;
                collapsed = false;
                $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                setTimeout(() => { newCreated = false; }, 3000);
            }
            break;

        case 'toggle-all':
            if (ctx === myCtx) {
                collapsed = d.expanded ? false : (level >= 2);
            }
            break;

        case 'search':
            if (ctx === myCtx) {
                const query = d.query.toLowerCase().trim();
                if (query === '') {
                    hidden = false;
                    highlighted = false;
                } else {
                    const matches = categoryName.includes(query);
                    highlighted = matches;
                    hidden = !matches;
                    if (matches) collapsed = false;
                }
            }
            break;

        case 'clear-all':
            if (ctx === myCtx) {
                isSelected = false;
                isPrimary = false;
            }
            break;

        case 'marked-for-deletion':
            if (Number(d.categoryId) === Number(categoryId) && ctx === myCtx) {
                markedForDeletion = true;
                isSelected = false;
                isPrimary = false;
            }
            break;

        case 'child-marked-for-deletion':
            // FIX 2025-11-28: Handle children when parent is marked
            if (Number(d.categoryId) === Number(categoryId) && ctx === myCtx) {
                markedForDeletion = true;
                parentMarkedForDeletion = true;
                isSelected = false;
                isPrimary = false;
            }
            break;

        case 'unmarked-for-deletion':
            if (Number(d.categoryId) === Number(categoryId) && ctx === myCtx) {
                markedForDeletion = false;
                parentMarkedForDeletion = false;
            }
            break;

        case 'child-unmarked-for-deletion':
            // FIX 2025-11-28: Handle children when parent is unmarked
            if (Number(d.categoryId) === Number(categoryId) && ctx === myCtx) {
                markedForDeletion = false;
                parentMarkedForDeletion = false;
            }
            break;

        case 'inline-form-open':
            // PERFORMANCE FIX 2025-11-27: Pure Alpine form toggle (no Livewire re-render!)
            // Close ALL forms, then open only the one that matches
            if (Number(d.categoryId) === Number(categoryId) && ctx === myCtx) {
                showInlineForm = true;
                $nextTick(() => { const inp = $el.querySelector('input'); if (inp) inp.focus(); });
            } else {
                showInlineForm = false;
            }
            break;

        case 'inline-form-close':
            // Close all inline forms
            showInlineForm = false;
            break;
    }
"
:class="{ 'category-hidden': hidden, 'category-highlighted': highlighted, 'category-new-created': newCreated, 'category-pending': {{ $isPendingCategory ? 'true' : 'false' }}, 'category-marked-for-deletion': markedForDeletion || parentMarkedForDeletion }"
wire:key="category-tree-{{ $context }}-{{ $category->id }}-{{ $isMarkedForDeletion ? 'd' : '' }}{{ $parentIsMarkedForDeletion ? 'p' : '' }}">
    <div class="category-tree-row flex items-center space-x-2 py-1"
         wire:key="category-row-{{ $context }}-{{ $category->id }}-{{ $isMarkedForDeletion ? 'd' : '' }}{{ $parentIsMarkedForDeletion ? 'p' : '' }}"
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
        {{-- FIX 2025-11-26: Disable checkbox when category is marked for deletion --}}
        {{-- FIX 2025-11-27: Also disable when PARENT is marked for deletion (children inherit status) --}}
        <input
            type="checkbox"
            id="category_{{ $context }}_{{ $category->id }}"
            x-model="isSelected"
            @change="$wire.toggleCategory({{ $category->id }})"
            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="$wire.categoryEditingDisabled || markedForDeletion || parentMarkedForDeletion"
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
        {{-- FIX 2025-11-26: Hide button when category is marked for deletion --}}
        {{-- FIX 2025-11-27: Also hide when PARENT is marked for deletion (children inherit status) --}}
        {{-- PERFORMANCE FIX 2025-11-27: Removed heavy transitions (scale causes reflow) --}}
        {{-- OPTIMISTIC UI FIX 2025-11-28: Dispatch event IMMEDIATELY on click, then call server --}}
        {{-- This eliminates race condition when clicking fast - UI updates instantly, server confirms later --}}
        <button
            x-show="isSelected && !markedForDeletion && !parentMarkedForDeletion"
            @click="
                window.dispatchEvent(new CustomEvent('category-event', {
                    detail: { type: 'primary-changed', categoryId: {{ $category->id }}, context: context }
                }));
                $wire.setPrimaryCategory({{ $category->id }})
            "
            type="button"
            :class="isPrimary ? 'category-primary-btn' : 'category-set-primary-btn'"
            class="px-2 py-1 text-xs rounded disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="$wire.categoryEditingDisabled"
            x-text="isPrimary ? 'Główna' : 'Ustaw główną'"
            x-transition.opacity.duration.100ms
        ></button>

        {{-- FAZA 4.2.3 PERF FIX: Inline category creation button (+) --}}
        {{-- PERFORMANCE FIX 2025-11-27: Pure Alpine toggle - NO Livewire call, NO re-render! --}}
        {{-- FIX 2025-11-27: Hide when category OR parent is marked for deletion --}}
        <button
            x-show="!markedForDeletion && !parentMarkedForDeletion && !showInlineForm"
            @click="window.dispatchEvent(new CustomEvent('category-event', { detail: { type: 'inline-form-open', categoryId: {{ $category->id }}, context: '{{ $context }}' } }))"
            type="button"
            class="inline-category-add-btn ml-1 p-1 text-gray-400 hover:text-green-400 hover:bg-green-900/30 rounded transition-colors"
            title="Dodaj podkategorie"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>

        {{-- 2025-11-26: Delete button for ALL categories (visible on hover) --}}
        {{-- For pending categories: removes from pending queue immediately --}}
        {{-- For real categories: marks for deletion (deferred until Save) --}}
        {{-- For marked categories: clicking again unmarks (undo) --}}
        {{-- 2025-11-26: Hide button entirely for children when parent is marked (they can't undo anyway) --}}
        <button
            x-show="!parentMarkedForDeletion"
            @if($isPendingCategory)
                wire:click="removePendingCategory({{ $category->id }}, '{{ $context }}')"
                wire:target="removePendingCategory"
                title="Usuń oczekującą kategorię"
            @else
                wire:click="markCategoryForDeletion({{ $category->id }}, '{{ $context }}')"
                wire:target="markCategoryForDeletion"
                x-bind:title="markedForDeletion ? 'Cofnij usuniecie' : 'Oznacz do usuniecia'"
            @endif
            type="button"
            class="category-delete-btn ml-1 p-1 text-gray-400 hover:text-red-400 hover:bg-red-900/30 rounded transition-colors"
            wire:loading.attr="disabled"
        >
            @if($isPendingCategory)
                {{-- Trash icon for pending (actual delete) --}}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            @else
                {{-- FIX 2025-11-26: Wrap SVGs in span (x-show on SVG causes cloneNode error) --}}
                {{-- Trash icon when NOT marked for deletion --}}
                <span x-show="!markedForDeletion" class="inline-flex">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </span>
                {{-- Undo/restore icon when marked for deletion (hidden if parent is also marked) --}}
                {{-- 2025-11-26: Hide undo for children when parent is marked - they can't be unmarked anyway --}}
                <span x-show="markedForDeletion && !parentMarkedForDeletion" x-cloak class="inline-flex">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </span>
            @endif
        </button>

        {{-- 2025-11-26: Badge for categories marked for deletion --}}
        {{-- FIX 2025-11-27: Show badge also for children when parent is marked --}}
        @if(!$isPendingCategory)
            <span x-show="markedForDeletion || parentMarkedForDeletion"
                  x-transition
                  class="category-deletion-badge">
                do usuniecia
            </span>
        @endif
    </div>

    {{-- PERFORMANCE FIX 2025-11-27: Pure Alpine form - NO Livewire re-render when opening! --}}
    {{-- Form is in DOM but hidden via x-show - only visible when showInlineForm=true --}}
    <div x-show="showInlineForm"
         x-transition.opacity.duration.150ms
         x-cloak
         class="inline-category-form flex items-center gap-2 py-2 px-3 mt-1 bg-gray-800/50 border border-gray-700 rounded-lg"
         style="margin-left: {{ ($level + 1) * 1.5 }}rem;">

        {{-- Input field - PERFORMANCE FIX: Pure Alpine x-model, NO wire:model.live! --}}
        <div class="flex-1 relative">
            <input
                type="text"
                x-model="inlineName"
                @keydown.enter="if(inlineName.trim()) { $wire.submitInlineCreate({{ $category->id }}, '{{ $context }}', inlineName); showInlineForm = false; inlineName = ''; }"
                @keydown.escape="showInlineForm = false; inlineName = ''; window.dispatchEvent(new CustomEvent('category-event', { detail: { type: 'inline-form-close' } }))"
                placeholder="Nazwa nowej podkategorii..."
                class="w-full px-3 py-1.5 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:ring-1 focus:ring-green-500"
            >
        </div>

        {{-- Submit button - PERFORMANCE FIX: Pass name via Alpine, NOT Livewire property --}}
        <button
            @click="if(inlineName.trim()) { $wire.submitInlineCreate({{ $category->id }}, '{{ $context }}', inlineName); showInlineForm = false; inlineName = ''; }"
            type="button"
            class="px-3 py-1.5 text-xs font-medium bg-green-600 hover:bg-green-700 text-white rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
        >
            <span wire:loading.remove wire:target="submitInlineCreate" class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Dodaj
            </span>
            <span wire:loading wire:target="submitInlineCreate" class="flex items-center gap-1">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Tworzenie...
            </span>
        </button>

        {{-- Cancel button - pure Alpine, no Livewire call --}}
        <button
            @click="showInlineForm = false; window.dispatchEvent(new CustomEvent('category-event', { detail: { type: 'inline-form-close' } }))"
            type="button"
            class="px-2 py-1.5 text-xs font-medium bg-gray-600 hover:bg-gray-500 text-gray-200 rounded transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Recursively render children with collapse/expand animation --}}
    {{-- PERFORMANCE FIX 2025-11-27: Use GPU-accelerated opacity-only transition (no reflow) --}}
    @if($hasChildren)
        <div x-show="!collapsed"
             x-transition.opacity.duration.100ms>
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
