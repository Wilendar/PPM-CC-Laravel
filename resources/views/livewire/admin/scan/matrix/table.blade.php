{{--
    Matrix Table View - Cross-Source Product Matrix
    Tabela macierzowa pokazujaca status produktow we wszystkich zrodlach.

    Zmienne:
    - $matrixData: LengthAwarePaginator
    - $sources: array [{type, id, name, icon, color, is_shop}]
    - $groupedView: bool
    - $expandedDiffs: array product IDs
    - $selectedProducts: array product IDs
--}}

<div x-data="{ expandedGroups: [] }">

    {{-- Table wrapper ze sticky column i horizontal scroll --}}
    <div class="overflow-x-auto rounded-lg border border-gray-700">
        <table class="w-full text-sm text-left">

            {{-- ========================================
                 THEAD
                 ======================================== --}}
            <thead class="bg-gray-800 text-gray-300 text-xs uppercase tracking-wider">
                <tr>
                    {{-- Checkbox select all (wire:model.live pattern) --}}
                    <th class="px-3 py-3 w-10">
                        <input type="checkbox"
                               wire:model.live="selectAll"
                               class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500 focus:ring-offset-gray-800 cursor-pointer">
                    </th>

                    {{-- SKU - sticky + sortable --}}
                    <th class="matrix-sticky-col px-3 py-3 font-semibold whitespace-nowrap cursor-pointer select-none hover:text-white transition-colors"
                        wire:click="sortBy('sku')">
                        <span class="inline-flex items-center gap-1">
                            SKU
                            @if($sortField === 'sku')
                                <svg class="w-3 h-3 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    @endif
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                </svg>
                            @endif
                        </span>
                    </th>

                    {{-- Nazwa - sortable --}}
                    <th class="px-3 py-3 font-semibold whitespace-nowrap min-w-[200px] cursor-pointer select-none hover:text-white transition-colors"
                        wire:click="sortBy('name')">
                        <span class="inline-flex items-center gap-1">
                            Nazwa
                            @if($sortField === 'name')
                                <svg class="w-3 h-3 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    @endif
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                </svg>
                            @endif
                        </span>
                    </th>

                    {{-- Marka - sortable --}}
                    <th class="px-3 py-3 font-semibold whitespace-nowrap cursor-pointer select-none hover:text-white transition-colors"
                        wire:click="sortBy('manufacturer')">
                        <span class="inline-flex items-center gap-1">
                            Marka
                            @if($sortField === 'manufacturer')
                                <svg class="w-3 h-3 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($sortDirection === 'asc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    @endif
                                </svg>
                            @else
                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                </svg>
                            @endif
                        </span>
                    </th>

                    {{-- Source headers (filterable) - wire:key MANDATORY for Livewire morph --}}
                    @foreach($sources as $source)
                        @php $srcKey = $source['type'] . '_' . $source['id']; @endphp
                        @if($this->isSourceVisible($srcKey))
                        <th wire:key="th-src-{{ $srcKey }}"
                            class="px-3 py-3 text-center whitespace-nowrap min-w-[100px]"
                            style="border-top: 3px solid {{ $source['color'] ?? '#6b7280' }}">
                            <div class="flex flex-col items-center space-y-1">
                                <span class="font-semibold text-gray-200 text-xs leading-tight">
                                    {{ $source['name'] }}
                                </span>
                                <span class="text-gray-500 text-xs font-normal normal-case tracking-normal">
                                    @if($source['type'] === 'prestashop')
                                        PS
                                    @elseif($source['type'] === 'subiekt_gt')
                                        SGT
                                    @elseif($source['type'] === 'baselinker')
                                        BL
                                    @else
                                        {{ Str::upper(Str::substr($source['type'], 0, 3)) }}
                                    @endif
                                </span>
                            </div>
                        </th>
                        @endif
                    @endforeach
                </tr>
            </thead>

            {{-- ========================================
                 TBODY
                 ======================================== --}}
            <tbody class="divide-y divide-gray-700">

                @if($matrixData->isEmpty())
                    {{-- Empty state --}}
                    <tr>
                        <td colspan="{{ 4 + count($sources) }}" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">Brak produktow spelniajacych kryteria</p>
                            <button wire:click="resetFilters"
                                    class="mt-2 text-sm text-[#e0ac7e] hover:underline">
                                Resetuj filtry
                            </button>
                        </td>
                    </tr>

                @elseif($groupedView)
                    {{-- ==================
                         GROUPED VIEW
                         ================== --}}
                    @foreach($matrixData->groupBy(fn($p) => $p->manufacturerRelation?->name ?? 'Bez marki') as $brand => $brandProducts)

                        {{-- Group header row --}}
                        <tr class="bg-gray-800/80">
                            <td colspan="{{ 4 + count($sources) }}" class="px-4 py-2">
                                <button class="flex items-center space-x-2 text-sm font-medium text-white w-full text-left"
                                        @click="expandedGroups = expandedGroups.includes('{{ addslashes($brand) }}')
                                            ? expandedGroups.filter(g => g !== '{{ addslashes($brand) }}')
                                            : [...expandedGroups, '{{ addslashes($brand) }}']">

                                    {{-- Arrow icon --}}
                                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0"
                                         :class="{ 'rotate-90': expandedGroups.includes('{{ addslashes($brand) }}') }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>

                                    <span class="text-gray-200">{{ $brand }}</span>
                                    <span class="text-gray-500 text-xs">({{ $brandProducts->count() }})</span>

                                    {{-- Mini status summary --}}
                                    @php
                                        $statusCounts = ['linked' => 0, 'missing' => 0, 'conflict' => 0, 'pending_sync' => 0];
                                        foreach ($brandProducts as $bp) {
                                            foreach (($bp->matrix_cells ?? []) as $cell) {
                                                $s = $cell['status'] ?? 'missing';
                                                if (isset($statusCounts[$s])) {
                                                    $statusCounts[$s]++;
                                                }
                                            }
                                        }
                                    @endphp
                                    <div class="flex items-center space-x-2 ml-3">
                                        @if($statusCounts['linked'] > 0)
                                            <span class="text-xs text-green-400">{{ $statusCounts['linked'] }} ok</span>
                                        @endif
                                        @if($statusCounts['conflict'] > 0)
                                            <span class="text-xs text-yellow-400">{{ $statusCounts['conflict'] }} konflikty</span>
                                        @endif
                                        @if($statusCounts['missing'] > 0)
                                            <span class="text-xs text-red-400">{{ $statusCounts['missing'] }} brak</span>
                                        @endif
                                    </div>
                                </button>
                            </td>
                        </tr>

                        {{-- Grouped product rows --}}
                        <template x-if="expandedGroups.includes('{{ addslashes($brand) }}')">
                            <tbody>
                                @foreach($brandProducts as $product)
                                    @include('livewire.admin.scan.matrix._product-row', [
                                        'product' => $product,
                                        'sources' => $sources,
                                        'selectedProducts' => $selectedProducts,
                                        'expandedDiffs' => $expandedDiffs,
                                    ])
                                @endforeach
                            </tbody>
                        </template>

                    @endforeach

                @else
                    {{-- ==================
                         FLAT VIEW
                         ================== --}}
                    @foreach($matrixData as $product)
                        @include('livewire.admin.scan.matrix._product-row', [
                            'product' => $product,
                            'sources' => $sources,
                            'selectedProducts' => $selectedProducts,
                            'expandedDiffs' => $expandedDiffs,
                        ])
                    @endforeach

                @endif

            </tbody>
        </table>
    </div>

    {{-- Infinite Scroll sentinel --}}
    @if($hasMoreProducts ?? false)
        <div
            x-data="{
                init() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                $wire.loadMore();
                            }
                        });
                    }, { threshold: 0.1 });
                    observer.observe($el);
                }
            }"
            class="matrix-load-more-sentinel">
            <div wire:loading wire:target="loadMore" class="matrix-load-more-spinner">
                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-400">Ladowanie...</span>
            </div>
        </div>
    @else
        @if(($matrixData->count() ?? 0) > 0)
            <div class="matrix-load-more-end">
                Wyswietlono wszystkie {{ $matrixData->count() }} produktow
            </div>
        @endif
    @endif

</div>
