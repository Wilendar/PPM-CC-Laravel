{{-- Products Modal - Shows products using an attribute value --}}
@if($showProductsModal && $selectedValueIdForProducts)
    @php
        $selectedValue = \App\Models\AttributeValue::find($selectedValueIdForProducts);
    @endphp
    @teleport('body')
    <div x-data="{ show: true }" x-show="show" x-cloak
         @keydown.escape.window="$wire.closeProductsModal()"
         class="fixed inset-0 z-50">

        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.closeProductsModal()"></div>

        <div class="relative z-10 h-full overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full border border-gray-700" @click.stop>

                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Produkty uzywajace wartosci</h3>
                                @if($selectedValue)
                                    <p class="text-sm text-gray-400 mt-1">
                                        <span class="font-semibold text-blue-400">{{ $selectedValue->label }}</span>
                                        ({{ $selectedValue->code }})
                                    </p>
                                @endif
                            </div>
                            <button wire:click="closeProductsModal" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                        @if($this->productsUsingValue->count() > 0)
                            <table class="w-full">
                                <thead class="text-left text-xs text-gray-400 uppercase bg-gray-900/50">
                                    <tr>
                                        <th class="px-3 py-2">SKU</th>
                                        <th class="px-3 py-2">Nazwa produktu</th>
                                        <th class="px-3 py-2 text-center">Warianty</th>
                                        <th class="px-3 py-2 text-right">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    @foreach($this->productsUsingValue as $product)
                                        <tr class="hover:bg-gray-700/30 transition-colors">
                                            <td class="px-3 py-3">
                                                <span class="font-mono text-sm text-gray-300">{{ $product['sku'] }}</span>
                                            </td>
                                            <td class="px-3 py-3">
                                                <span class="text-gray-200">{{ Str::limit($product['name'], 40) }}</span>
                                            </td>
                                            <td class="px-3 py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                                    {{ $product['variant_count'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <a href="{{ route('products.edit', $product['id']) }}"
                                                   class="text-blue-400 hover:text-blue-300 text-sm" target="_blank">
                                                    Otworz →
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-8">
                                <div class="text-4xl mb-3 opacity-50">📦</div>
                                <p class="text-gray-400">Brak produktow uzywajacych tej wartosci</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                        <button wire:click="closeProductsModal" class="btn-enterprise-secondary">Zamknij</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endteleport
@endif
