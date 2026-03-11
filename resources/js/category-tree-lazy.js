/**
 * Category Tree Lazy Load - Alpine.js component
 *
 * Handles on-demand loading of category tree children via Livewire #[Renderless].
 * Each instance manages one level of children for a given parent.
 * Recursion is handled entirely in JS (no Blade @include) to avoid stack overflow.
 *
 * Usage in Blade:
 *   x-data="categoryTreeLazy({ parentId: 123, context: 'default', level: 2 })"
 */
export function registerCategoryTreeLazy(Alpine) {
    Alpine.data('categoryTreeLazy', (config) => ({
        parentId: config.parentId,
        context: config.context || 'default',
        level: config.level || 0,
        children: config.preloaded || [],
        loading: false,
        loaded: !!(config.preloaded && config.preloaded.length),

        async loadChildren() {
            if (this.loaded || this.loading) return;
            this.loading = true;
            try {
                this.children = await this.$wire.fetchChildCategories(this.parentId, this.context);
                this.loaded = true;
            } catch (e) {
                console.error('[categoryTreeLazy] Failed to load children for', this.parentId, e);
            } finally {
                this.loading = false;
            }
        },

        toggleChild(child) {
            child._collapsed = !child._collapsed;
            if (!child._collapsed && child.hasChildren && !child._subLoaded && !child._subLoading) {
                child._subLoading = true;
                this.$wire.fetchChildCategories(child.id, this.context).then(data => {
                    child._subChildren = data;
                    child._subLoaded = true;
                    child._subLoading = false;
                    // Render sub-children into the container and init Alpine
                    this.$nextTick(() => {
                        const container = this.$el.querySelector(`[data-lazy-sub="${child.id}"]`);
                        if (container && data.length > 0) {
                            container.innerHTML = this._buildChildrenHtml(data, this.level + 1);
                            Alpine.initTree(container);
                        }
                    });
                });
            }
        },

        toggleCategory(childId) {
            this.$wire.toggleCategory(childId);
        },

        setPrimary(childId) {
            window.dispatchEvent(new CustomEvent('category-event', {
                detail: { type: 'primary-changed', categoryId: childId, context: this.context }
            }));
            this.$wire.setPrimaryCategory(childId);
        },

        markForDeletion(childId) {
            this.$wire.markCategoryForDeletion(childId, this.context);
        },

        openInlineForm(childId) {
            window.dispatchEvent(new CustomEvent('category-event', {
                detail: { type: 'inline-form-open', categoryId: childId, context: this.context }
            }));
        },

        /**
         * Build HTML for a list of children (used for recursive sub-levels).
         * Each sub-child with hasChildren gets its own expandable container.
         */
        _buildChildrenHtml(children, level) {
            const ctx = this.context;
            const esc = (s) => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

            return children.map(child => {
                const pad = level * 1.5;
                const chevron = child.hasChildren
                    ? `<button type="button" onclick="this.closest('[data-lazy-child]').__x_toggle()" class="text-gray-500 hover:text-gray-300 transition-transform duration-200 rotate-0" data-chevron>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                       </button>`
                    : '<span class="w-4"></span>';

                const checked = child.isSelected ? 'checked' : '';
                const primaryBtn = child.isSelected
                    ? `<button onclick="this.closest('[data-lazy-child]').__x_primary()"
                        type="button" class="${child.isPrimary ? 'category-primary-btn' : 'category-set-primary-btn'} px-2 py-1 text-xs rounded">
                        ${child.isPrimary ? 'Glowna' : 'Ustaw glowna'}
                       </button>`
                    : '';

                const subContainer = child.hasChildren
                    ? `<div data-lazy-sub="${child.id}" style="display:none;"></div>`
                    : '';

                return `<div data-lazy-child="${child.id}" data-context="${esc(ctx)}" data-level="${level}"
                    x-data="{
                        _collapsed: true, isSelected: ${child.isSelected}, isPrimary: ${child.isPrimary},
                        markedForDeletion: ${child.isDeleted || false}
                    }"
                    x-init="
                        $el.__x_toggle = () => {
                            _collapsed = !_collapsed;
                            const chevronEl = $el.querySelector('[data-chevron]');
                            if (chevronEl) chevronEl.classList.toggle('rotate-90', !_collapsed);
                            const sub = $el.querySelector('[data-lazy-sub]');
                            if (sub) {
                                sub.style.display = _collapsed ? 'none' : 'block';
                                if (!_collapsed && !sub.dataset.loaded) {
                                    sub.dataset.loaded = '1';
                                    sub.innerHTML = '<div class=&quot;flex items-center gap-2 py-1 text-gray-400 text-sm&quot; style=&quot;padding-left:${(level+1)*1.5}rem&quot;><svg class=&quot;animate-spin w-3 h-3&quot; fill=&quot;none&quot; viewBox=&quot;0 0 24 24&quot;><circle class=&quot;opacity-25&quot; cx=&quot;12&quot; cy=&quot;12&quot; r=&quot;10&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;4&quot;></circle><path class=&quot;opacity-75&quot; fill=&quot;currentColor&quot; d=&quot;M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z&quot;></path></svg><span class=&quot;text-xs&quot;>Ladowanie...</span></div>';
                                    $wire.fetchChildCategories(${child.id}, '${esc(ctx)}').then(data => {
                                        if (data.length > 0) {
                                            const builder = Alpine.$data($el.closest('[x-data*=categoryTreeLazy]'));
                                            if (builder && builder._buildChildrenHtml) {
                                                sub.innerHTML = builder._buildChildrenHtml(data, ${level + 1});
                                                Alpine.initTree(sub);
                                            }
                                        } else {
                                            sub.innerHTML = '';
                                        }
                                    });
                                }
                            }
                        };
                        $el.__x_primary = () => {
                            window.dispatchEvent(new CustomEvent('category-event', {
                                detail: { type: 'primary-changed', categoryId: ${child.id}, context: '${esc(ctx)}' }
                            }));
                            $wire.setPrimaryCategory(${child.id});
                        };
                    "
                    x-on:category-event.window="
                        const d = $event.detail;
                        if (d.type === 'primary-changed' && (String(d.context) === String('${esc(ctx)}') || !d.context)) {
                            isPrimary = (d.categoryId === ${child.id});
                        }
                    "
                    :class="{ 'category-marked-for-deletion': markedForDeletion }">
                    <div class="category-tree-row flex items-center space-x-2 py-1" style="padding-left: ${pad}rem;">
                        ${chevron}
                        <input type="checkbox" id="category_${esc(ctx)}_${child.id}"
                            x-model="isSelected" @change="$wire.toggleCategory(${child.id})"
                            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="$wire.categoryEditingDisabled || markedForDeletion" ${checked}>
                        <label for="category_${esc(ctx)}_${child.id}" class="flex-1 category-tree-label">
                            <span class="category-tree-icon mr-1">${level > 0 ? '\u2514\u2500' : ''}</span>
                            ${esc(child.name)}
                        </label>
                        ${primaryBtn}
                        <button onclick="$wire.markCategoryForDeletion(${child.id}, '${esc(ctx)}')"
                            type="button" class="category-delete-btn ml-1 p-1 text-gray-400 hover:text-red-400 hover:bg-red-900/30 rounded transition-colors"
                            title="Oznacz do usuniecia">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    ${subContainer}
                </div>`;
            }).join('');
        },
    }));
}
