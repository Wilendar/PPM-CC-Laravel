{{-- Grouped view: images organized by variant assignment --}}
@php
    $groups = $this->variantImageGroups;
    $mainImages = $groups['_main'] ?? [];
@endphp

{{-- Main product images group --}}
@if(count($mainImages) > 0)
<div class="import-grouped-section" x-data="{ collapsed: false }">
    <div class="import-grouped-section-header" @click="collapsed = !collapsed">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-90': !collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="px-2 py-0.5 bg-gray-600 text-gray-200 text-xs rounded font-medium">
                Produkt glowny
            </span>
            <span class="text-xs text-gray-500">
                {{ count($mainImages) }} {{ count($mainImages) === 1 ? 'zdjecie' : 'zdjec' }}
            </span>
        </div>
    </div>
    <div class="import-grouped-section-body" x-show="!collapsed" x-collapse>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($mainImages as $item)
                @php
                    $index = $item['index'];
                    $image = $item['image'];
                    $isGlobalCover = $image['is_cover'] ?? false;
                    $isVariantCover = false;
                @endphp
                @include('livewire.products.import.modals.partials.image-card', [
                    'image' => $image,
                    'index' => $index,
                    'isGlobalCover' => $isGlobalCover,
                    'isVariantCover' => $isVariantCover,
                    'showVariantAssignment' => $showVariantAssignment,
                    'variants' => $variants,
                    'variantCovers' => $variantCovers,
                    'images' => $images,
                    'selectedImages' => $selectedImages,
                ])
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Variant groups --}}
@foreach($groups as $groupKey => $groupImages)
    @if($groupKey === '_main')
        @continue
    @endif
    @php
        $variantInfo = collect($variants)->firstWhere('sku_suffix', $groupKey);
        $variantName = $variantInfo ? $this->getVariantDisplayName($variantInfo) : $groupKey;
        $coverIndex = $variantCovers[$groupKey] ?? null;
    @endphp

    <div class="import-grouped-section" x-data="{ collapsed: false }">
        <div class="import-grouped-section-header" @click="collapsed = !collapsed">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-90': !collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="px-2 py-0.5 bg-purple-900/50 text-purple-300 border border-purple-700/50 text-xs rounded font-medium">
                    {{ $variantName }}
                </span>
                <span class="text-xs text-gray-500">{{ $groupKey }}</span>
                <span class="text-xs text-gray-500">
                    {{ count($groupImages) }} {{ count($groupImages) === 1 ? 'zdjecie' : 'zdjec' }}
                </span>
            </div>
            @if(count($groupImages) > 0 && $coverIndex !== null)
            <span class="text-xs text-amber-400">
                Okladka: #{{ $coverIndex + 1 }}
            </span>
            @endif
        </div>

        <div class="import-grouped-section-body" x-show="!collapsed" x-collapse>
            @if(count($groupImages) > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($groupImages as $item)
                    @php
                        $index = $item['index'];
                        $image = $item['image'];
                        $isGlobalCover = $image['is_cover'] ?? false;
                        $variantSku = $image['variant_sku'] ?? null;
                        $isVariantCover = $variantSku && isset($variantCovers[$variantSku]) && $variantCovers[$variantSku] === $index;
                    @endphp
                    @include('livewire.products.import.modals.partials.image-card', [
                        'image' => $image,
                        'index' => $index,
                        'isGlobalCover' => $isGlobalCover,
                        'isVariantCover' => $isVariantCover,
                        'showVariantAssignment' => $showVariantAssignment,
                        'variants' => $variants,
                        'variantCovers' => $variantCovers,
                        'images' => $images,
                        'selectedImages' => $selectedImages,
                    ])
                @endforeach
            </div>
            @else
            <p class="text-xs text-gray-500 italic py-2 text-center">Brak przypisanych zdjec</p>
            @endif
        </div>
    </div>
@endforeach

{{-- Unassigned images (if any exist that don't belong to known variants) --}}
@php
    $unassigned = [];
    $knownKeys = array_merge(['_main'], collect($variants)->pluck('sku_suffix')->filter()->toArray());
    foreach ($images as $idx => $img) {
        $vSku = $img['variant_sku'] ?? null;
        if ($vSku && !in_array($vSku, $knownKeys)) {
            $unassigned[] = ['index' => $idx, 'image' => $img];
        }
    }
@endphp

@if(count($unassigned) > 0)
<div class="import-grouped-section" x-data="{ collapsed: false }">
    <div class="import-grouped-section-header" @click="collapsed = !collapsed">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-90': !collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="px-2 py-0.5 bg-red-600/50 text-red-200 text-xs rounded font-medium">
                Nieprzypisane
            </span>
            <span class="text-xs text-gray-500">
                {{ count($unassigned) }} {{ count($unassigned) === 1 ? 'zdjecie' : 'zdjec' }}
            </span>
        </div>
    </div>
    <div class="import-grouped-section-body" x-show="!collapsed" x-collapse>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($unassigned as $item)
                @include('livewire.products.import.modals.partials.image-card', [
                    'image' => $item['image'],
                    'index' => $item['index'],
                    'isGlobalCover' => $item['image']['is_cover'] ?? false,
                    'isVariantCover' => false,
                    'showVariantAssignment' => $showVariantAssignment,
                    'variants' => $variants,
                    'variantCovers' => $variantCovers,
                    'images' => $images,
                    'selectedImages' => $selectedImages,
                ])
            @endforeach
        </div>
    </div>
</div>
@endif
