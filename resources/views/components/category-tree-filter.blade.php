{{-- Reusable Category Tree Filter - Blade Component --}}
{{-- Usage: <x-category-tree-filter wire-model="categoryFilter" label="Kategoria" /> --}}

<div x-data="categoryTreeDropdown(
    {{ Js::from($categoryTree) }},
    $wire.get('{{ $wireModel }}') || '',
    '{{ $wireModel }}'
)" class="category-tree-dropdown-wrapper">
    <label class="block text-sm font-medium text-gray-300 mb-2">{{ $label }}</label>

    {{-- Trigger Button --}}
    <button @click="open = !open"
            type="button"
            class="category-tree-dropdown__trigger form-input w-full rounded-lg text-sm text-left flex items-center justify-between">
        <span x-text="selectedLabel || 'Wszystkie kategorie'" class="truncate"></span>
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 ml-2 transition-transform duration-150"
             :class="{ 'rotate-180': open }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="open"
         x-cloak
         @click.away="open = false"
         @keydown.escape.window="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="category-tree-dropdown__panel">

        {{-- Search in dropdown --}}
        <div class="category-tree-dropdown__search-wrapper">
            <input x-model="searchTerm"
                   x-ref="searchInput"
                   type="text"
                   placeholder="Szukaj kategorii..."
                   class="category-tree-dropdown__search form-input w-full text-sm rounded"
                   @click.stop>
        </div>

        {{-- "All categories" option --}}
        <button @click="selectCategory('', 'Wszystkie kategorie')"
                type="button"
                class="category-tree-dropdown__item"
                :class="{ 'category-tree-dropdown__item--active': !selectedId }">
            Wszystkie kategorie
        </button>

        {{-- Tree --}}
        <div class="category-tree-dropdown__tree">
            <template x-for="root in filteredTree" :key="root.id">
                <div>
                    {{-- Root node (level 0) --}}
                    <div class="category-tree-dropdown__node" style="padding-left: 0.5rem;">
                        <button x-show="root.children && root.children.length > 0"
                                @click.stop="toggleExpand(root.id)"
                                type="button"
                                class="category-tree-dropdown__expand-btn">
                            <svg :class="{ 'rotate-90': isExpanded(root.id) }"
                                 class="w-3 h-3 transition-transform duration-150"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <span x-show="!root.children || root.children.length === 0"
                              class="w-3 h-3 inline-block flex-shrink-0"></span>
                        <button @click="selectCategory(root.id, root.name)"
                                type="button"
                                class="category-tree-dropdown__item-text"
                                :class="{ 'category-tree-dropdown__item--active': selectedId == root.id }">
                            <span x-text="root.name"></span>
                        </button>
                    </div>

                    {{-- Level 1 children --}}
                    <template x-if="(isExpanded(root.id) || searchTerm.length > 0) && root.children">
                        <div>
                            <template x-for="l1 in filterChildren(root.children)" :key="l1.id">
                                <div>
                                    <div class="category-tree-dropdown__node" style="padding-left: 1.5rem;">
                                        <button x-show="l1.children && l1.children.length > 0"
                                                @click.stop="toggleExpand(l1.id)"
                                                type="button"
                                                class="category-tree-dropdown__expand-btn">
                                            <svg :class="{ 'rotate-90': isExpanded(l1.id) }"
                                                 class="w-3 h-3 transition-transform duration-150"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                        <span x-show="!l1.children || l1.children.length === 0"
                                              class="w-3 h-3 inline-block flex-shrink-0"></span>
                                        <button @click="selectCategory(l1.id, l1.name)"
                                                type="button"
                                                class="category-tree-dropdown__item-text"
                                                :class="{ 'category-tree-dropdown__item--active': selectedId == l1.id }">
                                            <span x-text="l1.name"></span>
                                        </button>
                                    </div>

                                    {{-- Level 2 children --}}
                                    <template x-if="(isExpanded(l1.id) || searchTerm.length > 0) && l1.children">
                                        <div>
                                            <template x-for="l2 in filterChildren(l1.children)" :key="l2.id">
                                                <div>
                                                    <div class="category-tree-dropdown__node" style="padding-left: 2.5rem;">
                                                        <button x-show="l2.children && l2.children.length > 0"
                                                                @click.stop="toggleExpand(l2.id)"
                                                                type="button"
                                                                class="category-tree-dropdown__expand-btn">
                                                            <svg :class="{ 'rotate-90': isExpanded(l2.id) }"
                                                                 class="w-3 h-3 transition-transform duration-150"
                                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                            </svg>
                                                        </button>
                                                        <span x-show="!l2.children || l2.children.length === 0"
                                                              class="w-3 h-3 inline-block flex-shrink-0"></span>
                                                        <button @click="selectCategory(l2.id, l2.name)"
                                                                type="button"
                                                                class="category-tree-dropdown__item-text"
                                                                :class="{ 'category-tree-dropdown__item--active': selectedId == l2.id }">
                                                            <span x-text="l2.name"></span>
                                                        </button>
                                                    </div>

                                                    {{-- Level 3 children --}}
                                                    <template x-if="(isExpanded(l2.id) || searchTerm.length > 0) && l2.children">
                                                        <div>
                                                            <template x-for="l3 in filterChildren(l2.children)" :key="l3.id">
                                                                <div class="category-tree-dropdown__node" style="padding-left: 3.5rem;">
                                                                    <span class="w-3 h-3 inline-block flex-shrink-0"></span>
                                                                    <button @click="selectCategory(l3.id, l3.name)"
                                                                            type="button"
                                                                            class="category-tree-dropdown__item-text"
                                                                            :class="{ 'category-tree-dropdown__item--active': selectedId == l3.id }">
                                                                        <span x-text="l3.name"></span>
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

@once
@script
<script>
Alpine.data('categoryTreeDropdown', (tree, initialFilter, wireProperty) => ({
    open: false,
    searchTerm: '',
    expandedNodes: [],
    selectedId: initialFilter || '',
    selectedLabel: '',
    tree: tree || [],
    wireProperty: wireProperty || 'categoryFilter',

    init() {
        if (this.selectedId) {
            this.selectedLabel = this.findCategoryName(this.tree, this.selectedId) || '';
        }

        this.$watch('open', (val) => {
            if (val) {
                this.$nextTick(() => {
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                });
            }
        });
    },

    findCategoryName(nodes, id) {
        for (const node of nodes) {
            if (String(node.id) === String(id)) return node.name;
            if (node.children && node.children.length > 0) {
                const found = this.findCategoryName(node.children, id);
                if (found) return found;
            }
        }
        return null;
    },

    toggleExpand(id) {
        const idx = this.expandedNodes.indexOf(id);
        if (idx >= 0) {
            this.expandedNodes.splice(idx, 1);
        } else {
            this.expandedNodes.push(id);
        }
    },

    isExpanded(id) {
        return this.expandedNodes.includes(id);
    },

    matchesSearch(name) {
        if (this.searchTerm.length === 0) return true;
        return name.toLowerCase().includes(this.searchTerm.toLowerCase());
    },

    nodeMatchesSearch(node) {
        if (this.matchesSearch(node.name)) return true;
        if (node.children) {
            return node.children.some(child => this.nodeMatchesSearch(child));
        }
        return false;
    },

    get filteredTree() {
        if (this.searchTerm.length === 0) return this.tree;
        return this.tree.filter(node => this.nodeMatchesSearch(node));
    },

    filterChildren(children) {
        if (!children) return [];
        if (this.searchTerm.length === 0) return children;
        return children.filter(node => this.nodeMatchesSearch(node));
    },

    selectCategory(id, label) {
        this.selectedId = id;
        this.selectedLabel = id ? label : '';
        this.$wire.set(this.wireProperty, id ? String(id) : '');
        this.open = false;
        this.searchTerm = '';
    }
}));
</script>
@endscript
@endonce
