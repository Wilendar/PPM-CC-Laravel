{{-- ETAP_05b PHASE 6: Variant Prices Grid (PPM UI Standards Compliant) --}}
<div class="bg-gray-800 rounded-xl p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h4 class="text-lg font-medium text-white">
            <i class="fas fa-tags text-green-500 mr-2"></i>
            Ceny Wariantów per Grupa Cenowa
        </h4>
    </div>

    @if($product && $product->variants && $product->variants->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider sticky left-0 bg-gray-900">
                            Wariant (SKU)
                        </th>
                        {{-- Price Group Headers (placeholder - will be populated by methods in Zadanie 3) --}}
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Detaliczna
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Dealer Standard
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Dealer Premium
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Warsztat
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($product->variants as $variant)
                        <tr wire:key="variant-price-row-{{ $variant->id }}" class="hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-white font-mono sticky left-0 bg-gray-800">
                                {{ $variant->sku }}
                            </td>
                            {{-- Price Cells (placeholder - Alpine.js x-model for inline editing) --}}
                            <td class="px-4 py-3 text-center">
                                <input type="number"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="w-24 px-2 py-1 bg-gray-900 border border-gray-600 rounded text-white text-sm text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                       x-model="variantPrices[{{ $variant->id }}].retail">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="w-24 px-2 py-1 bg-gray-900 border border-gray-600 rounded text-white text-sm text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                       x-model="variantPrices[{{ $variant->id }}].dealer_standard">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="w-24 px-2 py-1 bg-gray-900 border border-gray-600 rounded text-white text-sm text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                       x-model="variantPrices[{{ $variant->id }}].dealer_premium">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="w-24 px-2 py-1 bg-gray-900 border border-gray-600 rounded text-white text-sm text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                       x-model="variantPrices[{{ $variant->id }}].workshop">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-end mt-4">
            <button wire:click="savePrices"
                    wire:loading.attr="disabled"
                    class="btn-enterprise-primary px-6 py-2">
                <span wire:loading.remove wire:target="savePrices">
                    <i class="fas fa-save mr-2"></i>Zapisz Ceny
                </span>
                <span wire:loading wire:target="savePrices">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Zapisywanie...
                </span>
            </button>
        </div>
    @else
        <div class="text-center py-12">
            <i class="fas fa-tags text-4xl text-gray-600 mb-4"></i>
            <p class="text-sm text-gray-400">
                Brak wariantów. Dodaj warianty produktu, aby zarządzać cenami.
            </p>
        </div>
    @endif
</div>
