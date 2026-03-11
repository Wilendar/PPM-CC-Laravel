{{-- Lazy-loaded category tree children (Alpine.js + JS recursion) --}}
{{-- Rendered by categoryTreeLazy Alpine component --}}
{{-- Sub-children rendered via JS _buildChildrenHtml() + Alpine.initTree() --}}
{{-- NO @include recursion (prevents Blade compile stack overflow) --}}

{{-- Loading spinner --}}
<div x-show="loading" class="flex items-center gap-2 py-1 text-gray-400 text-sm"
     :style="'padding-left: ' + (level * 1.5) + 'rem'">
    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span>Ladowanie...</span>
</div>

{{-- First-level children rendered via Blade x-for (Alpine handles events) --}}
<template x-for="child in children" :key="'lazy-' + context + '-' + child.id">
    <div x-data="{
        collapsed: true,
        isSelected: child.isSelected,
        isPrimary: child.isPrimary,
        markedForDeletion: child.isDeleted || false,
        parentMarkedForDeletion: false,
        showInlineForm: false,
        inlineName: ''
    }"
    x-on:category-event.window="
        const d = $event.detail;
        const ctx = String(d.context);
        const myCtx = String(context);
        switch(d.type) {
            case 'primary-changed':
                if (ctx === myCtx || !d.context) isPrimary = (d.categoryId === child.id);
                break;
            case 'toggle-all':
                if (ctx === myCtx) collapsed = d.expanded ? false : true;
                break;
            case 'search':
                if (ctx === myCtx) {
                    const q = d.query.toLowerCase().trim();
                    if (q === '') { $el.classList.remove('category-hidden','category-highlighted'); }
                    else {
                        const m = child.name.toLowerCase().includes(q);
                        $el.classList.toggle('category-highlighted', m);
                        $el.classList.toggle('category-hidden', !m);
                        if (m) collapsed = false;
                    }
                }
                break;
            case 'clear-all':
                if (ctx === myCtx) { isSelected = false; isPrimary = false; }
                break;
            case 'marked-for-deletion':
                if (Number(d.categoryId) === Number(child.id) && ctx === myCtx) {
                    markedForDeletion = true; isSelected = false; isPrimary = false;
                }
                break;
            case 'unmarked-for-deletion':
                if (Number(d.categoryId) === Number(child.id) && ctx === myCtx) {
                    markedForDeletion = false; parentMarkedForDeletion = false;
                }
                break;
            case 'inline-form-open':
                if (Number(d.categoryId) === Number(child.id) && ctx === myCtx) {
                    showInlineForm = true;
                    $nextTick(() => { const inp = $el.querySelector('input[type=text]'); if (inp) inp.focus(); });
                } else { showInlineForm = false; }
                break;
            case 'inline-form-close':
                showInlineForm = false;
                break;
        }
    "
    :class="{ 'category-marked-for-deletion': markedForDeletion || parentMarkedForDeletion }">

        {{-- Row --}}
        <div class="category-tree-row flex items-center space-x-2 py-1"
             :style="'padding-left: ' + (level * 1.5) + 'rem'">

            {{-- Chevron (click triggers JS-based sub-children loading) --}}
            <template x-if="child.hasChildren">
                <button type="button"
                        @click="
                            collapsed = !collapsed;
                            if (!collapsed) {
                                const sub = $el.closest('[x-data]').querySelector('[data-lazy-sub=&quot;' + child.id + '&quot;]');
                                if (sub) {
                                    sub.style.display = 'block';
                                    if (!sub.dataset.loaded) {
                                        sub.dataset.loaded = '1';
                                        sub.innerHTML = '<div class=&quot;flex items-center gap-2 py-1 text-gray-400 text-sm&quot; style=&quot;padding-left:' + ((level+1)*1.5) + 'rem&quot;><svg class=&quot;animate-spin w-3 h-3&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot;><circle class=&quot;opacity-25&quot; cx=&quot;12&quot; cy=&quot;12&quot; r=&quot;10&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;4&quot;></circle><path class=&quot;opacity-75&quot; fill=&quot;currentColor&quot; d=&quot;M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z&quot;></path></svg><span class=&quot;text-xs&quot;>Ladowanie...</span></div>';
                                        $wire.fetchChildCategories(child.id, context).then(data => {
                                            const treeRoot = $el.closest('[x-data*=&quot;categoryTreeLazy&quot;]');
                                            const builder = treeRoot ? Alpine.$data(treeRoot) : null;
                                            if (builder && builder._buildChildrenHtml && data.length > 0) {
                                                sub.innerHTML = builder._buildChildrenHtml(data, level + 1);
                                                Alpine.initTree(sub);
                                            } else {
                                                sub.innerHTML = '';
                                            }
                                        });
                                    }
                                }
                            } else {
                                const sub = $el.closest('[x-data]').querySelector('[data-lazy-sub=&quot;' + child.id + '&quot;]');
                                if (sub) sub.style.display = 'none';
                            }
                        "
                        class="text-gray-500 hover:text-gray-300 transition-transform duration-200"
                        :class="collapsed ? 'rotate-0' : 'rotate-90'">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </template>
            <template x-if="!child.hasChildren">
                <span class="w-4"></span>
            </template>

            {{-- Checkbox --}}
            <input type="checkbox"
                   :id="'category_' + context + '_' + child.id"
                   x-model="isSelected"
                   @change="$wire.toggleCategory(child.id)"
                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                   :disabled="$wire.categoryEditingDisabled || markedForDeletion || parentMarkedForDeletion">

            {{-- Label --}}
            <label :for="'category_' + context + '_' + child.id"
                   class="flex-1 category-tree-label"
                   :class="$wire.categoryEditingDisabled ? 'opacity-50 cursor-not-allowed' : ''">
                <span class="category-tree-icon mr-1" x-text="level > 0 ? '\u2514\u2500' : ''"></span>
                <span x-text="child.name"></span>
            </label>

            {{-- Set Primary button --}}
            <button x-show="isSelected && !markedForDeletion && !parentMarkedForDeletion"
                    @click="
                        window.dispatchEvent(new CustomEvent('category-event', {
                            detail: { type: 'primary-changed', categoryId: child.id, context: context }
                        }));
                        $wire.setPrimaryCategory(child.id)
                    "
                    type="button"
                    :class="isPrimary ? 'category-primary-btn' : 'category-set-primary-btn'"
                    class="px-2 py-1 text-xs rounded disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="$wire.categoryEditingDisabled"
                    x-text="isPrimary ? 'Glowna' : 'Ustaw glowna'"
                    x-transition.opacity.duration.100ms>
            </button>

            {{-- Delete button --}}
            <button x-show="!parentMarkedForDeletion"
                    @click="$wire.markCategoryForDeletion(child.id, context)"
                    type="button"
                    class="category-delete-btn ml-1 p-1 text-gray-400 hover:text-red-400 hover:bg-red-900/30 rounded transition-colors"
                    :title="markedForDeletion ? 'Cofnij usuniecie' : 'Oznacz do usuniecia'">
                <span x-show="!markedForDeletion" class="inline-flex">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </span>
                <span x-show="markedForDeletion" x-cloak class="inline-flex">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </span>
            </button>

            {{-- Deletion badge --}}
            <span x-show="markedForDeletion || parentMarkedForDeletion"
                  x-transition
                  class="category-deletion-badge">
                do usuniecia
            </span>
        </div>

        {{-- Inline create form --}}
        <div x-show="showInlineForm"
             x-transition.opacity.duration.150ms
             x-cloak
             class="inline-category-form flex items-center gap-2 py-2 px-3 mt-1 bg-gray-800/50 border border-gray-700 rounded-lg"
             :style="'margin-left: ' + ((level + 1) * 1.5) + 'rem'">
            <div class="flex-1 relative">
                <input type="text"
                       x-model="inlineName"
                       @keydown.enter="if(inlineName.trim()) { $wire.submitInlineCreate(child.id, context, inlineName); showInlineForm = false; inlineName = ''; }"
                       @keydown.escape="showInlineForm = false; inlineName = '';"
                       placeholder="Nazwa nowej podkategorii..."
                       class="w-full px-3 py-1.5 text-sm bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-400 focus:border-green-500 focus:ring-1 focus:ring-green-500">
            </div>
            <button @click="if(inlineName.trim()) { $wire.submitInlineCreate(child.id, context, inlineName); showInlineForm = false; inlineName = ''; }"
                    type="button"
                    class="px-3 py-1.5 text-xs font-medium bg-green-600 hover:bg-green-700 text-white rounded transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Dodaj
            </button>
            <button @click="showInlineForm = false;"
                    type="button"
                    class="px-2 py-1.5 text-xs font-medium bg-gray-600 hover:bg-gray-500 text-gray-200 rounded transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Sub-children container (JS-rendered via _buildChildrenHtml + Alpine.initTree) --}}
        <template x-if="child.hasChildren">
            <div :data-lazy-sub="child.id" style="display: none;"></div>
        </template>
    </div>
</template>
