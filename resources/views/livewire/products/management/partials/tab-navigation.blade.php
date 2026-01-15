{{-- Enterprise Tab Navigation --}}
<div class="tabs-enterprise">
    <button class="tab-enterprise {{ $activeTab === 'basic' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('basic')">
        <i class="fas fa-info-circle icon"></i>
        <span>Informacje podstawowe</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'description' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('description')">
        <i class="fas fa-align-left icon"></i>
        <span>Opisy i SEO</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'physical' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('physical')">
        <i class="fas fa-box icon"></i>
        <span>Właściwości fizyczne</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'attributes' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('attributes')">
        <i class="fas fa-tags icon"></i>
        <span>Atrybuty</span>
    </button>

    {{-- Dopasowania tab - only for czesc-zamienna type products --}}
    @if(isset($product) && $product->productType?->slug === 'czesc-zamienna')
    <button class="tab-enterprise {{ $activeTab === 'compatibility' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('compatibility')">
        <i class="fas fa-link icon"></i>
        <span>Dopasowania</span>
        @php
            $compatCounts = method_exists($this, 'getCompatibilityCounts') ? $this->getCompatibilityCounts() : ['total' => 0];
        @endphp
        @if($compatCounts['total'] > 0)
            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-500/20 text-green-300">
                {{ $compatCounts['total'] }}
            </span>
        @endif
    </button>
    @endif

    <button class="tab-enterprise {{ $activeTab === 'prices' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('prices')">
        <i class="fas fa-dollar-sign icon"></i>
        <span>Ceny</span>
    </button>

    <button class="tab-enterprise {{ $activeTab === 'stock' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('stock')">
        <i class="fas fa-warehouse icon"></i>
        <span>Stany magazynowe</span>
    </button>

    @if($is_variant_master ?? false)
    <button class="tab-enterprise {{ $activeTab === 'variants' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('variants')">
        <i class="fas fa-cubes icon"></i>
        <span>Warianty</span>
        @if(isset($product) && $product->variants && $product->variants->count() > 0)
            @php
                $variantCount = $product->variants->count();
                $badgeClass = 'bg-gray-500/20 text-gray-300'; // Default: gray
                $badgeText = $variantCount;

                // Shop context: check variant compliance
                if (isset($activeShopId) && $activeShopId !== null && method_exists($this, 'getVariantShopStatus')) {
                    $wlasnyCount = 0;
                    foreach ($product->variants as $variant) {
                        $status = $this->getVariantShopStatus($variant->id);
                        if ($status === 'different') {
                            $wlasnyCount++;
                        }
                    }

                    if ($wlasnyCount > 0) {
                        // Orange: has custom variants
                        $badgeClass = 'bg-orange-500/20 text-orange-300';
                        $badgeText = $wlasnyCount . '/' . $variantCount;
                    } else {
                        // Green: all compliant
                        $badgeClass = 'bg-green-500/20 text-green-300';
                    }
                }
            @endphp
            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full {{ $badgeClass }}">
                {{ $badgeText }}
            </span>
        @endif
    </button>
    @endif

    <button class="tab-enterprise {{ $activeTab === 'gallery' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('gallery')">
        <i class="fas fa-images icon"></i>
        <span>Galeria</span>
    </button>

    {{-- Opis Wizualny tab - only in edit mode with product --}}
    @if(isset($product) && $product && $isEditMode)
    <button class="tab-enterprise {{ $activeTab === 'visual-description' ? 'active' : '' }}"
            type="button"
            wire:click="switchTab('visual-description')">
        <i class="fas fa-paint-brush icon"></i>
        <span>Opis Wizualny</span>
        @php
            $visualInfo = method_exists($this, 'visualDescriptionInfo') ? $this->visualDescriptionInfo : ['exists' => false];
        @endphp
        @if($visualInfo['exists'] && ($visualInfo['block_count'] ?? 0) > 0)
            <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-500/20 text-blue-300">
                {{ $visualInfo['block_count'] }}
            </span>
        @endif
    </button>
    @endif

</div>
