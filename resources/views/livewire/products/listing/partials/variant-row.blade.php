{{--
    Partial: Variant Row (Expandable)
    Purpose: Display ProductVariant data in expandable row under parent product
    Props: $variant (ProductVariant model)

    Note: ProductVariant has different properties than Product:
    - Has: sku, name, is_active, is_default, position, attributes, prices, stock, images
    - Missing (use parent): productType, manufacturer, supplier_code, shopData

    Column widths controlled by colgroup in parent table
--}}

<tr class="variant-subrow bg-gray-800/30 hover:bg-gray-700/40 transition-colors border-b border-gray-700/50">
    {{-- 1. Checkbox for bulk selection --}}
    <td class="px-4 py-2">
        <input type="checkbox"
               wire:model="selectedVariants"
               value="{{ $variant->id }}"
               class="rounded border-gray-600 text-orange-500 focus:ring-orange-500 bg-gray-700"
               @click.stop>
    </td>

    {{-- 2. Variant Thumbnail --}}
    <td class="px-2 py-2">
        @if($variant->images && $variant->images->isNotEmpty())
            @php $coverImage = $variant->images->where('is_cover', true)->first() ?? $variant->images->first(); @endphp
            <img src="{{ $coverImage->thumbnail_url ?? $coverImage->url ?? asset('images/placeholder.png') }}"
                 alt="{{ $variant->name }}"
                 class="w-12 h-12 object-cover rounded"
                 loading="lazy" />
        @else
            <div class="w-12 h-12 bg-gray-700 rounded flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </td>

    {{-- 3. Variant SKU --}}
    <td class="px-4 py-2">
        <div class="text-sm font-medium text-primary flex items-center">
            <svg class="w-3 h-3 mr-2 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-orange-300 truncate">{{ $variant->sku }}</span>
        </div>
    </td>

    {{-- 4. Variant Name + Attributes --}}
    <td class="px-4 py-2">
        <div class="text-sm text-gray-300 truncate">
            {{ Str::limit($variant->name, 35) }}
        </div>
        @if($variant->attributes && $variant->attributes->isNotEmpty())
            <div class="flex flex-wrap gap-1 mt-1">
                @foreach($variant->attributes->take(2) as $attribute)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-700 text-gray-300">
                        {{ $attribute->value }}
                    </span>
                @endforeach
                @if($variant->attributes->count() > 2)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-600 text-gray-400">
                        +{{ $variant->attributes->count() - 2 }}
                    </span>
                @endif
            </div>
        @endif
    </td>

    {{-- 5. Type (from parent) --}}
    <td class="px-4 py-2">
        @if($variant->product?->productType)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                @switch($variant->product->productType->slug)
                    @case('pojazd') bg-green-800/70 text-green-300 @break
                    @case('czesc-zamienna') bg-blue-800/70 text-blue-300 @break
                    @case('odziez') bg-yellow-800/70 text-yellow-300 @break
                    @default bg-gray-700 text-gray-300
                @endswitch">
                {{ $variant->product->productType->name }}
            </span>
        @else
            <span class="text-xs text-gray-500">-</span>
        @endif
    </td>

    {{-- 6. Manufacturer (from parent) --}}
    <td class="px-4 py-2 text-sm text-gray-400 truncate">
        {{ $variant->product?->manufacturer ?? '-' }}
    </td>

    {{-- 7. Status --}}
    <td class="px-4 py-2">
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
            {{ $variant->is_active ? 'bg-green-800/70 text-green-300' : 'bg-red-800/70 text-red-300' }}">
            <span class="w-1.5 h-1.5 rounded-full mr-1 {{ $variant->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
            {{ $variant->is_active ? 'Aktywny' : 'Nieaktywny' }}
        </span>
    </td>

    {{-- 8. PrestaShop Sync --}}
    <td class="px-4 py-2 text-center">
        <span class="text-xs text-gray-500">-</span>
    </td>

    {{-- 9. Updated At --}}
    <td class="px-4 py-2 text-xs text-gray-500">
        {{ $variant->updated_at?->format('d.m.Y H:i') ?? '-' }}
    </td>

    {{-- 10. Actions --}}
    <td class="px-4 py-2 text-right">
        <div class="flex items-center justify-end gap-1">
            {{-- Edit Variant --}}
            <a href="{{ route('products.edit', $variant->product_id) }}?tab=warianty"
               class="p-1 text-gray-400 hover:text-blue-400 transition-colors"
               title="Edytuj wariant"
               @click.stop>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>

            {{-- Toggle Status --}}
            <button wire:click="toggleVariantStatus({{ $variant->id }})"
                    class="p-1 text-gray-400 hover:text-yellow-400 transition-colors"
                    title="{{ $variant->is_active ? 'Dezaktywuj' : 'Aktywuj' }}"
                    @click.stop>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($variant->is_active)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    @endif
                </svg>
            </button>

            {{-- Delete Variant --}}
            <button wire:click="deleteVariant({{ $variant->id }})"
                    wire:confirm="Czy na pewno chcesz usunac ten wariant?"
                    class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                    title="Usun wariant"
                    @click.stop>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </td>
</tr>
