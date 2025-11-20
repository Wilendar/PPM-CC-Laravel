{{-- ETAP_05b PHASE 6: Variant List Table (PPM UI Standards Compliant) --}}
<div class="bg-gray-800 rounded-xl overflow-hidden">
    @if($product && $product->variants && $product->variants->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            SKU
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Nazwa Wariantu
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Atrybuty
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Akcje
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($product->variants as $variant)
                        <tr wire:key="variant-row-{{ $variant->id }}" class="hover:bg-gray-700/30 transition-colors">
                            @include('livewire.products.management.partials.variant-row', ['variant' => $variant])
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-700 mb-4">
                <i class="fas fa-cube text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-white mb-2">Brak wariantów</h3>
            <p class="text-sm text-gray-400 mb-6">
                Ten produkt nie ma jeszcze żadnych wariantów.
                <br>Dodaj pierwszy wariant, aby rozpocząć.
            </p>
            <button type="button"
                    @click="$dispatch('open-variant-create-modal')"
                    class="btn-enterprise-primary inline-flex items-center px-4 py-2 space-x-2">
                <i class="fas fa-plus"></i>
                <span>Dodaj Pierwszy Wariant</span>
            </button>
        </div>
    @endif
</div>
