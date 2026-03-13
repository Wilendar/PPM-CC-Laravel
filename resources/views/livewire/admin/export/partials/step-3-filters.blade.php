<div class="space-y-4">
    {{-- Header z statystykami --}}
    <div class="export-filter__header">
        <div>
            <h2 class="text-lg font-semibold text-white">Filtry produktow</h2>
            <p class="mt-1 text-sm text-gray-400">
                Okresl, ktore produkty maja byc uwzglednione w eksporcie.
                @if($this->getActiveAdvancedFilterCount() > 0)
                    <span style="color: var(--mpp-primary)">({{ $this->getActiveAdvancedFilterCount() }} zaawansowanych aktywnych)</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-400">
            <span>
                <span class="font-semibold text-white">{{ number_format($this->exportProductsCount, 0, ',', ' ') }}</span> produktow
            </span>
            @if(!empty($excludedProductIds))
                <span>
                    <span class="font-semibold text-red-400">{{ count($excludedProductIds) }}</span> wykluczonych
                </span>
            @endif
        </div>
    </div>

    {{-- Filters toolbar --}}
    @include('livewire.admin.export.partials.export-filters-bar')

    {{-- 2-column layout: sidebar + resizer + product table --}}
    <div class="export-filter-browser"
         x-data="{
             sidebarWidth: 280,
             isDragging: false,
             startX: 0,
             startW: 0
         }"
         x-on:mousemove.window="if (isDragging) {
             sidebarWidth = Math.max(200, Math.min(500, startW + ($event.clientX - startX)));
         }"
         x-on:mouseup.window="isDragging = false; document.body.style.cursor = ''"
         x-effect="if (isDragging) { document.body.style.cursor = 'col-resize'; document.body.style.userSelect = 'none' } else { document.body.style.userSelect = '' }">

        <div class="export-filter-browser__columns"
             :style="'grid-template-columns: ' + sidebarWidth + 'px auto 1fr'">

            {{-- SIDEBAR: Category Picker --}}
            <div class="export-filter-browser__sidebar">
                <div class="export-filter-browser__sidebar-header">
                    <span>Kategorie</span>
                    @if(!empty($filterCategoryIds))
                        <span class="rounded-full bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-300">
                            {{ count($filterCategoryIds) }}
                        </span>
                    @endif
                </div>
                <div class="export-filter-browser__sidebar-content" wire:ignore>
                    <livewire:products.category-picker
                        wire:model="filterCategoryIds"
                        context="export-filter"
                        :showCreateButton="false"
                        :enableChildAutoSelect="true"
                        wire:key="export-category-picker" />
                </div>
                <div class="export-filter-browser__sidebar-footer">
                    <span>Wybrano: <strong>{{ count($filterCategoryIds ?? []) }}</strong></span>
                </div>
            </div>

            {{-- RESIZER: Draggable divider --}}
            <div class="export-filter-browser__resizer"
                 :class="{ 'export-filter-browser__resizer--dragging': isDragging }"
                 @mousedown.prevent="isDragging = true; startX = $event.clientX; startW = sidebarWidth"
                 @dblclick="sidebarWidth = 280"
                 title="Przeciagnij aby zmienic szerokosc. Kliknij 2x aby zresetowac.">
            </div>

            {{-- MAIN: Product Table --}}
            <div class="export-filter-browser__main">
                @include('livewire.admin.export.partials.export-product-table')
            </div>
        </div>

        {{-- Summary bar --}}
        @include('livewire.admin.export.partials.export-summary-bar')
    </div>

    {{-- Advanced filters (collapsible) --}}
    @include('livewire.admin.export.partials.export-advanced-filters')
</div>
