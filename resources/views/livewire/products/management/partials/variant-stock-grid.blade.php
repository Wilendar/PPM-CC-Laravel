{{-- ETAP_05b PHASE 6: Variant Stock Grid (PPM UI Standards Compliant) --}}
<div class="bg-gray-800 rounded-xl p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h4 class="text-lg font-medium text-white">
            <i class="fas fa-warehouse text-blue-500 mr-2"></i>
            Stany Magazynowe Wariantów
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
                        {{-- Warehouse Headers (placeholder - will be populated by methods) --}}
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            MPPTRADE
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Pitbike.pl
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Cameraman
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">
                            Otopit
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($product->variants as $variant)
                        <tr wire:key="variant-stock-row-{{ $variant->id }}" class="hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-white font-mono sticky left-0 bg-gray-800">
                                {{ $variant->sku }}
                            </td>
                            {{-- Stock Cells (placeholder - Alpine.js x-model for inline editing) --}}
                            @for($i = 1; $i <= 4; $i++)
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <input type="number"
                                               min="0"
                                               placeholder="0"
                                               class="w-20 px-2 py-1 bg-gray-900 border border-gray-600 rounded text-white text-sm text-right focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                               x-model="variantStock[{{ $variant->id }}][{{ $i }}]">
                                        {{-- Low stock indicator --}}
                                        <span x-show="variantStock[{{ $variant->id }}][{{ $i }}] < 10"
                                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-900/30 text-red-200 border border-red-700/50">
                                            <i class="fas fa-exclamation-triangle text-xs mr-1"></i>
                                            Niski
                                        </span>
                                    </div>
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Niski stan: poniżej 10 sztuk
                </span>
            </div>
            <button wire:click="saveStock"
                    wire:loading.attr="disabled"
                    class="btn-enterprise-primary px-6 py-2">
                <span wire:loading.remove wire:target="saveStock">
                    <i class="fas fa-save mr-2"></i>Zapisz Stany
                </span>
                <span wire:loading wire:target="saveStock">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Zapisywanie...
                </span>
            </button>
        </div>
    @else
        <div class="text-center py-12">
            <i class="fas fa-warehouse text-4xl text-gray-600 mb-4"></i>
            <p class="text-sm text-gray-400">
                Brak wariantów. Dodaj warianty produktu, aby zarządzać stanami magazynowymi.
            </p>
        </div>
    @endif
</div>
