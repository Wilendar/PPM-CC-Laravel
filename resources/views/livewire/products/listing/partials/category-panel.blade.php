{{-- Category Panel - Sliding sidebar with category tree --}}
<div class="category-panel category-panel--{{ $side }}"
     :style="'width: ' + panelWidth + 'px'">

    {{-- Resize Handle --}}
    <div class="category-panel__resize-handle"
         :class="{'category-panel__resize-handle--active': isResizing}"
         @mousedown.prevent="startResize($event)"></div>

    {{-- Header --}}
    <div class="category-panel__header">
        <span class="category-panel__title">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
            Kategorie
        </span>
        <button class="category-panel__close-btn" @click="togglePanel(null)" title="Zamknij panel">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Search --}}
    <div class="category-panel__search">
        <div class="relative">
            <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   x-model.debounce.300ms="searchTerm"
                   class="category-panel__search-input"
                   placeholder="Szukaj kategorii...">
        </div>
    </div>

    {{-- Category Tree --}}
    <div class="category-panel__tree">
        {{-- Selected category info --}}
        <template x-if="selectedCategoryId">
            <div class="px-3 py-1.5 flex items-center justify-between border-b border-gray-700/50">
                <span class="text-xs text-gray-400">
                    Filtr: <span class="text-orange-400" x-text="getSelectedCategoryName()"></span>
                </span>
                <button @click="clearCategoryFilter()" class="text-xs text-gray-500 hover:text-white">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>

        {{-- Tree nodes (recursive via Alpine) --}}
        <template x-if="filteredTree().length > 0">
            <div>
                <template x-for="node in filteredTree()" :key="node.id">
                    <div>
                        {{-- Node --}}
                        <div class="category-panel__node"
                             :data-category-id="node.id"
                             :class="nodeClasses(node)"
                             :style="'padding-left: ' + ((node.level - 2) * 1.25 + 0.5) + 'rem'"
                             @click="selectCategory(node.id)">

                            {{-- Expand button --}}
                            <template x-if="node.children && node.children.length > 0">
                                <button class="category-panel__expand-btn"
                                        :class="{'category-panel__expand-btn--expanded': isNodeExpanded(node.id)}"
                                        @click.stop="toggleExpand(node.id)">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </template>
                            <template x-if="!node.children || node.children.length === 0">
                                <span class="category-panel__expand-spacer"></span>
                            </template>

                            {{-- Marker (star/dot) --}}
                            <span class="category-panel__marker"
                                  :class="getNodeMarkerClass(node.id)"
                                  x-text="getNodeMarker(node.id)"></span>

                            {{-- Name --}}
                            <span class="category-panel__name" x-text="node.name" :title="node.name"></span>
                        </div>

                        {{-- Children (Level 3) --}}
                        <template x-if="node.children && node.children.length > 0 && isNodeExpanded(node.id)">
                            <div>
                                <template x-for="child in node.children" :key="child.id">
                                    <div>
                                        <div class="category-panel__node"
                                             :data-category-id="child.id"
                                             :class="nodeClasses(child)"
                                             :style="'padding-left: ' + ((child.level - 2) * 1.25 + 0.5) + 'rem'"
                                             @click="selectCategory(child.id)">
                                            <template x-if="child.children && child.children.length > 0">
                                                <button class="category-panel__expand-btn"
                                                        :class="{'category-panel__expand-btn--expanded': isNodeExpanded(child.id)}"
                                                        @click.stop="toggleExpand(child.id)">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>
                                            </template>
                                            <template x-if="!child.children || child.children.length === 0">
                                                <span class="category-panel__expand-spacer"></span>
                                            </template>
                                            <span class="category-panel__marker"
                                                  :class="getNodeMarkerClass(child.id)"
                                                  x-text="getNodeMarker(child.id)"></span>
                                            <span class="category-panel__name" x-text="child.name" :title="child.name"></span>
                                        </div>

                                        {{-- Children (Level 4) --}}
                                        <template x-if="child.children && child.children.length > 0 && isNodeExpanded(child.id)">
                                            <div>
                                                <template x-for="grandchild in child.children" :key="grandchild.id">
                                                    <div>
                                                        <div class="category-panel__node"
                                                             :data-category-id="grandchild.id"
                                                             :class="nodeClasses(grandchild)"
                                                             :style="'padding-left: ' + ((grandchild.level - 2) * 1.25 + 0.5) + 'rem'"
                                                             @click="selectCategory(grandchild.id)">
                                                            <template x-if="grandchild.children && grandchild.children.length > 0">
                                                                <button class="category-panel__expand-btn"
                                                                        :class="{'category-panel__expand-btn--expanded': isNodeExpanded(grandchild.id)}"
                                                                        @click.stop="toggleExpand(grandchild.id)">
                                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                                    </svg>
                                                                </button>
                                                            </template>
                                                            <template x-if="!grandchild.children || grandchild.children.length === 0">
                                                                <span class="category-panel__expand-spacer"></span>
                                                            </template>
                                                            <span class="category-panel__marker"
                                                                  :class="getNodeMarkerClass(grandchild.id)"
                                                                  x-text="getNodeMarker(grandchild.id)"></span>
                                                            <span class="category-panel__name" x-text="grandchild.name" :title="grandchild.name"></span>
                                                        </div>

                                                        {{-- Children (Level 5) --}}
                                                        <template x-if="grandchild.children && grandchild.children.length > 0 && isNodeExpanded(grandchild.id)">
                                                            <div>
                                                                <template x-for="ggchild in grandchild.children" :key="ggchild.id">
                                                                    <div class="category-panel__node"
                                                                         :data-category-id="ggchild.id"
                                                                         :class="nodeClasses(ggchild)"
                                                                         :style="'padding-left: ' + ((ggchild.level - 2) * 1.25 + 0.5) + 'rem'"
                                                                         @click="selectCategory(ggchild.id)">
                                                                        <span class="category-panel__expand-spacer"></span>
                                                                        <span class="category-panel__marker"
                                                                              :class="getNodeMarkerClass(ggchild.id)"
                                                                              x-text="getNodeMarker(ggchild.id)"></span>
                                                                        <span class="category-panel__name" x-text="ggchild.name" :title="ggchild.name"></span>
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
        </template>

        {{-- Empty state --}}
        <template x-if="filteredTree().length === 0">
            <div class="category-panel__empty">
                <template x-if="searchTerm">
                    <span>Brak wynikow dla "<span x-text="searchTerm"></span>"</span>
                </template>
                <template x-if="!searchTerm">
                    <span>Brak kategorii do wyswietlenia</span>
                </template>
            </div>
        </template>
    </div>
</div>
