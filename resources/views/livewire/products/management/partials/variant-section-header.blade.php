{{-- ETAP_05b PHASE 6: Variant Section Header (PPM UI Standards Compliant) --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center space-x-4">
        <h3 class="text-xl font-semibold text-white">
            <i class="fas fa-layer-group text-blue-500 mr-2"></i>
            Warianty Produktu
        </h3>
        @if($product && $product->variants)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-900/30 text-blue-200 border border-blue-700/50">
                <i class="fas fa-cube mr-1"></i>
                {{ $product->variants->count() }} {{ $product->variants->count() === 1 ? 'wariant' : 'wariantów' }}
            </span>
        @endif
    </div>

    <button type="button"
            @click="$dispatch('open-variant-create-modal')"
            class="btn-enterprise-primary inline-flex items-center px-4 py-2 space-x-2">
        <i class="fas fa-plus"></i>
        <span>Dodaj Wariant</span>
    </button>
</div>
