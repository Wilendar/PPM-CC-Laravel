/**
 * Category Panel - Alpine.js component for product list sidebar
 *
 * Features:
 * - Sliding resizable panel with category tree
 * - Bidirectional hover: product row -> highlight categories
 * - Click category -> filter products (including descendants)
 * - Search within category tree
 * - Persisted panel state in localStorage
 */

export function registerCategoryPanel(Alpine) {
    Alpine.data('categoryPanel', (config) => ({
        // State
        panelSide: null,
        panelWidth: 300,
        isResizing: false,
        searchTerm: '',
        selectedCategoryId: config.initialFilter ? parseInt(config.initialFilter) : null,

        // Data
        categoryTree: config.tree || [],
        categoryMap: {},
        productCategoryMap: config.productMap || {},

        // Hover state
        hoveredProductId: null,
        highlightedCategories: [],
        expandedForHover: new Set(),
        manuallyExpanded: new Set(),

        // Checkbox selection state
        selectionHighlightedCategories: [],
        selectionPrimaryCategories: [],

        // Resize state
        _startX: 0,
        _startWidth: 0,

        init() {
            // Load persisted state
            const saved = localStorage.getItem('ppm_category_panel');
            if (saved) {
                try {
                    const state = JSON.parse(saved);
                    if (state.side) this.panelSide = state.side;
                    if (state.width) this.panelWidth = Math.max(250, Math.min(500, state.width));
                } catch (e) { /* ignore */ }
            }

            // Build flat map for O(1) lookups
            this.buildCategoryMap(this.categoryTree);

            // Refresh productCategoryMap after Livewire updates (pagination, filters)
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    setTimeout(() => {
                        if (this.panelSide && this.$wire) {
                            this.$wire.call('getProductCategoryMapForPanel').then(map => {
                                this.productCategoryMap = map || {};
                            });
                        }
                    }, 100);
                });
            });

            // Watch selectedProducts for checkbox integration
            this.$watch('$wire.selectedProducts', (newVal) => {
                this.onSelectionChanged(newVal);
            });

            // Resize handlers (bound to document)
            this._onMouseMove = (e) => this.doResize(e);
            this._onMouseUp = () => this.stopResize();
        },

        // -- Panel toggle --
        togglePanel(side) {
            if (side === null || this.panelSide === side) {
                this.panelSide = null;
            } else {
                this.panelSide = side;
                // Refresh map when opening
                this.$wire.call('getProductCategoryMapForPanel').then(map => {
                    this.productCategoryMap = map || {};
                });
            }
            this.saveState();
        },

        // -- Resize --
        startResize(e) {
            this.isResizing = true;
            this._startX = e.clientX;
            this._startWidth = this.panelWidth;
            document.body.classList.add('category-panel--resizing');
            document.addEventListener('mousemove', this._onMouseMove);
            document.addEventListener('mouseup', this._onMouseUp);
        },

        doResize(e) {
            if (!this.isResizing) return;
            const diff = this.panelSide === 'left'
                ? e.clientX - this._startX
                : this._startX - e.clientX;
            this.panelWidth = Math.max(250, Math.min(500, this._startWidth + diff));
        },

        stopResize() {
            if (!this.isResizing) return;
            this.isResizing = false;
            document.body.classList.remove('category-panel--resizing');
            document.removeEventListener('mousemove', this._onMouseMove);
            document.removeEventListener('mouseup', this._onMouseUp);
            this.saveState();
        },

        // -- Hover interaction --
        onProductHover(productId) {
            if (!this.panelSide) return;
            if (this.hoveredProductId === productId) return;

            this.hoveredProductId = productId;
            const mapping = this.productCategoryMap[productId];
            if (!mapping) {
                this.highlightedCategories = [];
                this.collapseHoverExpanded();
                return;
            }

            this.highlightedCategories = mapping.categories || [];
            this.expandToCategories(this.highlightedCategories);

            // Auto-scroll to primary category (or first highlighted)
            // Delay to ensure Alpine has rendered expanded nodes in DOM
            const scrollTarget = mapping.primary || (mapping.categories ? mapping.categories[0] : null);
            if (scrollTarget) {
                this.$nextTick(() => {
                    setTimeout(() => this.scrollToCategory(scrollTarget), 120);
                });
            }
        },

        expandToCategories(categoryIds) {
            // Collapse previously hover-expanded
            this.expandedForHover.forEach(id => {
                if (!this.manuallyExpanded.has(id)) {
                    this.expandedForHover.delete(id);
                }
            });

            // Walk parent chain for each category and expand
            const toExpand = new Set();
            categoryIds.forEach(catId => {
                let node = this.categoryMap[catId];
                while (node && node.parentId) {
                    const parent = this.categoryMap[node.parentId];
                    if (parent) {
                        toExpand.add(parent.id);
                    }
                    node = parent;
                }
            });

            toExpand.forEach(id => {
                this.expandedForHover.add(id);
            });
        },

        collapseHoverExpanded() {
            // Sticky hover: keep hoveredProductId, only clear visual state
            this.highlightedCategories = [];
            this.expandedForHover = new Set();
        },

        // -- Checkbox selection integration --
        onSelectionChanged(selectedIds) {
            if (!this.panelSide) return;
            if (!selectedIds || selectedIds.length === 0) {
                this.selectionHighlightedCategories = [];
                this.selectionPrimaryCategories = [];
                return;
            }

            const allCats = new Set();
            const allPrimary = new Set();
            let lastProductId = null;

            for (const pid of selectedIds) {
                const mapping = this.productCategoryMap[pid];
                if (mapping) {
                    (mapping.categories || []).forEach(c => allCats.add(c));
                    if (mapping.primary) allPrimary.add(mapping.primary);
                    lastProductId = pid;
                }
            }

            this.selectionHighlightedCategories = [...allCats];
            this.selectionPrimaryCategories = [...allPrimary];

            this.expandToCategories([...allCats]);

            // Auto-scroll to primary category of last selected product
            if (lastProductId) {
                const lastMapping = this.productCategoryMap[lastProductId];
                const scrollTarget = lastMapping?.primary || lastMapping?.categories?.[0];
                if (scrollTarget) {
                    this.$nextTick(() => {
                        setTimeout(() => this.scrollToCategory(scrollTarget), 120);
                    });
                }
            }
        },

        // -- Category selection --
        selectCategory(id) {
            // Persist expansion path to the selected category before clearing hover
            let node = this.categoryMap[id];
            while (node && node.parentId) {
                const parent = this.categoryMap[node.parentId];
                if (parent) {
                    this.manuallyExpanded.add(parent.id);
                }
                node = parent;
            }

            // Clear sticky hover (tree stays expanded via manuallyExpanded)
            this.hoveredProductId = null;
            this.highlightedCategories = [];
            this.expandedForHover = new Set();

            if (this.selectedCategoryId === id) {
                this.clearCategoryFilter();
            } else {
                this.selectedCategoryId = id;
                this.$wire.set('categoryFilter', String(id));
            }
        },

        clearCategoryFilter() {
            this.selectedCategoryId = null;
            this.$wire.set('categoryFilter', '');
        },

        getSelectedCategoryName() {
            if (!this.selectedCategoryId) return '';
            const node = this.categoryMap[this.selectedCategoryId];
            return node ? node.name : '';
        },

        // -- Expand/collapse --
        toggleExpand(id) {
            if (this.manuallyExpanded.has(id)) {
                this.manuallyExpanded.delete(id);
            } else {
                this.manuallyExpanded.add(id);
            }
        },

        isNodeExpanded(id) {
            return this.manuallyExpanded.has(id) || this.expandedForHover.has(id);
        },

        // -- Node rendering --
        nodeClasses(node) {
            const classes = {};

            // 1. Active filter (click) - highest priority
            if (this.selectedCategoryId === node.id) {
                classes['category-panel__node--selected'] = true;
            }

            // 2. Hover highlight (mouseenter on product row)
            if (this.highlightedCategories.includes(node.id)) {
                classes['category-panel__node--highlighted'] = true;
                const mapping = this.productCategoryMap[this.hoveredProductId];
                if (mapping && mapping.primary === node.id) {
                    classes['category-panel__node--primary'] = true;
                }
            }

            // 3. Checkbox highlight (selected products)
            if (this.selectionHighlightedCategories.includes(node.id)) {
                classes['category-panel__node--selection-highlighted'] = true;
                if (this.selectionPrimaryCategories.includes(node.id)) {
                    classes['category-panel__node--selection-primary'] = true;
                }
            }

            return classes;
        },

        getNodeMarker(nodeId) {
            // Priority 1: hover markers
            if (this.hoveredProductId) {
                const mapping = this.productCategoryMap[this.hoveredProductId];
                if (mapping) {
                    if (mapping.primary === nodeId) return '\u2605';
                    if (mapping.categories && mapping.categories.includes(nodeId)) return '\u25CF';
                }
            }
            // Priority 2: checkbox markers
            if (this.selectionPrimaryCategories.includes(nodeId)) return '\u2605';
            if (this.selectionHighlightedCategories.includes(nodeId)) return '\u25CF';
            return '';
        },

        getNodeMarkerClass(nodeId) {
            // Priority 1: hover markers
            if (this.hoveredProductId) {
                const mapping = this.productCategoryMap[this.hoveredProductId];
                if (mapping) {
                    if (mapping.primary === nodeId) return 'category-panel__marker--primary';
                    if (mapping.categories && mapping.categories.includes(nodeId)) return 'category-panel__marker--other';
                }
            }
            // Priority 2: checkbox markers
            if (this.selectionPrimaryCategories.includes(nodeId)) return 'category-panel__marker--selection-primary';
            if (this.selectionHighlightedCategories.includes(nodeId)) return 'category-panel__marker--selection-other';
            return '';
        },

        // -- Search --
        filteredTree() {
            if (!this.searchTerm) return this.categoryTree;
            const term = this.searchTerm.toLowerCase();
            return this.filterNodes(this.categoryTree, term);
        },

        filterNodes(nodes, term) {
            const result = [];
            for (const node of nodes) {
                const nameMatch = node.name.toLowerCase().includes(term);
                const filteredChildren = node.children
                    ? this.filterNodes(node.children, term)
                    : [];

                if (nameMatch || filteredChildren.length > 0) {
                    result.push({
                        ...node,
                        children: nameMatch ? (node.children || []) : filteredChildren
                    });
                }
            }
            return result;
        },

        // -- Auto-scroll --
        scrollToCategory(categoryId) {
            const treeEl = this.$el.querySelector('.category-panel__tree');
            if (!treeEl) return;
            const nodeEl = treeEl.querySelector(`[data-category-id="${categoryId}"]`);
            if (!nodeEl) return;

            const treeRect = treeEl.getBoundingClientRect();
            const nodeRect = nodeEl.getBoundingClientRect();

            // Always center the target node in the tree panel
            const nodeCenter = nodeRect.top + nodeRect.height / 2;
            const treeCenter = treeRect.top + treeRect.height / 2;
            const offset = nodeCenter - treeCenter;

            if (Math.abs(offset) > 20) {
                treeEl.scrollBy({ top: offset, behavior: 'smooth' });
            }
        },

        // -- Helpers --
        buildCategoryMap(tree) {
            for (const node of tree) {
                this.categoryMap[node.id] = {
                    id: node.id,
                    name: node.name,
                    parentId: node.parentId || null,
                };
                if (node.children && node.children.length > 0) {
                    this.buildCategoryMap(node.children);
                }
            }
        },

        saveState() {
            localStorage.setItem('ppm_category_panel', JSON.stringify({
                side: this.panelSide,
                width: this.panelWidth,
            }));
        },
    }));
}
